<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

// ------------------ Insert Νέου Θέματος ------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $summary = $_POST['description']; // textarea από τη φόρμα

    $pdf_path = null;

    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = uniqid() . "-" . basename($_FILES["pdf_file"]["name"]);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_file)) {
            $pdf_path = $target_file;
        } else {
            $message = "⚠️ Αποτυχία στην αποθήκευση του αρχείου PDF.";
        }
    }

    $stmt = $db->prepare("INSERT INTO topics (title, summary, pdf_path, teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $summary, $pdf_path, $teacher_id);

    if ($stmt->execute()) {
        $message = "✅ Το θέμα καταχωρήθηκε με επιτυχία!";
    } else {
        $message = "❌ Σφάλμα κατά την καταχώρηση.";
    }
}

// ------------------ Εμφάνιση Ονόματος Καθηγητή ------------------
$teacherName = "";
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
}

// ------------------ Φέρνουμε τα θέματα του καθηγητή ------------------
$stmt = $db->prepare("SELECT * FROM topics WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$topicsResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Θεμάτων Διπλωματικών</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/TeacherCreateThesis.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='profile.php'">
        <div class="user-name">
          <?= htmlspecialchars($teacherName) ?>
        </div>
        <div class="name-separator"></div>
        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="TeacherDashboard.php"><img src="icons/menu.png" class="nav-icon"> Dashboard</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherStats.php"><img src="icons/stats.png" class="nav-icon"> Στατιστικα</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherCreateThesis.php" class="active"><img src="icons/file.png" class="nav-icon"> Θεματα ΔΕ</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherThesisList.php"><img src="list.png" class="nav-icon"> Λιστα ΔΕ</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherInvites.php"><img src="icons/invitation.png" class="nav-icon"> Προσκλησεις</a>
          </li>
          <div class="nav-separator"></div>
          <li class="nav-spacing">
            <a href="settings.php"><img src="icons/setting.png" class="nav-icon"> Ρυθμισεις</a>
          </li>
          <li class="nav-spacing">
            <a href="logout.php" class="logout"><img src="icons/logout.png" class="nav-icon"> Αποσυνδεση</a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col py-3">
      <button class="mobile-menu-btn d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <i class="fas fa-bars"></i> Μενού
      </button>

      <div class="container">
        <header class="header">
          <div class="header-title">Θέματα Διπλωματικών</div>
        </header>
        <hr class="hr">

        <!-- Success/Error message -->
        <?php if (!empty($message)): ?>
          <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <!-- Form -->
        <div class="create-topic">
          <h2>Δημιουργία Νέου Θέματος</h2>
          <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
              <label for="title">Τίτλος Θέματος:</label>
              <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
              <label for="description">Περιγραφή:</label>
              <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group file-upload-submit-row">
              <div class="form-group">
                <label for="pdf_file">Αρχείο PDF:</label>
                <input type="file" id="pdf_file" name="pdf_file" accept=".pdf">
              </div>
              <button type="submit" class="create-topic-btn">Δημιουργία Θέματος</button>
            </div>
          </form>
        </div>

        <!-- Topics List -->
        <div class="topics-list mt-4">
          <h2>Τα Θέματά μου</h2>
          <?php while ($row = $topicsResult->fetch_assoc()): ?>
            <div class="topic-card">
              <h3><?= htmlspecialchars($row['title']) ?></h3>
              <p><?= htmlspecialchars($row['summary']) ?></p>
              <?php if ($row['pdf_path']): ?>
                <a href="<?= $row['pdf_path'] ?>" target="_blank">Προβολή PDF</a>
              <?php endif; ?>
              <div class="text-muted small"><?= $row['created_at'] ?></div>
              <i class="fas fa-edit edit-icon" data-topic-id="<?= $row['id'] ?>"></i>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
