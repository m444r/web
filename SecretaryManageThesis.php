<?php
session_start();
require_once "../config.php"; // ensure this creates a mysqli $db connection (e.g. $db = new mysqli(...))

$debug = true; // set false in production

// --- Protect page: only secretary allowed
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.html");
    exit();
}

// --- Helper: flash messages
function set_flash($msg, $type = 'success') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// --- Ensure topic_logs table exists (simple schema, no FK constraints to avoid potential DB issues)
$chk = $db->query("SHOW TABLES LIKE 'topic_logs'");
if (!$chk || $chk->num_rows === 0) {
    $createLogs = "
    CREATE TABLE IF NOT EXISTS topic_logs (
      id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      topic_id INT(11) UNSIGNED NOT NULL,
      user_id INT(11) UNSIGNED DEFAULT NULL,
      action VARCHAR(100) NOT NULL,
      old_status VARCHAR(50) DEFAULT NULL,
      new_status VARCHAR(50) DEFAULT NULL,
      details TEXT DEFAULT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_topic (topic_id),
      KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    if (!$db->query($createLogs) && $debug) {
        // not fatal — show later if debug
        $createLogsErr = $db->error;
    }
}

// --- Ensure topics.protocol_ap column exists (used to store the ΑΠ)
$colChk = $db->query("SHOW COLUMNS FROM topics LIKE 'protocol_ap'");
if ($colChk && $colChk->num_rows === 0) {
    $alter = "ALTER TABLE topics ADD COLUMN protocol_ap VARCHAR(100) DEFAULT NULL";
    if (!$db->query($alter) && $debug) {
        $alterErr = $db->error;
    }
}

// --- Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $topic_id = (int)($_POST['topic_id'] ?? 0);
    if ($topic_id <= 0) {
        set_flash('Σφάλμα: Μη έγκυρο ID διπλωματικής.', 'danger');
        header("Location: SecretaryManageThesis.php");
        exit();
    }

    // fetch old status before any change
    $oldStatus = null;
    $ps = $db->prepare("SELECT status FROM topics WHERE id = ?");
    $ps->bind_param("i", $topic_id);
    $ps->execute();
    $ps->bind_result($oldStatus);
    $ps->fetch();
    $ps->close();

    if ($action === 'register_protocol') {
        $protocol_number = trim($_POST['protocol_number'] ?? '');
        if ($protocol_number === '') {
            set_flash('Παρακαλώ εισάγετε Αριθμό Πρωτοκόλλου.', 'danger');
            header("Location: SecretaryManageThesis.php");
            exit();
        }

        $u = $_SESSION['userid'];
        $upd = $db->prepare("UPDATE topics SET protocol_ap = ? WHERE id = ?");
        $upd->bind_param("si", $protocol_number, $topic_id);
        $ok = $upd->execute();
        $upd->close();

        if ($ok) {
            $log = $db->prepare("INSERT INTO topic_logs (topic_id,user_id,action,old_status,new_status,details) VALUES (?,?,?,?,?,?)");
            $detail = "ΑΠ: " . $protocol_number;
            $log->bind_param("iissss", $topic_id, $u, $action, $oldStatus, null, $detail);
            $log->execute();
            $log->close();

            set_flash("Ο ΑΠ ($protocol_number) καταχωρήθηκε επιτυχώς.", 'success');
        } else {
            set_flash("Σφάλμα κατά την καταχώρηση ΑΠ: " . ($debug ? $db->error : ''), 'danger');
        }

        header("Location: SecretaryManageThesis.php");
        exit();
    }

    if ($action === 'cancel_thesis') {
        $cancel_info = trim($_POST['cancellation_reason'] ?? '');
        if ($cancel_info === '') {
            set_flash('Παρακαλώ εισάγετε τον αριθμό και έτος της ΓΣ (λόγος ακύρωσης).', 'danger');
            header("Location: SecretaryManageThesis.php");
            exit();
        }

        $u = $_SESSION['userid'];
        $reason_text = 'από Γραμματεία';
        $upd = $db->prepare("UPDATE topics SET status = 'cancelled', cancel_reason = ?, cancel_info = ? WHERE id = ?");
        $upd->bind_param("ssi", $reason_text, $cancel_info, $topic_id);
        $ok = $upd->execute();
        $upd->close();

        if ($ok) {
            $log = $db->prepare("INSERT INTO topic_logs (topic_id,user_id,action,old_status,new_status,details) VALUES (?,?,?,?,?,?)");
            $detail = "Ακύρωση ΓΣ: " . $cancel_info;
            $newStatus = 'cancelled';
            $log->bind_param("iissss", $topic_id, $u, $action, $oldStatus, $newStatus, $detail);
            $log->execute();
            $log->close();

            set_flash("Η ΔΕ ακυρώθηκε επιτυχώς.", 'success');
        } else {
            set_flash("Σφάλμα κατά την ακύρωση: " . ($debug ? $db->error : ''), 'danger');
        }

        header("Location: SecretaryManageThesis.php");
        exit();
    }

    if ($action === 'complete_thesis') {
        // optional final_grade
        $final_grade = null;
        if (isset($_POST['final_grade']) && $_POST['final_grade'] !== '') {
            $final_grade = floatval(str_replace(',', '.', $_POST['final_grade']));
        }

        $u = $_SESSION['userid'];
        if ($final_grade !== null) {
            $upd = $db->prepare("UPDATE topics SET status = 'completed', final_grade = ? WHERE id = ?");
            $upd->bind_param("di", $final_grade, $topic_id);
        } else {
            $upd = $db->prepare("UPDATE topics SET status = 'completed' WHERE id = ?");
            $upd->bind_param("i", $topic_id);
        }
        $ok = $upd->execute();
        $upd->close();

        if ($ok) {
            $log = $db->prepare("INSERT INTO topic_logs (topic_id,user_id,action,old_status,new_status,details) VALUES (?,?,?,?,?,?)");
            $detail = $final_grade !== null ? ("Τελικός βαθμός: " . $final_grade) : "Ολοκλήρωση χωρίς βαθμό";
            $newStatus = 'completed';
            $log->bind_param("iissss", $topic_id, $u, $action, $oldStatus, $newStatus, $detail);
            $log->execute();
            $log->close();

            set_flash("Η ΔΕ χαρακτηρίστηκε ως 'Περατωμένη'." . ($final_grade !== null ? " Βαθμός: $final_grade" : ""), 'success');
        } else {
            set_flash("Σφάλμα κατά την ολοκλήρωση: " . ($debug ? $db->error : ''), 'danger');
        }

        header("Location: SecretaryManageThesis.php");
        exit();
    }

    // unknown action
    set_flash('Μη έγκυρη ενέργεια.', 'danger');
    header("Location: SecretaryManageThesis.php");
    exit();
}

