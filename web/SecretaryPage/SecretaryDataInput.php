<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εισαγωγή Δεδομένων - Γραμματεία</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/SecretaryDataInput.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="../icons/account.png" alt="Profile" class="profile-avatar">

        <!-- User name link -->
        <div class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Γραμματεία') ?></div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="SecretaryDashboard.php">
              <img src=" ../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryThesis.php">
              <img src=" ../icons/file.png" alt="Thesis View" class="nav-icon">
              Προβολή ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryDataInput.php" class="active">
              <img src=" ../icons/graph.png" alt="Data Input" class="nav-icon">
              Εισαγωγή δεδομένων
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryManageThesis.php">
              <img src=" ../icons/stats.png" alt="Manage Thesis" class="nav-icon">
              Διαχείριση ΔΕ
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="../logout.php" class="logout">
              <img src=" ../icons/logout.png" alt="Logout" class="nav-icon">
              Αποσυνδεση
            </a>
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
        <header>
            <h1>Εισαγωγή Δεδομένων</h1>
        </header>
        <hr class="hr">

        
        <div class="upload-section">
            <div class="upload-card">
                <div class="upload-header">
                    <i class="fas fa-users"></i>
                    <h3>Εισαγωγή Δεδομένων Φοιτητών</h3>
                </div>
                <p>Επιλέξτε ένα JSON αρχείο που περιέχει τα προσωπικά δεδομένα των φοιτητών.</p>
               <form action="import_users.php" method="POST" enctype="multipart/form-data" class="upload-form">
                  <input type="hidden" name="role" value="student">
                  
                  <label for="students-json" class="file-label">
                      <i class="fas fa-cloud-upload-alt"></i>
                      <span>Επιλέξτε JSON αρχείο</span>
                  </label>
                  <input type="file" id="students-json" name="jsonFile" accept=".json" class="file-input" required>

                  <button type="submit" class="upload-btn">
                    <i class="fas fa-upload"></i> Εισαγωγή Φοιτητών
                  </button>
                </form>


            <div class="upload-card">
                <div class="upload-header">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Εισαγωγή Δεδομένων Εκπαιδευτικών</h3>
                </div>
                <p>Επιλέξτε ένα JSON αρχείο που περιέχει τα προσωπικά δεδομένα των εκπαιδευτικών.</p>
                <div class="file-upload-area">
                    <input type="file" id="faculty-file" accept=".json" class="file-input">
                    <label for="faculty-file" class="file-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Επιλέξτε JSON αρχείο</span>
                    </label>
                </div>
                <form action="import_users.php" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="role" value="teacher">
                  <input type="file" name="jsonFile" accept=".json" class="file-input" required>
                  <button type="submit" class="upload-btn">
                  <i class="fas fa-upload"></i> Εισαγωγή Εκπαιδευτικών
                </button>
                </form>
            </div>
        </div>

    
        

            
        <div class="format-guide-section">
            <h2>Οδηγίες Μορφής JSON</h2>
            <div class="format-cards">
                <div class="format-card">
                    <h4>Μορφή για Φοιτητές</h4>
                    <pre><code>[
  {
    "student_id": "123456",
    "name": "Γιώργος Παπαδόπουλος",
    "email": "giorgos@upatras.gr",
    "department": "Πληροφορικής",
    "semester": 8
  }
]</code></pre>
                </div>
                
                <div class="format-card">
                    <h4>Μορφή για Εκπαιδευτικούς</h4>
                    <pre><code>[
  {
    "faculty_id": "F001",
    "name": "Δρ. Μαρία Κωνσταντίνου",
    "email": "maria@upatras.gr",
    "department": "Πληροφορικής",
    "title": "Καθηγήτρια"
  }
]</code></pre>
                </div>
            </div>
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

