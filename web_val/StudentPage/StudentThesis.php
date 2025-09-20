<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$student_id = $_SESSION["userid"];
$message = "";

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
$stmt->close();


// Fetch detailed thesis information with supervisor and status
$sql = "SELECT t.*, u.name AS teacher_name, u.surname AS teacher_surname, u.email AS teacher_email
        FROM topics t
        JOIN users u ON u.id = t.teacher_id
        WHERE t.assigned_to = ? AND t.status IN ('awaiting_committee', 'confirmed', 'for examination', 'for_grade', 'completed')
        ORDER BY t.id DESC";

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
    <link rel="stylesheet" href="../css/StudentThesis.css">
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
            <a href="StudentDashboard.php" >
              <img src="../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-item nav-spacing">
            <a href="StudentThesis.php" class="active">
              <img src="../icons/thesis.png" alt="Thesis" class="nav-icon">
              Διπλωματική Εργασία
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentInvites.php">
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
          
          <div class="nav-separator"></div>
          
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

    <div class="col py-3">
      <!-- Mobile toggle button -->
      <button class="mobile-menu-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <i class="fas fa-bars"></i> Μενού
      </button>
<!-- Main content -->
    <div class="container">
      <header>
        <h1>Η διπλωματική μου</h1>
      </header>
      <hr class="hr">
      
      <?php if ($topics->num_rows > 0): ?>
        <?php while ($row = $topics->fetch_assoc()): ?>
            <div class="thesis-card">
                <?php
                // Map status to display information
                $status_info = [
                    'awaiting_committee' => ['title' => 'Υπό Ανάθεση - Επιλογή Επιτροπής', 'class' => 'warning', 'icon' => 'fas fa-users'],
                    'confirmed' => ['title' => 'Ενεργή Διπλωματική', 'class' => 'active', 'icon' => 'fas fa-play'],
                    'for examination' => ['title' => 'Υπό Εξέταση', 'class' => 'info', 'icon' => 'fas fa-search'],
                    'for_grade' => ['title' => 'Υπό Βαθμολόγηση', 'class' => 'info', 'icon' => 'fas fa-chart-line'],
                    'completed' => ['title' => 'Περατωμένη Διπλωματική', 'class' => 'success', 'icon' => 'fas fa-check-circle']
                ];
                $current_status = $status_info[$row['status']] ?? ['title' => 'Διπλωματική Εργασία', 'class' => 'pending', 'icon' => 'fas fa-file'];

                // Fetch committee members (supervisor + first 2 accepted invitations)
                $committee_sql = "(
                                    SELECT u.id, u.name, u.surname, u.email, 'supervisor' as role, 0 as priority
                                    FROM users u 
                                    WHERE u.id = ?
                                ) UNION ALL (
                                    SELECT u.id, u.name, u.surname, u.email, 'committee' as role, cr.id as priority
                                    FROM committee_requests cr
                                    JOIN users u ON u.id = cr.teacher_id
                                    WHERE cr.topic_id = ? AND cr.status = 'accepted'
                                    ORDER BY cr.id ASC
                                    LIMIT 2
                                ) ORDER BY priority ASC";
                $cstmt = $db->prepare($committee_sql);
                $cstmt->bind_param("ii", $row["teacher_id"], $row["id"]);
                $cstmt->execute();
                $committee = $cstmt->get_result();
                $cstmt->close();

                // Calculate time elapsed since assignment
                $time_elapsed = '';
                if (!empty($row['assigned_time'])) {
                    $assigned_date = new DateTime($row['assigned_time']);
                    $now = new DateTime();
                    $interval = $assigned_date->diff($now);
                    
                    if ($interval->days > 0) {
                        $time_elapsed = $interval->days . ' ημέρες';
                    } elseif ($interval->h > 0) {
                        $time_elapsed = $interval->h . ' ώρες';
                    } else {
                        $time_elapsed = $interval->i . ' λεπτά';
                    }
                }
              ?>

              <!-- Thesis Header -->
              <div class="thesis-header">
                <h3><?= htmlspecialchars($row["title"]) ?></h3>
                <span class="status-badge <?= $current_status['class'] ?>">
                  <i class="<?= $current_status['icon'] ?>"></i>
                  <?= $current_status['title'] ?>
                </span>
              </div>

              <!-- Thesis Details -->
              <div class="thesis-details">
                <div class="detail-section">
                  <h4><i class="fas fa-info-circle"></i> Λεπτομέρειες Διπλωματικής</h4>
                  <p><strong>Περιγραφή:</strong> <?= htmlspecialchars($row["summary"]) ?></p>
                  
                  <?php if (!empty($row['pdf_path'])): ?>
                    <p><strong>Αρχείο Περιγραφής:</strong></p>
                    <a href="<?= htmlspecialchars($row['pdf_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-file-pdf"></i> Προβολή PDF
                    </a>
                  <?php else: ?>
                    <p><strong>Αρχείο Περιγραφής:</strong> <span class="text-muted">Δεν έχει ανέβει αρχείο</span></p>
                  <?php endif; ?>
                  <p><strong>Τελικός Βαθμός:</strong> <?= htmlspecialchars($row["final_grade"]) ?></p>

                </div>

                <div class="detail-section">
                  <h4><i class="fas fa-user-tie"></i> Επιβλέπων Καθηγητής</h4>
                  <p><strong>Ονοματεπώνυμο:</strong> <?= htmlspecialchars($row["teacher_name"] . " " . $row["teacher_surname"]) ?></p>
                  <p><strong>Email:</strong> <?= htmlspecialchars($row["teacher_email"]) ?></p>
                </div>

                <div class="detail-section">
                  <h4><i class="fas fa-users"></i> Τριμελής Επιτροπή</h4>
                  <?php if ($committee->num_rows > 0): ?>
                    <div class="committee-members">
                      <?php while ($member = $committee->fetch_assoc()): ?>
                        <div class="committee-member">
                          <?php if ($member['role'] === 'supervisor'): ?>
                            <i class="fas fa-user-tie"></i>
                            <div class="member-info">
                              <strong><?= htmlspecialchars($member['name'] . ' ' . $member['surname']) ?></strong>
                              <small class="text-muted">(<?= htmlspecialchars($member['email']) ?>)</small>
                              <span class="role-badge supervisor">Επιβλέπων</span>
                            </div>
                          <?php else: ?>
                            <i class="fas fa-user"></i>
                            <div class="member-info">
                              <strong><?= htmlspecialchars($member['name'] . ' ' . $member['surname']) ?></strong>
                              <small class="text-muted">(<?= htmlspecialchars($member['email']) ?>)</small>
                              <span class="role-badge committee">Μέλος Επιτροπής</span>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endwhile; ?>
                    </div>
                  <?php else: ?>
                    <p class="text-muted">Δεν έχουν οριστεί ακόμα μέλη της επιτροπής</p>
                  <?php endif; ?>
                </div>

                <div class="detail-section">
                  <h4><i class="fas fa-clock"></i> Χρονολόγιο</h4>
                  <?php if (!empty($row['assigned_time'])): ?>
                    <p><strong>Ημερομηνία Ανάθεσης:</strong> <?= date('d/m/Y H:i', strtotime($row['assigned_time'])) ?></p>
                    <?php if (!empty($time_elapsed)): ?>
                      <p><strong>Χρόνος που έχει περάσει:</strong> <span class="badge bg-info"><?= $time_elapsed ?></span></p>
                    <?php endif; ?>
                  <?php endif; ?>
                  
                  <?php if (!empty($row['confirmed_time'])): ?>
                    <p><strong>Ημερομηνία Επιβεβαίωσης:</strong> <?= date('d/m/Y H:i', strtotime($row['confirmed_time'])) ?></p>
                  <?php endif; ?>
                  
                  <?php if (!empty($row['deadline'])): ?>
                    <p><strong>Προθεσμία Υποβολής:</strong> <?= date('d/m/Y', strtotime($row['deadline'])) ?></p>
                  <?php endif; ?>
                </div>
              </div>

            </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-thesis-card">
          <div class="no-thesis-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h4>Δεν έχετε διπλωματική εργασία</h4>
          <p>Επικοινωνήστε με τον καθηγητή σας για ανάθεση θέματος.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
    </body>
</html>