<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Θέματα Διπλωματικών - Φοιτητής</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/StudentTopics.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='StudentProfile.php'">
        
        <!-- User name link -->
        <div class="user-name">
          Γιώργος Παπαδόπουλος
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="StudentDashboard.php">
              <img src="icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentTopics.php" class="active">
              <img src="icons/file.png" alt="Topics" class="nav-icon">
              Θέματα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentManageThesis.php">
              <img src="icons/stats.png" alt="Manage Thesis" class="nav-icon">
              Διαχείριση ΔΕ
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="StudentProfile.php">
              <img src="icons/setting.png" alt="Profile" class="nav-icon">
              Προφίλ
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

      <!-- Student Topics Content -->
      <div class="container">
        <header>
            <h1>Διαθέσιμα Θέματα Διπλωματικών</h1>
        </header>
        <hr class="hr">

        <div class="topics-container">
            <!-- Topic cards will go here -->
            <div class="topic-card">
                <h3>Ανάπτυξη Web Εφαρμογής για Διαχείριση Διπλωματικών</h3>
                <p>Ανάπτυξη σύγχρονης web εφαρμογής για τη διαχείριση διπλωματικών εργασιών με χρήση τεχνολογιών όπως React, Node.js και MySQL.</p>
                <div class="topic-details">
                    <span class="supervisor">Επιβλέπων: Δρ. Μαρία Κωνσταντίνου</span>
                    <span class="department">Τμήμα: Πληροφορικής</span>
                </div>
                <button class="apply-btn">Αίτηση Θέματος</button>
            </div>

            <div class="topic-card">
                <h3>Μηχανική Μάθηση για Προγνωστικά Μοντέλα</h3>
                <p>Εφαρμογή αλγορίθμων μηχανικής μάθησης για την ανάπτυξη προγνωστικών μοντέλων σε πραγματικά δεδομένα.</p>
                <div class="topic-details">
                    <span class="supervisor">Επιβλέπων: Δρ. Νίκος Αλεξίου</span>
                    <span class="department">Τμήμα: Πληροφορικής</span>
                </div>
                <button class="apply-btn">Αίτηση Θέματος</button>
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
