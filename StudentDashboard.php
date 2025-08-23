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

$theses = [];
if ($student_id) {
    $stmt = $db->prepare("SELECT t.*, u.name AS teacher_name, u.surname AS teacher_surname 
                          FROM topics t 
                          JOIN users u ON t.teacher_id = u.id 
                          WHERE t.assigned_to = ? AND t.status = 'confirmed'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
    }
}

if ($student_id) {
    // Παίρνουμε τις ενεργές διπλωματικές (status=confirmed)
    $stmt = $db->prepare("SELECT COUNT(*) as count, MIN(DATEDIFF(deadline, CURDATE())) as days
                          FROM topics
                          WHERE assigned_to = ? AND status = 'confirmed'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $active_count = intval($result['count']);
    if ($result['days'] !== null) {
        $days_remaining = max(0, intval($result['days'])); // Δεν εμφανίζουμε αρνητικές μέρες
    }
}

?>





<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Φοιτητή</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/StudentDashboard.css">
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
            <a href="StudentDashboard.php" class="active">
              <img src="icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentTopics.php">
              <img src="icons/file.png" alt="Topics" class="nav-icon">
              Θέματα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentManageThesis.php">
              <img src="icons/stats.png" alt="Manage Thesis" class="nav-icon">
              Διαχείριση ΔΕ
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

    <!-- Main Content -->
    <div class="col py-3">
      <!-- Mobile toggle button -->
      <button class="mobile-menu-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <i class="fas fa-bars"></i> Μενού
      </button>

      <!-- Student Dashboard Content -->
      <div class="container">
        <header>
            <h1>Καλώς ήρθες, Γιώργο!</h1>
        </header>
        <hr class="hr">

        <!-- Quick Stats Section -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                  <h3>Διπλωματική Εργασία</h3>
                  <p class="stat-number"><?= $active_count ?></p>
                  <p class="stat-label">Ενεργή</p>
              </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>Πρόοδος</h3>
                    <p class="stat-number">65%</p>
                    <p class="stat-label">Ολοκληρώθηκε</p>
                </div>
            </div>
            
            
        </div>

        <!-- Current Thesis Section -->
       <div class="thesis-section">
        <h2>Η Τρέχουσα Διπλωματική μου</h2>

        <?php if(empty($theses)): ?>
            <p>Δεν υπάρχουν ενεργές διπλωματικές με status confirmed.</p>
        <?php else: ?>
            <?php foreach($theses as $thesis): ?>
                <div class="thesis-card">
                    <div class="thesis-header">
                        <h3><?= htmlspecialchars($thesis['title']) ?></h3>
                        <span class="status-badge active"><?= htmlspecialchars($thesis['status']) ?></span>
                    </div>
                    <div class="thesis-details">
                        <p><strong>Επιβλέπων:</strong> <?= htmlspecialchars($thesis['teacher_name'].' '.$thesis['teacher_surname']) ?></p>
                        <p><strong>Ημερομηνία Έναρξης:</strong> <?= htmlspecialchars($thesis['confirmed_time']) ?></p>
                        <p><strong>Προθεσμία Υποβολής:</strong> <?= htmlspecialchars($thesis['deadline'] ?? '-') ?></p>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= intval($thesis['progress'] ?? 0) ?>%"></div>
                    </div>
                    <p class="progress-text">Πρόοδος: <?= intval($thesis['progress'] ?? 0) ?>%</p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

        <!-- Recent Activities Section -->
        <div class="activities-section">
            <h2>Πρόσφατες Ενεργειες</h2>
            <div class="activities-list">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Υποβολή Κεφαλαίου 3</h4>
                        <p>Ανεβάσατε το τρίτο κεφάλαιο της διπλωματικής σας</p>
                        <span class="activity-time">Πριν 2 ώρες</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-comment"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Σχόλιο από Επιβλέπων</h4>
                        <p>Η Δρ. Κωνσταντίνου έστειλε σχόλια για το κεφάλαιο 2</p>
                        <span class="activity-time">Πριν 1 ημέρα</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Εγκρίθηκε Κεφάλαιο 2</h4>
                        <p>Το δεύτερο κεφάλαιο εγκρίθηκε από τον επιβλέπων</p>
                        <span class="activity-time">Πριν 3 ημέρες</span>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Mobile menu toggle
  document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn) {
      mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('show');
      });
    }
  });
</script>
</body>
</html>
