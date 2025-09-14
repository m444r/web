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
    <style>
        .alert-top {
            position: fixed;
            top: 20px;
            left: calc(16.66667% + (83.33333% / 2));
            transform: translateX(-50%);
            z-index: 1050;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        .section-title {
            color: #2c3e50;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #6A90C7;
            position: relative;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #6A90C7, #4a69a8);
            border-radius: 2px;
        }
        
        .invitations-section {
            margin-bottom: 3rem;
        }
        
        .invitation-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .invitation-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .invitation-card.accepted {
            background: linear-gradient(135deg, #ffffff, #f8fff8);
            border-left: 5px solid #28a745;
            box-shadow: 0 4px 20px rgba(40, 167, 69, 0.15);
        }
        
        .invitation-card.accepted:hover {
            box-shadow: 0 8px 30px rgba(40, 167, 69, 0.2);
        }
        
        .invitation-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .invitation-card.accepted .invitation-header {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
        }
        
        .invitation-header h3 {
            color: #2c3e50;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            flex: 1;
        }
        
        .status {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status.pending {
            background: linear-gradient(135deg, #ffc107, #ff8c00);
            color: white;
        }
        
        .status.accepted {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .invitation-details {
            padding: 1.5rem;
        }
        
        .invitation-details p {
            margin-bottom: 0.75rem;
            color: #495057;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .invitation-details i {
            color: #6A90C7;
            width: 16px;
            text-align: center;
        }
        
        .invitation-actions {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 1rem;
        }
        
        .invitation-card.accepted .invitation-actions {
            background: #e8f5e8;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn.accept {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }
        
        .btn.accept:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        
        .btn.reject {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        }
        
        .btn.reject:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        
        .no-invitations {
            text-align: center;
            padding: 3rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 16px;
            border: 2px dashed #dee2e6;
        }
        
        .no-invitations p {
            color: #6c757d;
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Animation for cards appearing */
        .invitation-card {
            animation: slideInUp 0.6s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Staggered animation for multiple cards */
        .invitation-card:nth-child(1) { animation-delay: 0.1s; }
        .invitation-card:nth-child(2) { animation-delay: 0.2s; }
        .invitation-card:nth-child(3) { animation-delay: 0.3s; }
        .invitation-card:nth-child(4) { animation-delay: 0.4s; }
        .invitation-card:nth-child(5) { animation-delay: 0.5s; }
        
        /* Loading state for buttons */
        .btn:active {
            transform: translateY(0) scale(0.98);
        }
        
        /* Enhanced mobile responsiveness */
        @media (max-width: 768px) {
            .alert-top {
                left: 50%;
            }
            
            .section-title {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .invitation-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .invitation-header h3 {
                font-size: 1.1rem;
            }
            
            .invitation-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .invitation-details p {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .invitation-card {
                margin-bottom: 1rem;
            }
            
            .invitation-header,
            .invitation-details,
            .invitation-actions {
                padding: 1rem;
            }
            
            .section-title {
                font-size: 1.3rem;
            }
        }
    </style>
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
            <div class="alert alert-info alert-top"><?= htmlspecialchars($message) ?></div>
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
                        <?php foreach ($pending_invitations as $invitation): ?>
                            <div class="invitation-card">
                                <div class="invitation-header">
                                    <h3><?= htmlspecialchars($invitation['title']) ?></h3>
                                    <span class="status pending">Σε εκκρεμότητα</span>
                                </div>
                                <div class="invitation-details">
                                    <p><i class="fas fa-user"></i> Φοιτητής: <?= htmlspecialchars($invitation['student_name'] . ' ' . $invitation['student_surname']) ?> (ΑΜ: <?= htmlspecialchars($invitation['student_am']) ?>)</p>
                                    <p><i class="fas fa-calendar"></i> Ημερομηνία πρόσκλησης: <?= date('d/m/Y', strtotime($invitation['requested_at'])) ?></p>
                                </div>
                                <div class="invitation-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn accept"><i class="fas fa-check"></i> Αποδοχή</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn reject"><i class="fas fa-times"></i> Απόρριψη</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Accepted Invitations Section -->
                <?php if (!empty($accepted_invitations)): ?>
                    <div class="invitations-section">
                        <h2 class="section-title">Αποδεκτές Προσκλήσεις</h2>
                        <?php foreach ($accepted_invitations as $invitation): ?>
                            <div class="invitation-card accepted">
                                <div class="invitation-header">
                                    <h3><?= htmlspecialchars($invitation['title']) ?></h3>
                                    <span class="status accepted">Αποδεκτή</span>
                                </div>
                                <div class="invitation-details">
                                    <p><i class="fas fa-user"></i> Φοιτητής: <?= htmlspecialchars($invitation['student_name'] . ' ' . $invitation['student_surname']) ?> (ΑΜ: <?= htmlspecialchars($invitation['student_am']) ?>)</p>
                                    <p><i class="fas fa-calendar"></i> Ημερομηνία πρόσκλησης: <?= date('d/m/Y', strtotime($invitation['requested_at'])) ?></p>
                                    <p><i class="fas fa-check-circle"></i> Αποδεχθήκατε: <?= date('d/m/Y', strtotime($invitation['responded_at'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
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