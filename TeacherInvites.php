<?php
session_start();
require 'config.php';

// Έλεγχος login καθηγητή
if (!isset($_SESSION["userid"]) || $_SESSION["role"] !== "teacher") {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

// --- Όνομα καθηγητή ---
$teacherName = "";
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
}

// ---------------- Αποδοχή ή Απόρριψη Πρόσκλησης ----------------
if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'] === 'accept' ? 'accepted' : 'cancelled';

    $stmt = $db->prepare("UPDATE committee_requests SET status=? WHERE id=? AND teacher_id=?");
    $stmt->bind_param("sii", $action, $request_id, $teacher_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "✅ Η πρόσκληση ενημερώθηκε σε: " . $action;
    } else {
        $message = "⚠️ Σφάλμα κατά την ενημέρωση.";
    }
}

// ---------------- Λίστα Προσκλήσεων ----------------
$stmt = $db->prepare("
    SELECT cr.id, cr.status, t.title, u.name, u.surname, cr.requested_at
    FROM committee_requests cr
    JOIN topics t ON cr.topic_id = t.id
    JOIN users u ON t.assigned_to = u.id
    WHERE cr.teacher_id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$requests = $stmt->get_result();

if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'] === 'accept' ? 'accepted' : 'cancelled';

    // Ενημέρωση της συγκεκριμένης πρόσκλησης
    $stmt = $db->prepare("UPDATE committee_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $request_id);
    $stmt->execute();

    if ($action === 'accepted') {
        // Βρες το topic_id αυτής της πρόσκλησης
        $stmt = $db->prepare("SELECT topic_id FROM committee_requests WHERE id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $topic_id = $stmt->get_result()->fetch_assoc()['topic_id'];

        // Μέτρησε πόσες προσκλήσεις είναι ήδη confirmed
        $stmt = $db->prepare("SELECT COUNT(*) AS accepted_count FROM committee_requests WHERE topic_id=? AND status='accepted'");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        $accepted_count = $stmt->get_result()->fetch_assoc()['accepted_count'];

        if ($accepted_count >= 2) {
            // Κάνε το θέμα confirmed
            $stmt = $db->prepare("UPDATE topics SET status='confirmed', confirmed_time=NOW() WHERE id=?");
            $stmt->bind_param("i", $topic_id);
            $stmt->execute();

            // Ακύρωσε τις υπόλοιπες pending προσκλήσεις
            $stmt = $db->prepare("
                    UPDATE committee_requests 
                    SET status='cancelled', responded_at=NOW() 
                    WHERE topic_id=? AND status='pending'
                ");       
            $stmt->bind_param("i", $topic_id);
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
    <title>Προσκλήσεις Τριμελών Επιτροπών</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/TeacherInvites.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='profile.php'">
        
        <!-- User name link -->
        <div class="user-name">
          <?= htmlspecialchars($teacherName) ?>
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="TeacherDashboard.php">
              <img src="icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherStats.php">
              <img src="icons/stats.png" alt="Statistics" class="nav-icon">
              Στατιστικα
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherCreateThesis.php">
              <img src="icons/file.png" alt="Thesis Topics" class="nav-icon">
              Θεματα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherThesisList.php">
              <img src="list.png" alt="Thesis List" class="nav-icon">
              Λιστα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherInvites.php" class="active">
              <img src="icons/invitation.png" alt="Invitations" class="nav-icon">
              Προσκλησεις
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="settings.php">
              <img src="icons/setting.png" alt="Settings" class="nav-icon">
              Ρυθμισεις
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

      <!-- Invitations Content -->
      <div class="container">
    <header class="mb-4">
      <h1><i class="fas fa-envelope-open-text"></i> Προσκλήσεις Τριμελών Επιτροπών</h1>
      <p class="subtitle">Διαχείριση προσκλήσεων συμμετοχής σε τριμελείς επιτροπές</p>
    </header>

    <?php if ($message): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="invitations-container">
      <?php if ($requests->num_rows > 0): ?>
        <?php while ($r = $requests->fetch_assoc()): ?>
          <div class="card mb-3 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5><?= htmlspecialchars($r['title']) ?></h5>
              <span class="badge 
                <?= $r['status']=='confirmed'?'bg-success':($r['status']=='cancelled'?'bg-danger':'bg-warning text-dark') ?>">
                <?= htmlspecialchars($r['status']) ?>
              </span>
            </div>
            <div class="card-body">
              <p><i class="fas fa-user"></i> Φοιτητής: <?= htmlspecialchars($r['name']." ".$r['surname']) ?></p>
              <p><i class="fas fa-calendar"></i> Ημερομηνία Πρόσκλησης: <?= htmlspecialchars($r['requested_at']) ?></p>
            </div>
            <?php if ($r['status'] == 'pending'): ?>
              <div class="card-footer">
                <form method="POST" class="d-inline">
                  <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="action" value="accept">
                  <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-check"></i> Αποδοχή
                  </button>
                </form>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-times"></i> Απόρριψη
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>Δεν υπάρχουν προσκλήσεις προς το παρόν.</p>
      <?php endif; ?>
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