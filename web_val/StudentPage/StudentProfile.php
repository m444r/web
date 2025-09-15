<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../register.php");
    exit;
}

$student_id = $_SESSION["userid"];
$message = "";

$studentName = "";
$stmt = $db->prepare("SELECT name, surname, am , email, mobile_telephone , landline_telephone, street, number,city, postcode  FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $studentName = $row['name'] . " " . $row['surname'];
    $am_student = $row['am'];
    $email_student = $row['email'];
    $phone_student = $row['mobile_telephone'];
    $phone_landline_student = $row['landline_telephone'];
    $address_student = $row['street'] . " " . $row['number'] . ", " . $row['city'] . " " . $row['postcode'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'] ?? '';
    $phone_mobile = $_POST['phone_mobile'] ?? '';
    $phone_landline = $_POST['phone_landline'] ?? '';
    $street = $_POST['street'] ?? '';
    $number = $_POST['number'] ?? '';
    $city = $_POST['city'] ?? '';
    $postcode = $_POST['postcode'] ?? '';

    $stmt = $db->prepare("UPDATE users SET email=?, mobile_telephone=?, landline_telephone=?, street=?, number=?, city=?, postcode=? WHERE id=?");
    $stmt->bind_param("sssssssi", $email, $phone_mobile, $phone_landline, $street, $number, $city, $postcode, $student_id);
    $stmt->execute();
    $stmt->close();

    $message = "Οι αλλαγές αποθηκεύτηκαν επιτυχώς!";
    // Φορτώνουμε ξανά τα δεδομένα
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>


<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προφίλ Φοιτητή</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/StudentProfile.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='StudentProfile.php'">
        
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
            <a href="StudentTopics.php">
              <img src="../icons/list.png" alt="Dashboard" class="nav-icon">
              Λίστα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentTopics.php" >
              <img src="../icons/file.png" alt="Topics" class="nav-icon">
              Ανάρτηση Αρχείων
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentManageThesis.php">
              <img src="../icons/invitation.png" alt="Manage Thesis" class="nav-icon">
              Προσκλήσεις
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="StudentProfile.php" class="active">
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

      <!-- Student Profile Content -->
      <div class="container">
        <header>
            <h1>Προφίλ Φοιτητή</h1>
        </header>
        <hr class="hr">

        <div class="profile-section">
            <div class="profile-card">
                <div class="profile-header">
                    <img src="../icons/account.png" alt="Profile Picture" class="profile-picture">
                    <div class="profile-info">
                        <h2><?= htmlspecialchars($studentName) ?></h2>
                        <p class="student-id">ΑΜ: <?= htmlspecialchars($am_student) ?></p>
                        <p class="department">Τμήμα Πληροφορικής</p>
                    </div>
                </div>
                
                <form method="post">
    <div class="profile-details">
        <div class="detail-item">
            <label class="label" for="email">Email:</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email_student) ?>" required>
        </div>
        <div class="detail-item">
            <label class="label" for="phone_mobile">Κινητό Τηλέφωνο:</label>
            <input type="text" id="phone_mobile" name="phone_mobile" class="form-control" value="<?= htmlspecialchars($phone_student) ?>">
        </div>
        <div class="detail-item">
            <label class="label" for="phone_landline">Σταθερό Τηλέφωνο:</label>
            <input type="text" id="phone_landline" name="phone_landline" class="form-control" value="<?= htmlspecialchars($phone_landline_student) ?>">
        </div>
        <div class="detail-item">
            <label class="label" for="street">Οδός:</label>
            <input type="text" id="street" name="street" class="form-control" value="<?= htmlspecialchars($row['street']) ?>">
        </div>
        <div class="detail-item">
            <label class="label" for="number">Αριθμός:</label>
            <input type="text" id="number" name="number" class="form-control" value="<?= htmlspecialchars($row['number']) ?>">
        </div>
        <div class="detail-item">
            <label class="label" for="city">Πόλη:</label>
            <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($row['city']) ?>">
        </div>
        <div class="detail-item">
            <label class="label" for="postcode">Τ.Κ.:</label>
            <input type="text" id="postcode" name="postcode" class="form-control" value="<?= htmlspecialchars($row['postcode']) ?>">
        </div>
    </div>

    <button type="submit" name="update_profile" class="btn btn-primary mt-3">Αποθήκευση Αλλαγών</button>
</form>

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
