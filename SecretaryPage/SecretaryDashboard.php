<?php
session_start();
require_once __DIR__ . "/../config.php"; // <-- Βεβαιώσου ότι το path είναι σωστό!

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.php");
    exit();
}

// Helper function
function getCount($db, $sql) {
    $res = mysqli_query($db, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return (int)$row['c'];
    }
    return 0;
}

// Fetch stats
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/SecretaryDashboard.css">
  <style>
    .stat-card { border-radius: 12px; padding: 1rem; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .stat-value { font-size: 2rem; font-weight: bold; }
    .stat-label { font-size: 1rem; color: #555; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='SecretaryProfile.php'">
        <div class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? "Γραμματεία") ?></div>
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing"><a href="SecretaryDashboard.php" class="active"><img src="../icons/menu.png" class="nav-icon">Dashboard</a></li>
          <li class="nav-spacing"><a href="SecretaryThesis.php"><img src="../icons/file.png" class="nav-icon">Προβολή ΔΕ</a></li>
          <li class="nav-spacing"><a href="SecretaryDataInput.php"><img src="../icons/graph.png" class="nav-icon">Εισαγωγή δεδομένων</a></li>
          <li class="nav-spacing"><a href="SecretaryManageThesis.php"><img src="../icons/stats.png" class="nav-icon">Διαχείριση ΔΕ</a></li>
          <div class="nav-separator"></div>
          <li class="nav-spacing"><a href="SecretaryProfile.php"><img src="../icons/setting.png" class="nav-icon">Προφίλ</a></li>
          <li class="nav-spacing"><a href="../logout.php" class="logout"><img src="../icons/logout.png" class="nav-icon">Αποσύνδεση</a></li>
        </ul>
      </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="col py-3">
      <div class="container">
        <header><h1>Καλώς ήρθες, <?= htmlspecialchars($_SESSION['name'] ?? "Γραμματεία") ?>!</h1></header>
        <hr class="hr">

        <div class="row g-4">
          <div class="col-md-4">
            <div class="stat-card bg-light">
              <div class="stat-value"><?= $students_count ?></div>
              <div class="stat-label">Φοιτητές</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card bg-light">
              <div class="stat-value"><?= $teachers_count ?></div>
              <div class="stat-label">Καθηγητές</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card bg-light">
              <div class="stat-value"><?= $total_thesis ?></div>
              <div class="stat-label">Συνολικές ΔΕ</div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="stat-card bg-success text-white">
              <div class="stat-value"><?= $active_thesis ?></div>
              <div class="stat-label">Ενεργές ΔΕ</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card bg-warning">
              <div class="stat-value"><?= $exam_thesis ?></div>
              <div class="stat-label">Υπό Εξέταση</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-card bg-secondary text-white">
              <div class="stat-value"><?= $completed_thesis ?></div>
              <div class="stat-label">Ολοκληρωμένες</div>
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
