<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$teacher_id = $_SESSION["userid"];

// Get teacher information - Updated to fix undefined variable error
$teacherName = "";
$teacherEmail = "";
$teacherProfilePicture = "../icons/account.png"; // Default profile picture

// Check if profile_picture column exists
$check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column->num_rows > 0;

if ($has_profile_picture) {
    $stmt = $db->prepare("SELECT name, surname, email, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $db->prepare("SELECT name, surname, email FROM users WHERE id = ?");
}

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
    $teacherEmail = $row['email'] ?? "";
    if ($has_profile_picture) {
        $teacherProfilePicture = (!empty($row['profile_picture'])) ? "../" . $row['profile_picture'] : "../icons/account.png";
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προφίλ Καθηγητή</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/TeacherProfile.css?v=<?php echo time(); ?>">
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
            <a href="TeacherInvites.php">
              <img src="../icons/invitation.png" alt="Invitations" class="nav-icon">
              Προσκλησεις
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="TeacherProfile.php" class="active">
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

      <!-- Teacher Profile Content -->
      <div class="container">
        <header>
            <h1>Προφίλ Καθηγητή</h1>
        </header>
        <hr class="hr">

        <div class="profile-section">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-picture-container">
                        <img src="<?= htmlspecialchars($teacherProfilePicture) ?>" alt="Profile" class="profile-picture" id="profileImage">
                        <div class="edit-overlay" onclick="document.getElementById('profileUpload').click()">
                            <i class="fas fa-camera"></i>
                        </div>
                        <input type="file" id="profileUpload" accept="image/*" style="display: none;" onchange="handleProfileUpload(this)">
                    </div>
                    <div class="profile-info">
                        <h2><?= htmlspecialchars($teacherName) ?></h2>
                        <p class="teacher-id">ID: T<?= str_pad($teacher_id, 3, '0', STR_PAD_LEFT) ?></p>
                        <p class="department">Τμήμα Πληροφορικής</p>
                    </div>
                </div>
                
                <form class="profile-form" id="profileForm">
                    <div class="profile-details">
                        <div class="detail-item">
                            <span class="label">Email:</span>
                            <input type="email" class="form-input" value="<?= htmlspecialchars($teacherEmail) ?>" id="email">
                        </div>
                        <div class="detail-item">
                            <span class="label">Τηλέφωνο:</span>
                            <input type="tel" class="form-input" value="" id="phone" placeholder="Δεν έχει καταχωρηθεί">
                        </div>
                        <div class="detail-item">
                            <span class="label">Τίτλος:</span>
                            <input type="text" class="form-input" value="Αναπληρώτρια Καθηγήτρια" id="title">
                        </div>
                        <div class="detail-item">
                            <span class="label">Ειδικότητα:</span>
                            <input type="text" class="form-input" value="Τεχνητή Νοημοσύνη" id="specialty">
                        </div>
                        <div class="detail-item">
                            <span class="label">Γραφείο:</span>
                            <input type="text" class="form-input" value="Κτίριο Επιστημών, 2ος όροφος, 201" id="office">
                        </div>
                        <div class="detail-item">
                            <span class="label">Ώρες Γραφείου:</span>
                            <input type="text" class="form-input" value="Τρίτη & Πέμπτη 14:00-16:00" id="officeHours">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="resetForm()">Ακύρωση</button>
                        <button type="submit" class="btn-save">Αποθήκευση Αλλαγών</button>
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

    // Form handling
    const form = document.getElementById('profileForm');
    const originalValues = {};

    // Store original values
    const inputs = form.querySelectorAll('.form-input');
    inputs.forEach(input => {
      originalValues[input.id] = input.value;
    });

    // Form submission
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Here you would typically send the data to a server
      // For now, we'll just show a success message
      alert('Οι αλλαγές αποθηκεύτηκαν επιτυχώς!');
      
      // Update original values
      inputs.forEach(input => {
        originalValues[input.id] = input.value;
      });
    });

    // Reset form function
    window.resetForm = function() {
      inputs.forEach(input => {
        input.value = originalValues[input.id];
      });
    };

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
  });
</script>
</body>
</html>
