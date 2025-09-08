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


$stmt = $db->prepare("
    SELECT t.id, t.title, t.summary, t.pdf_path
    FROM topics t
    WHERE t.assigned_to = ? AND t.status = 'confirmed'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$topicsResult = $stmt->get_result();

$stmt = $db->prepare("
    SELECT t.id, t.title, t.summary
    FROM topics t
    WHERE t.assigned_to = ? AND t.status = 'confirmed'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$topicsResult = $stmt->get_result();

?>



<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Θέματα Διπλωματικών - Φοιτητής</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/StudentTopics.css">
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
            <a href="StudentTopics.php" class="active">
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

    <!-- Main Content -->
    <div class="col py-3">
      <!-- Mobile toggle button -->
      <button class="mobile-menu-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <i class="fas fa-bars"></i> Μενού
      </button>

      <!-- Student Topics Content -->
      <div class="container">
        <header>
            <h1>Ανάρτηση Αρχείων ΔΕ</h1>
        </header>
        <hr class="hr">

<div class="topics-container">
    <?php while($row = $topicsResult->fetch_assoc()): ?>
        <div class="topic-card">
            <h3>Θέμα: <?= htmlspecialchars($row['title']) ?></h3>
            
            <?php if(!empty($row['pdf_path'])): ?>
                <a href="<?= htmlspecialchars($row['pdf_path']) ?>" target="_blank" class="btn btn-sm btn-primary">Προβολή PDF</a>
            <?php endif; ?>

            <!-- Φόρμα upload με textarea για σχόλια -->
            <form action="upload_submission.php" method="POST" enctype="multipart/form-data" class="mt-2">
                <input type="hidden" name="topic_id" value="<?= $row['id'] ?>">

                <div class="mb-2">
                    <label for="comments_<?= $row['id'] ?>" class="form-label">Σχόλια:</label>
                    <textarea name="comments" id="comments_<?= $row['id'] ?>" class="form-control" rows="3" placeholder="Γράψε εδώ τα σχόλιά σου..."></textarea>
                </div>

                <div class="mb-2">
                    <input type="file" name="submission_file" accept=".pdf,.doc,.docx">
                </div>

                <button type="submit" class="btn btn-sm btn-success mt-1">Ανέβασμα αρχείου</button>
            </form>

            

        </div>
    <?php endwhile; ?>
</div>


<div class="topic-submissions mt-2">
    <h5>Προηγούμενες υποβολές:</h5>
    <?php
    $stmt2 = $db->prepare("
        SELECT s.file_path, s.uploaded_at, t.title, s.comments
        FROM student_submissions s
        INNER JOIN topics t ON s.topic_id = t.id
        WHERE s.student_id = ? AND t.status = 'confirmed'
        ORDER BY s.uploaded_at DESC
    ");
    $stmt2->bind_param("i", $student_id);
    $stmt2->execute();
    $submissionsResult = $stmt2->get_result();

    if($submissionsResult->num_rows > 0){
        echo "<ul>";
        while($sub = $submissionsResult->fetch_assoc()){
            echo "<li>";
            echo "<strong>" . htmlspecialchars($sub['title']) . ":</strong> ";
            if(!empty($sub['file_path'])){
                echo '<a href="'.htmlspecialchars($sub['file_path']).'" target="_blank">'.basename($sub['file_path']).'</a> - ';
            }
            echo htmlspecialchars($sub['comments']) ?: "<em>Δεν υπάρχουν σχόλια.</em>";
            echo " <small>(" . date("d/m/Y H:i", strtotime($sub['uploaded_at'])) . ")</small>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Δεν έχεις κάνει ακόμα υποβολές για αυτό το θέμα.</p>";
    }
    ?>
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
