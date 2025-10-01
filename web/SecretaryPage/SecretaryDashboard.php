<?php
session_start();
require_once __DIR__ . "/../config.php";

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.php");
    exit();
}

function getCount($db, $sql) {
    $res = mysqli_query($db, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return (int)$row['c'];
    }
    return 0;
}

$students_count   = getCount($db, "SELECT COUNT(*) AS c FROM users WHERE role='student'");
$teachers_count   = getCount($db, "SELECT COUNT(*) AS c FROM users WHERE role='teacher'");
$total_thesis     = getCount($db, "SELECT COUNT(*) AS c FROM topics");
$active_thesis    = getCount($db, "SELECT COUNT(*) AS c FROM topics WHERE status IN ('available','confirmed')");
$exam_thesis      = getCount($db, "SELECT COUNT(*) AS c FROM topics WHERE status='for examination'");
$completed_thesis = getCount($db, "SELECT COUNT(*) AS c FROM topics WHERE status='completed'");
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - Γραμματεία</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/SecretaryDashboard.css">
</head>
<body>
<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        <img src="../icons/account.png" alt="Profile" class="profile-avatar">
        <div class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? "Γραμματεία") ?></div>
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing"><a href="SecretaryDashboard.php" class="active"><img src="../icons/menu.png" class="nav-icon">Dashboard</a></li>
          <li class="nav-spacing"><a href="SecretaryThesis.php"><img src="../icons/file.png" class="nav-icon">Προβολή ΔΕ</a></li>
          <li class="nav-spacing"><a href="SecretaryDataInput.html"><img src="../icons/graph.png" class="nav-icon">Εισαγωγή δεδομένων</a></li>
          <li class="nav-spacing"><a href="SecretaryManageThesis.php"><img src="../icons/stats.png" class="nav-icon">Διαχείριση ΔΕ</a></li>
          <div class="nav-separator"></div>
          <li class="nav-spacing"><a href="../logout.php" class="logout"><img src="../icons/logout.png" class="nav-icon">Αποσύνδεση</a></li>
        </ul>
      </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="col py-3">
      <div class="container">
        <header><h1>Καλώς ήρθες, <?= htmlspecialchars($_SESSION['name'] ?? "Γραμματεία") ?>!</h1></header>
        <hr class="hr">

        <div class="stats-section">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
              <h3>ΦΟΙΤΗΤΕΣ</h3>
              <div class="stat-number"><?= $students_count ?></div>
              <p class="stat-label">Συνολικοί φοιτητές</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
              <h3>ΚΑΘΗΓΗΤΕΣ</h3>
              <div class="stat-number"><?= $teachers_count ?></div>
              <p class="stat-label">Συνολικοί καθηγητές</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
              <h3>ΣΥΝΟΛΙΚΕΣ ΔΕ</h3>
              <div class="stat-number"><?= $total_thesis ?></div>
              <p class="stat-label">Όλες οι διπλωματικές</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-play-circle"></i>
            </div>
            <div class="stat-content">
              <h3>ΕΝΕΡΓΕΣ ΔΕ</h3>
              <div class="stat-number"><?= $active_thesis ?></div>
              <p class="stat-label">Σε εξέλιξη</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-search"></i>
            </div>
            <div class="stat-content">
              <h3>ΥΠΟ ΕΞΕΤΑΣΗ</h3>
              <div class="stat-number"><?= $exam_thesis ?></div>
              <p class="stat-label">Προετοιμασία εξέτασης</p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
              <h3>ΟΛΟΚΛΗΡΩΜΕΝΕΣ</h3>
              <div class="stat-number"><?= $completed_thesis ?></div>
              <p class="stat-label">Τελειωμένες</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($db); ?>