<?php
session_start();
require_once "../config.php"; // ensure this creates a mysqli $db connection ($db = new mysqli(...))

$debug = true; // false σε production

// --- Protect page: only secretary allowed
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.php");
    exit();
}

// --- Helper flash messages
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

// --- Ensure topic_logs table exists
$db->query("
CREATE TABLE IF NOT EXISTS topic_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  topic_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  old_status VARCHAR(50) DEFAULT NULL,
  new_status VARCHAR(50) DEFAULT NULL,
  details TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_topic (topic_id),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// --- Ensure topics has needed columns
$db->query("ALTER TABLE topics ADD COLUMN IF NOT EXISTS protocol_ap VARCHAR(100) DEFAULT NULL");
$db->query("ALTER TABLE topics ADD COLUMN IF NOT EXISTS cancel_reason TEXT DEFAULT NULL");
$db->query("ALTER TABLE topics ADD COLUMN IF NOT EXISTS cancel_info VARCHAR(255) DEFAULT NULL");
$db->query("ALTER TABLE topics ADD COLUMN IF NOT EXISTS final_grade FLOAT DEFAULT NULL");
$db->query("ALTER TABLE topics ADD COLUMN IF NOT EXISTS nemertes_url VARCHAR(255) DEFAULT NULL");

// --- Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $topic_id = (int)($_POST['topic_id'] ?? 0);

    // fetch old status
    $oldStatus = null;
    $ps = $db->prepare("SELECT status FROM topics WHERE id=?");
    $ps->bind_param("i", $topic_id);
    $ps->execute();
    $ps->bind_result($oldStatus);
    $ps->fetch();
    $ps->close();

    if (!$oldStatus) {
        set_flash("Δεν βρέθηκε η διπλωματική.", "danger");
        header("Location: SecretaryManageThesis.php");
        exit();
    }

    // --- Register Protocol (only if active)
    if ($action === 'register_protocol' && in_array($oldStatus, ['confirmed','available'])) {
        $protocol_number = trim($_POST['protocol_number'] ?? '');
        if ($protocol_number === '') {
            set_flash("Παρακαλώ εισάγετε ΑΠ.", "danger");
        } else {
            $upd = $db->prepare("UPDATE topics SET protocol_ap=? WHERE id=?");
            $upd->bind_param("si", $protocol_number, $topic_id);
            $ok = $upd->execute();
            $upd->close();

            if ($ok) {
                $log = $db->prepare("INSERT INTO topic_logs (topic_id,user_id,action,old_status,new_status,details) VALUES (?,?,?,?,?,?)");
                $uid = $_SESSION['userid'];
                $detail = "ΑΠ: ".$protocol_number;
                $log->bind_param("iissss", $topic_id, $uid, $action, $oldStatus, $oldStatus, $detail);
                $log->execute();
                $log->close();
                set_flash("Ο ΑΠ ($protocol_number) καταχωρήθηκε επιτυχώς.");
            }
        }
        header("Location: SecretaryManageThesis.php"); exit();
    }

    // --- Cancel Thesis (only if active)
    if ($action === 'cancel_thesis' && in_array($oldStatus, ['confirmed','available'])) {
        $cancel_info = trim($_POST['cancellation_reason'] ?? '');
        if ($cancel_info === '') {
            set_flash("Παρακαλώ εισάγετε ΓΣ για ακύρωση.", "danger");
        } else {
            $reason_text = "κατόπιν αίτησης Φοιτητή/τριας";
            $upd = $db->prepare("UPDATE topics SET status='cancelled', cancel_reason=?, cancel_info=? WHERE id=?");
            $upd->bind_param("ssi", $reason_text, $cancel_info, $topic_id);
            $ok = $upd->execute();
            $upd->close();

            if ($ok) {
                $uid = $_SESSION['userid'];
                $newStatus = "cancelled";
                $detail = "Ακύρωση ΓΣ: ".$cancel_info;
                $log = $db->prepare("INSERT INTO topic_logs (topic_id,user_id,action,old_status,new_status,details) VALUES (?,?,?,?,?,?)");
                $log->bind_param("iissss", $topic_id, $uid, $action, $oldStatus, $newStatus, $detail);
                $log->execute(); $log->close();
                set_flash("Η ΔΕ ακυρώθηκε επιτυχώς.");
            }
        }
        header("Location: SecretaryManageThesis.php"); exit();
    }

    // --- Complete Thesis (only if under examination)
    if ($action === 'complete_thesis' && $oldStatus === 'for examination') {
        $grade = trim($_POST['final_grade'] ?? '');
        $grade = $grade !== '' ? floatval(str_replace(',', '.', $grade)) : null;

        // check nemertes url
        $nem = null;
        $chk = $db->prepare("SELECT nemertes_url FROM topics WHERE id=?");
        $chk->bind_param("i", $topic_id);
        $chk->execute();
        $chk->bind_result($nem);
        $chk->fetch();
        $chk->close();

        if (empty($nem)) {
            set_flash("Δεν υπάρχει σύνδεσμος προς Νημερτή. Δεν μπορεί να ολοκληρωθεί.", "danger");
        } else {
            if ($grade !== null) {
                $upd = $db->prepare("UPDATE topics SET status='completed', final_grade=? WHERE id=?");
                $upd->bind_param("di", $grade, $topic_id);
            } else {
                $upd = $db->prepare("UPDATE topics SET status='completed' WHERE id=?");
                $upd->bind_param("i", $topic_id);
            }
            $ok = $upd->execute(); $upd->close();

            if ($ok) {
                $uid = $_SESSION['userid'];
                $newStatus = "completed";
                $detail = "Ολοκλήρωση ΔΕ".($grade!==null ? " με βαθμό $grade" : "");
                $log = $db->prepare("INSERT INTO topic_logs (topic_id,user_id,action,old_status,new_status,details) VALUES (?,?,?,?,?,?)");
                $log->bind_param("iissss", $topic_id, $uid, $action, $oldStatus, $newStatus, $detail);
                $log->execute(); $log->close();
                set_flash("Η ΔΕ χαρακτηρίστηκε ως Περατωμένη.");
            }
        }
        header("Location: SecretaryManageThesis.php"); exit();
    }

    set_flash("Μη έγκυρη ενέργεια ή κατάσταση.", "danger");
    header("Location: SecretaryManageThesis.php"); exit();
}

