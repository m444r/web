<?php
session_start();
require_once "../config.php";

$debug = true; 


if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.php");
    exit();
}


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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $topic_id = (int)($_POST['topic_id'] ?? 0);

    
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
                set_flash("Ο ΑΠ ($protocol_number) καταχωρήθηκε επιτυχώς.");
            }
        }
        header("Location: SecretaryManageThesis.php"); exit();
    }

   
if ($action === 'cancel_thesis' && in_array($oldStatus, ['confirmed','available'])) {
    $cancel_info = trim($_POST['cancellation_reason'] ?? '');
    if ($cancel_info === '') {
        set_flash("Παρακαλώ εισάγετε ΓΣ για ακύρωση.", "danger");
    } else {
        $reason_text = "κατόπιν αίτησης Φοιτητή/τριας";
        $cancel_info_formatted = "ΓΣ " . $cancel_info; 

        $upd = $db->prepare("UPDATE topics SET status='cancelled', cancel_reason=?, cancel_info=? WHERE id=?");
        $upd->bind_param("ssi", $reason_text, $cancel_info_formatted, $topic_id);
        $ok = $upd->execute();
        $upd->close();

        if ($ok) {
            set_flash("Η ΔΕ ακυρώθηκε επιτυχώς.");
        }
    }
    header("Location: SecretaryManageThesis.php"); exit();
}


    
   if ($action === 'complete_thesis' && $oldStatus === 'completed') {
    // Παίρνουμε το library_link και τον final_grade
    $nem = null;
    $db_grade = null;
    $chk = $db->prepare("SELECT library_link, final_grade FROM topics WHERE id=?");
    $chk->bind_param("i", $topic_id);
    $chk->execute();
    $chk->bind_result($nem, $db_grade);
    $chk->fetch();
    $chk->close();

    if (empty($nem)) {
        set_flash("Δεν υπάρχει σύνδεσμος προς Νημερτή. Δεν μπορεί να ολοκληρωθεί.", "danger");
    } else {
        if ($db_grade !== null) {
            // Υπάρχει βαθμός -> μπορούμε να χαρακτηρίσουμε ως finished
            $upd = $db->prepare("UPDATE topics SET status='finished' WHERE id=?");
            $upd->bind_param("i", $topic_id);
            $ok = $upd->execute();
            $upd->close();

            if ($ok) {
                set_flash("Η ΔΕ χαρακτηρίστηκε ως Περατωμένη.");
            } else {
                set_flash("Πρόβλημα κατά την ενημέρωση της βάσης.", "danger");
            }
        } else {
            set_flash("Δεν υπάρχει καταχωρημένος βαθμός. Η εργασία δεν μπορεί να ολοκληρωθεί.", "danger");
        }
    }
    header("Location: SecretaryManageThesis.php"); 
    exit();
}


    set_flash("Μη έγκυρη ενέργεια ή κατάσταση.", "danger");
    header("Location: SecretaryManageThesis.php"); exit();
}


$q = "
SELECT t.id,t.title,t.status,t.protocol_ap,t.final_grade,t.nemertes_url,
       s.name AS student_name,s.surname AS student_surname,
       sup.name AS supervisor_name,sup.surname AS supervisor_surname
FROM topics t
LEFT JOIN users s ON t.assigned_to=s.id
LEFT JOIN users sup ON t.teacher_id=sup.id
ORDER BY t.id DESC";
$tRes = $db->query($q);
?>



<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Διαχείριση ΔΕ - Γραμματεία</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
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
        <img src="../icons/account.png" alt="Profile" class="profile-avatar">
        <!-- User name link -->
        <div class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Γραμματεία') ?></div>
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
            <a href="SecretaryDataInput.php">
              <img src="../icons/graph.png" alt="Data Input" class="nav-icon">
              Εισαγωγή δεδομένων
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryManageThesis.php" class="active">
              <img src="../icons/stats.png" alt="Manage Thesis" class="nav-icon">
              Διαχείριση ΔΕ
            </a>
          </li>
          <div class="nav-separator"></div>
          <li class="nav-spacing">
            <a href="../logout.php" class="logout">
              <img src="../icons/logout.png" alt="Logout" class="nav-icon">
              Αποσυνδεση
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col py-3">
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

          <?php if ($row['status']==='completed'): ?>
            <form method="post">
              <input type="hidden" name="action" value="complete_thesis">
              <input type="hidden" name="topic_id" value="<?php echo $row['id']; ?>">
              <div class="input-group">
                
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
    </div>
  </div>
</div>
</body>
</html>