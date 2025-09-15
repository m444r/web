<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$student_id = $_SESSION["userid"];
$message = "";

$studentName = "";
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $studentName = $row['name'] . " " . $row['surname'];
}
$stmt->close();


// Προετοιμασμένο query για ασφάλεια
// Φέρνουμε τα topics του φοιτητή
$sql = "SELECT t.*, u.name AS teacher_name, u.surname AS teacher_surname
        FROM topics t
        JOIN users u ON u.id = t.teacher_id
        WHERE t.assigned_to = ?";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$topics = $stmt->get_result();

?>




<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Λίστα Διπλωματικών</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/StudentThesis.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='StudentProfile.php'">
        
        <!-- User name link -->
        <div class="user-name">
           <?= htmlspecialchars($studentName) ?>
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="StudentDashboard.php" >
              <img src="icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-item nav-spacing">
            <a href="StudentThesis.php" class="active">
              <img src="icons/list.png" alt="Dashboard" class="nav-icon">
              Λίστα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentTopics.php">
              <img src="icons/file.png" alt="Topics" class="nav-icon">
              Ανάρτηση Αρχείων
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentManageThesis.php">
              <img src="icons/invitation.png" alt="Manage Thesis" class="nav-icon">
              Προσκλήσεις
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="StudentProfile.php">
              <img src="icons/setting.png" alt="Profile" class="nav-icon">
              Προφίλ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="logout.php" class="logout">
              <img src="icons/logout.png" alt="Logout" class="nav-icon">
              Αποσυνδεση
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="col py-3">
      <!-- Mobile toggle button -->
      <button class="mobile-menu-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <i class="fas fa-bars"></i> Μενού
      </button>
<!-- Main content -->
    <div class="container">
         <header>
        <h1>Λίστα Διπλωματικών</h1>
        </header>
      <hr class="hr">


      <?php if ($topics->num_rows > 0): ?>
        <div class="thesis-list">
          <?php while ($row = $topics->fetch_assoc()): ?>
            <div class="thesis-card">
                <?php
                // Map status to badge classes similar to teacher list
                $statusClass = 'pending';
                if ($row['status'] === 'confirmed') { $statusClass = 'active'; }
                elseif ($row['status'] === 'for examination') { $statusClass = 'warning'; }

                // Committee names in one line
                $committee_sql = "SELECT cr.*, u.name, u.surname
                                    FROM committee_requests cr
                                    JOIN users u ON u.id = cr.teacher_id
                                    WHERE cr.topic_id = ? AND cr.status = 'accepted'";
                $cstmt = $db->prepare($committee_sql);
                $cstmt->bind_param("i", $row["id"]);
                $cstmt->execute();
                $committee = $cstmt->get_result();
                $committeeNames = '—';
                if ($committee->num_rows > 0) {
                  $names = [];
                  while ($c = $committee->fetch_assoc()) {
                    $names[] = htmlspecialchars($c['name'].' '.$c['surname']);
                  }
                  $committeeNames = implode(', ', $names);
                }
                $cstmt->close();

                // Prepare submissions timeline
                $topic_id = (int)$row['id'];
                $stmtSub = $db->prepare("SELECT s.student_id, u.name, u.surname, s.file_path, s.comments, s.uploaded_at
                                          FROM student_submissions s
                                          JOIN users u ON u.id = s.student_id
                                          WHERE s.topic_id = ?
                                          ORDER BY s.uploaded_at DESC");
                $stmtSub->bind_param("i", $topic_id);
                $stmtSub->execute();
                $submissionsResult = $stmtSub->get_result();
              ?>

              <div class="d-flex justify-content-between align-items-center">
                <h3><?= htmlspecialchars($row["title"]) ?></h3>
                <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row["status"]) ?></span>
              </div>

              <div class="thesis-details mt-2">
                <p><strong>Περιγραφή:</strong> <?= htmlspecialchars($row["summary"]) ?></p>
                <p><strong>Προβολή PDF:</strong></p>
                <?php if (!empty($row['pdf_path'])): ?>
                    <a href="<?= htmlspecialchars($row['pdf_path']) ?>" 
                      target="_blank" 
                      class="status-badge">
                        Άνοιγμα PDF
                    </a>
                <?php else: ?>
                    <span class="status-badge">Δεν έχει ανέβει PDF</span>
                <?php endif; ?>                <p><strong>Φοιτητής:</strong> <?= htmlspecialchars($studentName) ?></p>
                <p><strong>Επιβλέπων:</strong> <?= htmlspecialchars($row["teacher_name"] . " " . $row["teacher_surname"]) ?></p>
                <p><strong>Τριμελής:</strong> <?= $committeeNames ?></p>
                <?php if (!empty($row["confirmed_time"])): ?>
                  <p><strong>Επιβεβαιώθηκε:</strong> <?= htmlspecialchars($row["confirmed_time"]) ?></p>
                <?php endif; ?>
                <p><strong>Προθεσμία:</strong> <?= htmlspecialchars($row["deadline"]) ?></p>

              </div>

              <div class="thesis-timeline">
                <h4>Χρονολόγιο Ενεργειών</h4>
                <ul>
                  <?php if (!empty($row['confirmed_time'])): ?>
                    <li><?= htmlspecialchars($row['confirmed_time']) ?> - Έναρξη διπλωματικής</li>
                  <?php endif; ?>
                  <?php if ($submissionsResult && $submissionsResult->num_rows > 0): ?>
                    <?php while ($sub = $submissionsResult->fetch_assoc()): ?>
                      <li>
                        <strong><?= htmlspecialchars($sub['name'].' '.$sub['surname']) ?>:</strong>
                        <?php if (!empty($sub['file_path'])): ?>
                          <a href="<?= htmlspecialchars($sub['file_path']) ?>" target="_blank"><?= basename($sub['file_path']) ?></a> -
                        <?php endif; ?>
                        <?= htmlspecialchars($sub['comments']) ?>
                        <small>(<?= date('d/m/Y H:i', strtotime($sub['uploaded_at'])) ?>)</small>
                    </li>
                    <?php endwhile; ?>
                  <?php endif; ?>
            </ul>
               </div>

          </div>
        <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p>Δεν βρέθηκαν διπλωματικές.</p>
      <?php endif; ?>




</div>
  </div>
    </body>
</html>