// --- Fetch all theses
$q = "
SELECT t.id,t.title,t.status,t.protocol_ap,t.final_grade,t.nemertes_url,
       s.name AS student_name,s.surname AS student_surname,
       sup.name AS supervisor_name,sup.surname AS supervisor_surname
FROM topics t
LEFT JOIN users s ON t.assigned_to=s.id
LEFT JOIN users sup ON t.teacher_id=s.id
ORDER BY t.id DESC";
$tRes = $db->query($q);

// --- Fetch logs
$logs = [];
$lr = $db->query("SELECT l.*,u.name AS uname,u.surname AS usurname FROM topic_logs l LEFT JOIN users u ON l.user_id=u.id ORDER BY l.created_at DESC LIMIT 10");
if ($lr) { while ($r=$lr->fetch_assoc()) $logs[]=$r; }
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="utf-8">
  <title>Διαχείριση ΔΕ - Γραμματεία</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <link rel="stylesheet" href="../css/SecretaryManageThesis.css"> </head> <body> <div class="container-fluid"> <div class="row flex-nowrap"> <!-- Sidebar --> <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu"> <div class="sidebar-container"> <!-- Profile pic --> <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='SecretaryProfile.html'"> <!-- User name link --> <div class="user-name"> Γραμματεία </div> <!-- Name separator --> <div class="name-separator"></div> <ul class="nav nav-pills flex-column mb-auto w-100"> <li class="nav-item nav-spacing"> <a href="SecretaryDashboard.php"> <img src="../icons/menu.png" alt="Dashboard" class="nav-icon"> Dashboard </a> </li> <li class="nav-spacing"> <a href="SecretaryThesis.php"> <img src="../icons/file.png" alt="Thesis View" class="nav-icon"> Προβολή ΔΕ </a> </li> <li class="nav-spacing"> <a href="SecretaryDataInput.html"> <img src="../icons/graph.png" alt="Data Input" class="nav-icon"> Εισαγωγή δεδομένων </a> </li> <li class="nav-spacing"> <a href="SecretaryManageThesis.php" class="active"> <img src="../icons/stats.png" alt="Manage Thesis" class="nav-icon"> Διαχείριση ΔΕ </a> </li> <div class="nav-separator"></div> <li class="nav-spacing"> <a href="SecretaryProfile.html"> <img src="../icons/setting.png" alt="Profile" class="nav-icon"> Προφίλ </a> </li> <li class="nav-spacing"> <a href="../login_page.php" class="logout"> <img src="../icons/logout.png" alt="Logout" class="nav-icon"> Αποσυνδεση </a> </li> </ul> </div> </div>
