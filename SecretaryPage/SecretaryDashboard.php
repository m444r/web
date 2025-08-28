<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Γραμματείας</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/SecretaryDashboard.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='SecretaryProfile.html'">
        
        <!-- User name link -->
        <div class="user-name">
          Γραμματεία
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="SecretaryDashboard.html" class="active">
              <img src="../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryThesis.html">
              <img src="../icons/file.png" alt="Thesis View" class="nav-icon">
              Προβολή ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryDataInput.html">
              <img src="../icons/graph.png" alt="Data Input" class="nav-icon">
              Εισαγωγή δεδομένων
            </a>
          </li>
          <li class="nav-spacing">
            <a href="SecretaryManageThesis.html">
              <img src="../icons/stats.png" alt="Manage Thesis" class="nav-icon">
              Διαχείριση ΔΕ
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="SecretaryProfile.html">
              <img src="../icons/setting.png" alt="Profile" class="nav-icon">
              Προφίλ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="../login_page.html" class="logout">
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

      <!-- Secretary Dashboard Content -->
      <div class="container">
        <header>
            <h1>Καλώς ήρθες, Γραμματεία!</h1>
        </header>
        <hr class="hr">

        <!-- Quick Stats Section -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>Ενεργές ΔΕ</h3>
                    <p class="stat-number">24</p>
                    <p class="stat-label">Διπλωματικές</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="stat-content">
                    <h3>Υπό Εξέταση</h3>
                    <p class="stat-number">8</p>
                    <p class="stat-label">ΔΕ σε εξέταση</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>Ολοκληρωμένες</h3>
                    <p class="stat-number">156</p>
                    <p class="stat-label">Φετος</p>
                </div>
            </div>
        </div>

        <!-- Recent Activities Section -->
        <div class="activities-section">
            <h2>Πρόσφατες Ενεργειες</h2>
            <div class="activities-list">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-upload"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Εισαγωγή Δεδομένων</h4>
                        <p>Εισήχθησαν 15 νέοι φοιτητές από το JSON αρχείο</p>
                        <span class="activity-time">Πριν 1 ώρα</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Ενημέρωση Κατάστασης ΔΕ</h4>
                        <p>Η ΔΕ "Μηχανική Μάθηση" άλλαξε σε "Υπό Εξέταση"</p>
                        <span class="activity-time">Πριν 3 ώρες</span>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Ολοκλήρωση ΔΕ</h4>
                        <p>Η ΔΕ "Web Εφαρμογή" ολοκληρώθηκε επιτυχώς</p>
                        <span class="activity-time">Πριν 1 ημέρα</span>
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
