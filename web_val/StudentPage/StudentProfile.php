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
    $stmt = $db->prepare("SELECT name, surname, am, email, mobile_telephone, landline_telephone, street, number, city, postcode, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $db->prepare("SELECT name, surname, am, email, mobile_telephone, landline_telephone, street, number, city, postcode FROM users WHERE id = ?");
}

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
    if ($has_profile_picture) {
        $studentProfilePicture = (!empty($row['profile_picture'])) ? "../" . $row['profile_picture'] : "../icons/account.png";
    }
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
            <a href="StudentInvites.php" >
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
                    <div class="profile-picture-container">
                        <img src="<?= htmlspecialchars($studentProfilePicture) ?>" alt="Profile Picture" class="profile-picture" id="profileImage">
                        <div class="edit-overlay" onclick="document.getElementById('profileUpload').click()">
                            <i class="fas fa-camera"></i>
                        </div>
                        <input type="file" id="profileUpload" accept="image/*" style="display: none;" onchange="handleProfileUpload(this)">
                    </div>
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

    <div class="form-actions">
        <button type="submit" name="update_profile" class="btn btn-primary">Αποθήκευση Αλλαγών</button>
    </div>
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

  // Profile upload function
  window.handleProfileUpload = function(input) {
    if (input.files && input.files[0]) {
      const file = input.files[0];
      
      // Show loading state
      const profileImg = document.getElementById('profileImage');
      const originalSrc = profileImg.src;
      profileImg.style.opacity = '0.5';
      
      // Create FormData to send file to server
      const formData = new FormData();
      formData.append('profile_picture', file);
      
      // Send to server
      console.log('Sending file to server...');
      fetch('upload_profile_picture.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Server response:', data);
        if (data.success) {
          // Update profile image with new path
          profileImg.src = '../' + data.image_path;
          profileImg.style.opacity = '1';
          
          // Show success message
          alert('Profile picture updated successfully!');
          
          // Update all sidebar profile images on the page
          updateSidebarProfileImages('../' + data.image_path);
        } else {
          // Show error message
          alert('Error: ' + data.message);
          profileImg.src = originalSrc;
          profileImg.style.opacity = '1';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error uploading profile picture. Please try again.');
        profileImg.src = originalSrc;
        profileImg.style.opacity = '1';
      });
    }
  };
  
  // Function to update sidebar profile images
  function updateSidebarProfileImages(newImagePath) {
    const sidebarImages = document.querySelectorAll('.profile-avatar');
    console.log('Found sidebar images:', sidebarImages.length);
    sidebarImages.forEach(img => {
      console.log('Updating image from', img.src, 'to', newImagePath);
      img.src = newImagePath;
    });
  }
</script>
</body>
</html>
