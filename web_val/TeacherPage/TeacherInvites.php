<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

// Handle invitation responses
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && isset($_POST['invitation_id'])) {
        $action = $_POST['action'];
        $invitation_id = $_POST['invitation_id'];
        
        if ($action === 'accept') {
            $stmt = $db->prepare("UPDATE committee_requests SET status = 'accepted', responded_at = NOW() WHERE id = ? AND teacher_id = ?");
            $stmt->bind_param("ii", $invitation_id, $teacher_id);
            if ($stmt->execute()) {
                $message = "Αποδεχθήκατε την πρόσκληση.";
                
                // Get the topic_id for this invitation
                $stmt = $db->prepare("SELECT topic_id FROM committee_requests WHERE id = ?");
                $stmt->bind_param("i", $invitation_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $topic_id = $result['topic_id'];
                
                // Check if we now have 2 accepted invitations for this topic
                $stmt = $db->prepare("SELECT COUNT(*) as accepted_count FROM committee_requests WHERE topic_id = ? AND status = 'accepted'");
                $stmt->bind_param("i", $topic_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['accepted_count'] >= 2) {
                    // Update topic status to 'confirmed'
                    $stmt = $db->prepare("UPDATE topics SET status = 'confirmed', confirmed_time = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $topic_id);
                    $stmt->execute();
                    
                    // Cancel all remaining pending invitations for this topic
                    $stmt = $db->prepare("UPDATE committee_requests SET status = 'cancelled' WHERE topic_id = ? AND status = 'pending'");
                    $stmt->bind_param("i", $topic_id);
                    $stmt->execute();
                    
                    $message = "Αποδεχθήκατε την πρόσκληση. Η διπλωματική εργασία είναι πλέον ενεργή! Ακυρώθηκαν οι υπόλοιπες προσκλήσεις.";
                }
            } else {
                $message = "Σφάλμα κατά την αποδοχή.";
            }
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE committee_requests SET status = 'rejected', responded_at = NOW() WHERE id = ? AND teacher_id = ?");
            $stmt->bind_param("ii", $invitation_id, $teacher_id);
            if ($stmt->execute()) {
                $message = "Απερρίψατε την πρόσκληση.";
            } else {
                $message = "Σφάλμα κατά την απόρριψη.";
            }
        }
    }
}

// Get teacher name
$teacherName = "";
$teacherProfilePicture = "../icons/account.png"; // Default profile picture

// Check if profile_picture column exists
$check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column->num_rows > 0;

if ($has_profile_picture) {
    $stmt = $db->prepare("SELECT name, surname, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
}

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
    if ($has_profile_picture) {
        $teacherProfilePicture = (!empty($row['profile_picture'])) ? "../" . $row['profile_picture'] : "../icons/account.png";
    }
}

// Get all invitations for this teacher (pending and accepted)
$invitations = [];
$stmt = $db->prepare("
    SELECT cr.*, t.title, u.name as student_name, u.surname as student_surname, u.am as student_am
    FROM committee_requests cr
    JOIN topics t ON t.id = cr.topic_id
    JOIN users u ON u.id = t.assigned_to
    WHERE cr.teacher_id = ? AND cr.status IN ('pending', 'accepted')
    ORDER BY cr.status ASC, cr.requested_at DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $invitations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσκλήσεις Τριμελών Επιτροπών</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/TeacherInvites.css?v=<?php echo time(); ?>">
    
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="<?= htmlspecialchars($teacherProfilePicture) ?>" alt="Profile" class="profile-avatar" onclick="window.location.href='TeacherProfile.php'">
        
        <!-- User name link -->
        <div class="user-name">
          <?= htmlspecialchars($teacherName) ?>
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="TeacherDashboard.php">
              <img src="../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherStats.php">
              <img src="../icons/stats.png" alt="Statistics" class="nav-icon">
              Στατιστικα
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherCreateThesis.php">
              <img src="../icons/file.png" alt="Thesis Topics" class="nav-icon">
              Θεματα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherThesisList.php">
              <img src="../list.png" alt="Thesis List" class="nav-icon">
              Λιστα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherNotes.php">
              <img src="../icons/wirte.png" alt="Notes" class="nav-icon">
              Σημειωσεις
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherInvites.php" class="active">
              <img src="../icons/invitation.png" alt="Invitations" class="nav-icon">
              Προσκλησεις
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="TeacherProfile.php">
              <img src="../icons/setting.png" alt="Settings" class="nav-icon">
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

      <!-- Invitations Content -->
      <div class="container">
        <header>
            <h1>Προσκλήσεις Τριμελών Επιτροπών</h1>
        </header>
        <hr class="hr">

        <?php if (!empty($message)): ?>
            <?php 
            // Determine alert type based on message content
            $alertClass = 'alert-info'; // Default to info (blue)
            if (strpos($message, 'Σφάλμα') !== false) {
                $alertClass = 'alert-danger'; // Red for errors
            } elseif (strpos($message, 'Αποδεχθήκατε') !== false || strpos($message, 'Απερρίψατε') !== false) {
                $alertClass = 'alert-success'; // Blue for success
            }
            ?>
            <div class="alert <?= $alertClass ?> alert-top"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="invitations-container">
            <?php if (empty($invitations)): ?>
                <div class="no-invitations">
                    <p>Δεν έχετε προσκλήσεις σε τριμελείς επιτροπές.</p>
                </div>
            <?php else: ?>
                <?php 
                // Separate invitations by status
                $pending_invitations = array_filter($invitations, function($inv) { return $inv['status'] === 'pending'; });
                $accepted_invitations = array_filter($invitations, function($inv) { return $inv['status'] === 'accepted'; });
                ?>
                
                <!-- Pending Invitations Section -->
                <?php if (!empty($pending_invitations)): ?>
                    <div class="invitations-section">
                        <h2 class="section-title">Προσκλήσεις σε Εκκρεμότητα</h2>
                        <div class="invitations-grid">
                            <?php foreach ($pending_invitations as $invitation): ?>
                                <div class="invitation-card pending">
                                    <div class="card-header">
                                        <h3 class="card-title"><?= htmlspecialchars($invitation['title']) ?></h3>
                                        <span class="status pending">Σε εκκρεμότητα</span>
                                    </div>
                                    <div class="card-details">
                                        <p><i class="fas fa-user"></i> Φοιτητής: <?= htmlspecialchars($invitation['student_name'] . ' ' . $invitation['student_surname']) ?> (ΑΜ: <?= htmlspecialchars($invitation['student_am']) ?>)</p>
                                        <p><i class="fas fa-calendar"></i> Ημερομηνία πρόσκλησης: <?= date('d/m/Y', strtotime($invitation['requested_at'])) ?></p>
                                    </div>
                                    <div class="card-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn accept">Αποδοχή</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn reject">Απόρριψη</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="topics-pagination">
                            <button class="pagination-btn" onclick="goToPage(1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="pagination-info">1/1</span>
                            <button class="pagination-btn" onclick="goToPage(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Accepted Invitations Section -->
                <?php if (!empty($accepted_invitations)): ?>
                    <div class="invitations-section">
                        <h2 class="section-title">Αποδεκτές Προσκλήσεις</h2>
                        <div class="invitations-grid">
                            <?php foreach ($accepted_invitations as $invitation): ?>
                                <div class="invitation-card accepted">
                                    <div class="card-header">
                                        <h3 class="card-title"><?= htmlspecialchars($invitation['title']) ?></h3>
                                        <span class="status accepted">Αποδεκτή</span>
                                    </div>
                                    <div class="card-details">
                                        <p><i class="fas fa-user"></i> Φοιτητής: <?= htmlspecialchars($invitation['student_name'] . ' ' . $invitation['student_surname']) ?> (ΑΜ: <?= htmlspecialchars($invitation['student_am']) ?>)</p>
                                        <p><i class="fas fa-calendar"></i> Ημερομηνία πρόσκλησης: <?= date('d/m/Y', strtotime($invitation['requested_at'])) ?></p>
                                        <p><i class="fas fa-check-circle"></i> Αποδεχθήκατε: <?= date('d/m/Y', strtotime($invitation['responded_at'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="topics-pagination">
                            <button class="pagination-btn" onclick="goToPage(1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="pagination-info">1/1</span>
                            <button class="pagination-btn" onclick="goToPage(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
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
    
    // Auto-hide alert messages after 3 seconds
    const alertMessages = document.querySelectorAll('.alert');
    alertMessages.forEach(alert => {
      setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
          alert.remove();
        }, 500);
      }, 3000);
    });
  });
</script>
</body>
</html> 