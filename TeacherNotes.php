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

$stmt = $db->prepare("
    SELECT n.id, n.topic_id, n.note_text, n.created_at, t.title 
    FROM notes n
    JOIN topics t ON n.topic_id = t.id
    WHERE n.teacher_id = ?
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

$notes = [];
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note_id'])) {
    $note_id = intval($_POST['delete_note_id']);
    
    $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $note_id, $teacher_id);
    $stmt->execute();
    $stmt->close();

    // Μετάβαση για να φανούν οι αλλαγές (auto-refresh)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}



?>


<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Οι Σημειώσεις Μου</title>
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
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='TeacherProfile.php'">
        
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
            <a href="TeacherInvites.php">
              <img src="icons/invitation.png" alt="Invitations" class="nav-icon">
              Προσκλησεις
            </a>
          </li>
          <li class="nav-spacing" class="active">
            <a href="TeacherNotes.php">
              <img src="list.png" alt="Thesis List" class="nav-icon">
              Οι σημειώσεις μου
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="TeacherSettings.php">
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


    <div class="col py-3">
      <div class="container">
        <header>
          <h1>Οι Σημειώσεις μου</h1>
        </header>
        <hr class="hr">

    
   
      <?php if(empty($notes)): ?>
          <p>Δεν υπάρχουν σημειώσεις.</p>
      <?php else: ?>
        <?php foreach($notes as $note): ?>
              <div class="card mb-3 shadow-sm">
                <div class="card-header">
                    <strong>Σημείωση για Θέμα: <?= htmlspecialchars($note['title']) ?></strong>
                    <small class="text-muted float-end"><?= htmlspecialchars($note['created_at']) ?></small>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                  <div class="card-header">
                      <p><?= nl2br(htmlspecialchars($note['note_text'])) ?></p>
                  </div>

                  <form method="POST" onsubmit="return confirm('Είσαι σίγουρος/η ότι θέλεις να διαγράψεις αυτή τη σημείωση;');">
                      <input type="hidden" name="delete_note_id" value="<?= $note['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">
                          <i class="fas fa-trash"></i> 
                      </button>
                  </form>
                </div>
              </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>
    </body>
</html> 