// --- Fetch list of theses
$sql = "
SELECT
  t.id,
  t.title,
  t.status,
  CASE WHEN t.assigned_time IS NULL OR t.assigned_time IN ('0000-00-00 00:00:00','0000-00-00') THEN t.created_at ELSE t.assigned_time END AS start_date,
  t.assigned_to,
  t.teacher_id,
  t.protocol_ap,
  t.cancel_reason,
  t.cancel_info,
  t.final_grade,
  s.name AS student_name,
  s.surname AS student_surname,
  sup.name AS supervisor_name,
  sup.surname AS supervisor_surname
FROM topics t
LEFT JOIN users s  ON t.assigned_to = s.id
LEFT JOIN users sup ON t.teacher_id  = sup.id
ORDER BY start_date DESC, t.id DESC
";

$tRes = $db->query($sql);
if (!$tRes && $debug) {
    $queryErr = $db->error;
}

// --- Recent management history (topic_logs)
$logs = [];
$lr = $db->query("SELECT l.*, u.name AS user_name, u.surname AS user_surname FROM topic_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10");
if ($lr) {
    while ($r = $lr->fetch_assoc()) $logs[] = $r;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Διαχείριση ΔΕ - Γραμματεία</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/SecretaryManageThesis.css">
</head>
<body>
<div class="container-fluid">
  <div class="row flex-nowrap">
     <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='SecretaryProfile.html'">
        
        <!-- User name link -->
        <div class="user-name">
          Γραμματεία
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="SecretaryDashboard.php">
              <img src="../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryThesis.php">
              <img src="../icons/file.png" alt="Thesis View" class="nav-icon">
              Προβολή ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryDataInput.html">
              <img src="../icons/graph.png" alt="Data Input" class="nav-icon">
              Εισαγωγή δεδομένων
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryManageThesis.html" class="active">
              <img src="../icons/stats.png" alt="Manage Thesis" class="nav-icon">
              Διαχείριση ΔΕ
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="SecretaryProfile.html">
              <img src="../icons/setting.png" alt="Profile" class="nav-icon">
              Προφίλ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="../login_page.php" class="logout">
              <img src="../icons/logout.png" alt="Logout" class="nav-icon">
              Αποσυνδεση
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Main -->
    <div class="col py-3">
      <div class="container">
        <header><h1>Διαχείριση Διπλωματικών Εργασιών</h1></header>
        <hr>

        <!-- Flash -->
        <?php if ($f = get_flash()): ?>
          <div class="alert alert-<?php echo ($f['type'] === 'danger' ? 'danger' : 'success'); ?>">
            <?php echo htmlspecialchars($f['msg']); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($createLogsErr) && $debug): ?>
          <div class="alert alert-warning">topic_logs creation error: <?php echo htmlspecialchars($createLogsErr); ?></div>
        <?php endif; ?>
        <?php if (isset($alterErr) && $debug): ?>
          <div class="alert alert-warning">ALTER topics error: <?php echo htmlspecialchars($alterErr); ?></div>
        <?php endif; ?>
        <?php if (isset($queryErr) && $debug): ?>
          <div class="alert alert-danger">Query error: <?php echo htmlspecialchars($queryErr); ?></div>
        <?php endif; ?>

        <!-- Management list -->
        <div class="management-section">
          <?php if ($tRes && $tRes->num_rows > 0): ?>
            <?php while ($row = $tRes->fetch_assoc()): ?>
              <?php
                $topicId = (int)$row['id'];
                $startRaw = $row['start_date'];
                $startDisplay = ($startRaw && $startRaw !== '0000-00-00 00:00:00') ? date("d/m/Y", strtotime($startRaw)) : '-';
              ?>
              <div class="card mb-3">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h5 class="card-title mb-1"><?php echo htmlspecialchars($row['title'] ?: '– χωρίς τίτλο –'); ?></h5>
                      <p class="mb-1"><strong>Φοιτητής:</strong> <?php echo htmlspecialchars(($row['student_name'] ?? '-') . ' ' . ($row['student_surname'] ?? '')); ?></p>
                      <p class="mb-1"><strong>Επιβλέπων:</strong> <?php echo htmlspecialchars(($row['supervisor_name'] ?? '-') . ' ' . ($row['supervisor_surname'] ?? '')); ?></p>
                      <p class="mb-0"><strong>Κατάσταση:</strong> <?php echo htmlspecialchars($row['status']); ?> | <strong>Ανάθεση:</strong> <?php echo $startDisplay; ?></p>
                      <p class="mb-0"><strong>ΑΠ:</strong> <?php echo htmlspecialchars($row['protocol_ap'] ?? '-'); ?> | <strong>Τελικός Βαθμός:</strong> <?php echo htmlspecialchars($row['final_grade'] ?? '-'); ?></p>
                    </div>

                    <div style="min-width:260px">
                      <!-- Register Protocol -->
                      <form method="POST" class="mb-2" onsubmit="return confirm('Καταχωρήστε τον ΑΠ;');">
                        <input type="hidden" name="action" value="register_protocol">
                        <input type="hidden" name="topic_id" value="<?php echo $topicId; ?>">
                        <div class="input-group">
                          <input type="text" name="protocol_number" class="form-control" placeholder="Αριθμός Πρωτοκόλλου (π.χ. 123/2024)" required>
                          <button class="btn btn-outline-primary" type="submit">Καταχώρηση ΑΠ</button>
                        </div>
                      </form>

                      <!-- Cancel Thesis -->
                      <form method="POST" class="mb-2" onsubmit="return confirm('Είστε σίγουροι ότι θέλετε να ακυρώσετε αυτή τη ΔΕ;');">
                        <input type="hidden" name="action" value="cancel_thesis">
                        <input type="hidden" name="topic_id" value="<?php echo $topicId; ?>">
                        <div class="input-group">
                          <input type="text" name="cancellation_reason" class="form-control" placeholder="Αριθμός και έτος ΓΣ (π.χ. 142/2024)" required>
                          <button class="btn btn-outline-danger" type="submit">Ακύρωση ΔΕ</button>
                        </div>
                      </form>

                      <!-- Complete Thesis -->
                      <form method="POST" onsubmit="return confirm('Οριστική ολοκλήρωση ΔΕ;');">
                        <input type="hidden" name="action" value="complete_thesis">
                        <input type="hidden" name="topic_id" value="<?php echo $topicId; ?>">
                        <div class="input-group">
                          <input type="text" name="final_grade" class="form-control" placeholder="Τελικός βαθμός (προαιρετικό)">
                          <button class="btn btn-outline-success" type="submit">Ολοκλήρωση ΔΕ</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="alert alert-info">Δεν βρέθηκαν διπλωματικές εργασίες.</div>
          <?php endif; ?>
        </div>

        <!-- Recent management history -->
        <div class="history-section mt-4">
          <h4>Πρόσφατο Ιστορικό Διαχείρισης</h4>
          <?php if (!empty($logs)): ?>
            <ul class="list-group">
              <?php foreach ($logs as $l): ?>
                <li class="list-group-item">
                  <strong><?php echo htmlspecialchars($l['action']); ?></strong>
                  &nbsp;—&nbsp; <?php echo htmlspecialchars($l['details'] ?? '-'); ?>
                  <br><small class="text-muted"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($l['created_at']))); ?> by <?php echo htmlspecialchars(($l['user_name'] ?? '-') . ' ' . ($l['user_surname'] ?? '')); ?></small>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-muted">Δεν υπάρχουν καταγεγραμμένες ενέργειες.</p>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// close resources if any
?>