</head>
<body>
<div class="container mt-4">
  <h1>Διαχείριση Διπλωματικών Εργασιών</h1>
  <hr>

  <?php if ($f=get_flash()): ?>
    <div class="alert alert-<?php echo $f['type']==='danger'?'danger':'success'; ?>">
      <?php echo htmlspecialchars($f['msg']); ?>
    </div>
  <?php endif; ?>

  <?php if ($tRes && $tRes->num_rows>0): ?>
    <?php while($row=$tRes->fetch_assoc()): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h5><?php echo htmlspecialchars($row['title']); ?></h5>
          <p><strong>Φοιτητής:</strong> <?php echo htmlspecialchars($row['student_name'].' '.$row['student_surname']); ?></p>
          <p><strong>Επιβλέπων:</strong> <?php echo htmlspecialchars($row['supervisor_name'].' '.$row['supervisor_surname']); ?></p>
          <p><strong>Κατάσταση:</strong> <?php echo htmlspecialchars($row['status']); ?></p>
          <p><strong>ΑΠ:</strong> <?php echo htmlspecialchars($row['protocol_ap']??'-'); ?> | <strong>Βαθμός:</strong> <?php echo htmlspecialchars($row['final_grade']??'-'); ?></p>

          <!-- Actions depending on status -->
          <?php if (in_array($row['status'], ['confirmed','available'])): ?>
            <form method="post" class="mb-2">
              <input type="hidden" name="action" value="register_protocol">
              <input type="hidden" name="topic_id" value="<?php echo $row['id']; ?>">
              <div class="input-group">
                <input type="text" name="protocol_number" class="form-control" placeholder="ΑΠ ΓΣ (π.χ. 123/2024)">
                <button class="btn btn-outline-primary">Καταχώρηση ΑΠ</button>
              </div>
            </form>
            <form method="post">
              <input type="hidden" name="action" value="cancel_thesis">
              <input type="hidden" name="topic_id" value="<?php echo $row['id']; ?>">
              <div class="input-group">
                <input type="text" name="cancellation_reason" class="form-control" placeholder="Αριθμός και έτος ΓΣ">
                <button class="btn btn-outline-danger">Ακύρωση</button>
              </div>
            </form>
          <?php endif; ?>

          <?php if ($row['status']==='for examination'): ?>
            <form method="post">
              <input type="hidden" name="action" value="complete_thesis">
              <input type="hidden" name="topic_id" value="<?php echo $row['id']; ?>">
              <div class="input-group">
                <input type="text" name="final_grade" class="form-control" placeholder="Τελικός βαθμός">
                <button class="btn btn-outline-success">Ολοκλήρωση</button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="alert alert-info">Δεν βρέθηκαν διπλωματικές.</div>
  <?php endif; ?>

  <h4 class="mt-4">Ιστορικό</h4>
  <?php if ($logs): ?>
    <ul class="list-group">
      <?php foreach($logs as $l): ?>
        <li class="list-group-item">
          <strong><?php echo htmlspecialchars($l['action']); ?></strong>
          - <?php echo htmlspecialchars($l['details']); ?>
          <br><small><?php echo htmlspecialchars($l['created_at']); ?> από <?php echo htmlspecialchars($l['uname'].' '.$l['usurname']); ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
