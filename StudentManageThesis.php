<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$assigned_to = $_SESSION["userid"];
$message = "";

// Φέρνουμε το όνομα φοιτητή
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $assigned_to);
$stmt->execute();
$result = $stmt->get_result();
$studentName = "";
if ($row = $result->fetch_assoc()) {
    $studentName = $row['name'] . " " . $row['surname'];
}

// Φέρνουμε τη διπλωματική του φοιτητή
$stmt = $db->prepare("
    SELECT t.id, t.title, t.status, u.name as supervisor_name, u.surname as supervisor_surname, t.deadline
    FROM topics t
    JOIN users u ON t.teacher_id = u.id
    WHERE t.assigned_to = ?
    ORDER BY t.id DESC LIMIT 1
");
$stmt->bind_param("i", $assigned_to);
$stmt->execute();
$topic = $stmt->get_result()->fetch_assoc();

// Φόρτωση καθηγητών
$teachers = $db->query("SELECT id, name, surname FROM users WHERE role='teacher'");

// Αν ο φοιτητής έστειλε πρόσκληση
if (isset($_POST['teacher_id']) && $topic) {
    $teacher_id = intval($_POST['teacher_id']);
    
    // Έλεγξε αν έχει ξανασταλεί πρόσκληση
    $stmt = $db->prepare("SELECT id FROM committee_requests WHERE topic_id=? AND teacher_id=?");
    $stmt->bind_param("ii", $topic['id'], $teacher_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    
    if (!$exists) {
        $status = "pending";
        $stmt = $db->prepare("INSERT INTO committee_requests (topic_id, teacher_id, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $topic['id'], $teacher_id, $status);
        $stmt->execute();
    }
}

// Φόρτωση διπλωματικών με awaiting_committee
$topics_stmt = $db->prepare("
    SELECT id, title 
    FROM topics 
    WHERE assigned_to = ? AND status = 'awaiting_committee'
    ORDER BY id DESC
");
$topics_stmt->bind_param("i", $assigned_to);
$topics_stmt->execute();
$topics = $topics_stmt->get_result();

// Αν ο φοιτητής έστειλε προσκλήσεις
if (isset($_POST['topic_id']) && isset($_POST['teacher_ids'])) {
    $topic_id = intval($_POST['topic_id']);
    $teacher_ids = $_POST['teacher_ids']; // array

    foreach ($teacher_ids as $teacher_id) {
        $teacher_id = intval($teacher_id);

        // Έλεγχος αν υπάρχει ήδη η πρόσκληση
        $stmt = $db->prepare("SELECT id FROM committee_requests WHERE topic_id=? AND teacher_id=?");
        $stmt->bind_param("ii", $topic_id, $teacher_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();

        if (!$exists) {
            $status = "pending";
            $stmt = $db->prepare("INSERT INTO committee_requests (topic_id, teacher_id, status) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $topic_id, $teacher_id, $status);
            $stmt->execute();
        }
    }
}





?>




<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Διπλωματικής - Φοιτητής</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/StudentManageThesis.css">
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
            <a href="StudentDashboard.php">
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
            <a href="StudentManageThesis.php" class="active">
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

      <!-- Student Manage Thesis Content -->
      <div class="container">
        <header>
            <h1>Διαχείριση Διπλωματικής Εργασίας</h1>
        </header>
        <hr class="hr">

        <div class="thesis-status">
          <div class="status-card">
              <h3>Κατάσταση Διπλωματικής</h3>
              <div class="status-info">
                  <span class="status-label">Τίτλος:</span>
                  <span class="status-value"><?= htmlspecialchars($topic['title']) ?></span>
              </div>
              <div class="status-info">
                  <span class="status-label">Επιβλέπων:</span>
                  <span class="status-value"><?= htmlspecialchars($topic['supervisor_name']." ".$topic['supervisor_surname']) ?></span>
              </div>
              <div class="status-info">
                  <span class="status-label">Κατάσταση:</span>
                  <span class="status-badge <?= ($topic['status']=='confirmed'?'active':'pending') ?>">
                      <?= htmlspecialchars($topic['status']) ?>
                  </span>
              </div>
              <?php if (!empty($topic['deadline'])): ?>
              <div class="status-info">
                  <span class="status-label">Προθεσμία:</span>
                  <span class="status-value">
                    <?= date("d/m/Y", strtotime($topic['deadline'])) ?>
                  </span>
              </div>

              
              <?php endif; ?>
          </div>

          <div class="invite-teachers mt-4">
    <h3>Πρόσκληση Καθηγητών</h3>
    <form method="POST">
        <div class="mb-3">
            <label for="topic" class="form-label">Επιλέξτε Διπλωματική</label>
            <select name="topic_id" id="topic" class="form-select" required>
                <option value="">-- Επιλέξτε Διπλωματική --</option>
                <?php while ($t = $topics->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="teachers" class="form-label">Επιλέξτε Καθηγητές</label>
            <select name="teacher_ids[]" id="teachers" class="form-select" multiple required>
                <?php while ($t = $teachers->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']." ".$t['surname']) ?></option>
                <?php endwhile; ?>
            </select>
            <small class="form-text text-muted">Κρατήστε πατημένο Ctrl για πολλαπλή επιλογή</small>
        </div>

        <button type="submit" class="btn btn-primary">Αποστολή Προσκλήσεων</button>
    </form>
</div>

          

          <div class="committee-status mt-4">
    <h3>Κατάσταση Προσκλήσεων</h3>
    <?php 
    $stmt = $db->prepare("
        SELECT ci.status, u.name, u.surname, t.title 
        FROM committee_requests ci
        JOIN users u ON ci.teacher_id = u.id
        JOIN topics t ON ci.topic_id = t.id
        WHERE t.assigned_to = ?
    ");
    $stmt->bind_param("i", $assigned_to);
    $stmt->execute();
    $committee = $stmt->get_result();

    if ($committee->num_rows > 0): ?>
        <ul class="list-group">
            <?php while ($c = $committee->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($c['title']) ?></strong><br>
                        <?= htmlspecialchars($c['name']." ".$c['surname']) ?>
                    </div>
                    <span class="badge 
                        <?= $c['status']=='accepted'?'bg-success':($c['status']=='rejected'?'bg-danger':'bg-warning text-dark') ?>">
                        <?= htmlspecialchars($c['status']) ?>
                    </span>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Δεν έχουν σταλεί προσκλήσεις ακόμα.</p>
    <?php endif; ?>
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
