<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../register.php");
    exit;
}

$student_id = $_SESSION["userid"];
$message = "";

// Handle committee invitation form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['teacher_ids']) && isset($_POST['topic_id'])) {
    $topic_id = intval($_POST['topic_id']);
    $teacher_ids = $_POST['teacher_ids'];
    
    // Validate that at least 2 teachers are selected
    if (count($teacher_ids) < 2) {
        $message = "Παρακαλώ επιλέξτε τουλάχιστον 2 καθηγητές για την επιτροπή.";
    } else {
        // Check if the topic belongs to this student
        $stmt = $db->prepare("SELECT id FROM topics WHERE id = ? AND assigned_to = ? AND status = 'awaiting_committee'");
        $stmt->bind_param("ii", $topic_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Clear existing invitations for this topic
            $stmt = $db->prepare("DELETE FROM committee_requests WHERE topic_id = ?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();
            
            // Insert new invitations
            $success_count = 0;
            foreach ($teacher_ids as $teacher_id) {
                $stmt = $db->prepare("INSERT INTO committee_requests (topic_id, teacher_id, status, requested_at) VALUES (?, ?, 'pending', NOW())");
                $stmt->bind_param("ii", $topic_id, $teacher_id);
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
            
            if ($success_count > 0) {
                $message = "✅ Επιτυχής αποστολή! Στάλθηκαν προσκλήσεις σε " . $success_count . " καθηγητές για την επιτροπή.";
            } else {
                $message = "❌ Σφάλμα κατά την αποστολή των προσκλήσεων.";
            }
        } else {
            $message = "❌ Δεν έχετε δικαίωμα να στείλετε προσκλήσεις για αυτή τη διπλωματική.";
        }
    }
}

$studentName = "";
$studentProfilePicture = "../icons/account.png"; // Default profile picture

// Check if profile_picture column exists
$check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column->num_rows > 0;

if ($has_profile_picture) {
    $stmt = $db->prepare("SELECT name, surname, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $studentName = $row['name'] . " " . $row['surname'];
    if ($has_profile_picture) {
        $studentProfilePicture = (!empty($row['profile_picture'])) ? "../" . $row['profile_picture'] : "../icons/account.png";
    }
}


// Fetch all thesis details with supervisor and status information
$stmt = $db->prepare("
    SELECT t.id, t.title, t.summary, t.pdf_path, t.status, t.assigned_time, t.confirmed_time, t.deadline,
           u.name AS supervisor_name, u.surname AS supervisor_surname, u.email AS supervisor_email
    FROM topics t
    JOIN users u ON t.teacher_id = u.id
    WHERE t.assigned_to = ? AND t.status IN ('awaiting_committee', 'confirmed', 'for examination', 'for_grade', 'completed')
    ORDER BY t.id DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$topicsResult = $stmt->get_result();

// Convert result to array for committee filtering
$topics = [];
while ($row = $topicsResult->fetch_assoc()) {
    $topics[] = $row;
}

// Reset the result pointer for the while loop later
$topicsResult->data_seek(0);

?>



<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσκλήσεις - Φοιτητής</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/StudentInvites.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="<?= htmlspecialchars($studentProfilePicture) ?>" alt="Profile" class="profile-avatar" onclick="window.location.href='StudentProfile.php'">
        
        <!-- User name link -->
        <div class="user-name">
          <?= htmlspecialchars($studentName) ?>
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="StudentDashboard.php">
              <img src="../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentThesis.php">
              <img src="../icons/thesis.png" alt="Thesis" class="nav-icon">
              Διπλωματική Εργασία
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentInvites.php" class="active">
              <img src="../icons/invitation.png" alt="Invitations" class="nav-icon">
              Προσκλήσεις
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentManageThesis.php">
              <img src="../icons/project.png" alt="Manage Thesis" class="nav-icon">
              Διαχείριση ΔΕ
            </a>
          </li>
          
          <li class="nav-separator"></li>
          
          <li class="nav-spacing">
            <a href="StudentProfile.php">
              <img src="../icons/setting.png" alt="Profile" class="nav-icon">
              Προφίλ
            </a>
          </li>
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
      <!-- Mobile toggle button -->
      <button class="mobile-menu-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <i class="fas fa-bars"></i> Μενού
      </button>

      <!-- Student Topics Content -->
      <div class="container">
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?= strpos($message, '✅') !== false ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= strpos($message, '✅') !== false ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars(str_replace('✅ ', '', $message)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <header>
            <h1>Προσκλήσεις Επιτροπής</h1>
        </header>
        <hr class="hr">

        <!-- Committee Invitations Section -->
        <?php 
        $awaiting_committee_topics = array_filter($topics, function($topic) {
            return $topic['status'] == 'awaiting_committee';
        });
        if (!empty($awaiting_committee_topics)): ?>
        <div class="thesis-section" id="committee-section">
            <div class="status-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Επιλογή Επιτροπής</h3>
                    <p class="text-muted">Επιλέξτε καθηγητές για να σχηματίσουν την τριμελή επιτροπή εξέτασης.</p>
                </div>
                <form method="POST" class="invitation-form">
                    <div class="form-group">
                        <label for="topic" class="form-label">
                            <i class="fas fa-graduation-cap"></i> Επιλέξτε Διπλωματική
                        </label>
                        <select name="topic_id" id="topic" class="form-select" required>
                            <option value="">-- Επιλέξτε Διπλωματική --</option>
                            <?php foreach ($awaiting_committee_topics as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-chalkboard-teacher"></i> Επιλέξτε Καθηγητές (2 απαιτούνται)
                        </label>
                        <div class="teachers-grid">
                            <?php 
                            // Reset the teachers query result
                            $teachers = $db->query("SELECT id, name, surname FROM users WHERE role='teacher' ORDER BY name, surname");
                            while ($t = $teachers->fetch_assoc()): ?>
                                <div class="teacher-option">
                                    <input type="checkbox" name="teacher_ids[]" value="<?= $t['id'] ?>" id="teacher_<?= $t['id'] ?>" class="teacher-checkbox">
                                    <label for="teacher_<?= $t['id'] ?>" class="teacher-label">
                                        <span class="teacher-name"><?= htmlspecialchars($t['name']." ".$t['surname']) ?></span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Επιλέξτε τουλάχιστον 2 καθηγητές για την επιτροπή.
                        </small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-paper-plane"></i> Αποστολή Προσκλήσεων
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Invitation Status Section -->
        <div class="thesis-section">
            <div class="status-card">
                <div class="card-header">
                    <h3><i class="fas fa-clipboard-list"></i> Κατάσταση Προσκλήσεων</h3>
                </div>
                <?php 
                $stmt = $db->prepare("
                    SELECT ci.status, u.name, u.surname, t.title, ci.accepted_at
                    FROM committee_requests ci
                    JOIN users u ON ci.teacher_id = u.id
                    JOIN topics t ON ci.topic_id = t.id
                    WHERE t.assigned_to = ? AND ci.status = 'accepted'
                    ORDER BY ci.accepted_at ASC
                ");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $committee = $stmt->get_result();

                if ($committee->num_rows > 0): ?>
                     <div class="committee-list">
                          <?php while ($c = $committee->fetch_assoc()): ?>
                            <div class="committee-card">
                              <div class="committee-info">
                                <span class="committee-title"><?= htmlspecialchars($c['title']) ?></span>
                                <span class="committee-teacher"><?= htmlspecialchars($c['name']." ".$c['surname']) ?></span>
                                <?php if (!empty($c['accepted_at'])): ?>
                                  <span class="committee-date">Αποδεκτή: <?= date("d/m/Y H:i", strtotime($c['accepted_at'])) ?></span>
                                <?php endif; ?>
                              </div>
                              <span class="status-badge active">
                                <i class="fas fa-check-circle"></i> Αποδεκτή
                              </span>
                            </div>
                          <?php endwhile; ?>
                        </div>
                      <?php else: ?>
                        <div class="empty-state">
                          <i class="fas fa-users"></i>
                          <h4>Δεν υπάρχουν αποδεκτές προσκλήσεις</h4>
                          <p>Οι προσκλήσεις που έχουν αποδεχθεί θα εμφανιστούν εδώ.</p>
                        </div>
                      <?php endif; ?>
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

  // Teacher selection validation
  const checkboxes = document.querySelectorAll('.teacher-checkbox');
  const submitBtn = document.getElementById('submit-btn');
  const form = document.querySelector('.invitation-form');
  
  function updateSubmitButton() {
      const checkedCount = document.querySelectorAll('.teacher-checkbox:checked').length;
      
      if (checkedCount >= 2) {
          submitBtn.disabled = false;
          submitBtn.style.opacity = '1';
          submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Αποστολή Προσκλήσεων (' + checkedCount + ' καθηγητές)';
      } else {
          submitBtn.disabled = true;
          submitBtn.style.opacity = '0.6';
          if (checkedCount === 0) {
              submitBtn.innerHTML = '<i class="fas fa-info-circle"></i> Επιλέξτε τουλάχιστον 2 καθηγητές';
          } else {
              submitBtn.innerHTML = '<i class="fas fa-info-circle"></i> Επιλέξτε τουλάχιστον 2 καθηγητές (' + checkedCount + '/2)';
          }
      }
  }
  
  // Add event listeners to all checkboxes
  checkboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function() {
          updateSubmitButton();
      });
  });
  
  // Form submission validation
  if (form) {
      form.addEventListener('submit', function(e) {
          const checkedCount = document.querySelectorAll('.teacher-checkbox:checked').length;
          
          if (checkedCount < 2) {
              e.preventDefault();
              alert('Παρακαλώ επιλέξτε τουλάχιστον 2 καθηγητές για την επιτροπή. Επιλέξατε ' + checkedCount + ' καθηγητές.');
              return false;
          }
      });
  }
  
  // Initialize button state
  updateSubmitButton();

  // Auto-hide alert messages after 3 seconds
  const alert = document.querySelector('.alert');
  if (alert) {
      setTimeout(function() {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-20px)';
          setTimeout(function() {
              alert.remove();
          }, 300);
      }, 3000);
  }
</script>
</body>
</html>
