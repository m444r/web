<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";




    if (isset($_POST['assign'])) {
        $assigned_to = $_POST['assigned_to'] ?? null;
        $topic_id = $_POST['topic_id'] ?? null;

        if ($assigned_to && $topic_id) {
            $stmt = $db->prepare("UPDATE topics SET assigned_to = ?, status = 'awaiting_committee' WHERE id = ? AND teacher_id = ?");
            $stmt->bind_param("iii", $assigned_to, $topic_id, $teacher_id);
            $stmt->execute();
        }
    }

// --- Όνομα καθηγητή ---
$teacherName = "";
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
}

// --- Λίστα φοιτητών ---
$students = [];
$res = $db->query("SELECT id, name, surname FROM users WHERE role='student'");
while ($s = $res->fetch_assoc()) $students[] = $s;

// --- Λίστα θεμάτων ---
$topics = [];
$stmt = $db->prepare("SELECT * FROM topics WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $topics[] = $row;

?>






<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard - Thesis Management</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/TeacherDashboard.css">
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
            <a href="#" class="active">
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

    <div class="content-box">
  <h3 class="main-heading">Τα θεματα μου</h3>

  <table id="topicsTable" class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Τίτλος</th>
        <th>Κατάσταση</th>
        <th>PDF</th>
        <th>Ημερομηνία</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $stmt = $db->prepare("SELECT * FROM topics WHERE teacher_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()): ?>
          <tr class="topic-row">
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td>
              <?php if ($row['pdf_path']): ?>
                <a href="<?= $row['pdf_path'] ?>" target="_blank" class="btn btn-sm btn-primary">Προβολή</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= $row['created_at'] ?></td>
          </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Pagination controls -->
  <div class="d-flex justify-content-center align-items-center">
    <button id="prevBtn" class="btn btn-outline-secondary me-2">
      <i class="fas fa-chevron-left"></i>
    </button>
    <span id="pageInfo"></span>
    <button id="nextBtn" class="btn btn-outline-secondary ms-2">
      <i class="fas fa-chevron-right"></i>
    </button>
  </div>
</div>
<div class="content-box mt-4">
  <h3 class="main-heading">Ανάθεση σε Φοιτητές</h3>

  <form method="post" class="d-flex align-items-center gap-2">
    <!-- Επιλογή φοιτητή -->
    <select name="assigned_to" class="form-select" required>
      <option value="">-- Επιλογή Φοιτητή --</option>
      <?php
        $stmt = $db->prepare("SELECT id, name, surname, email FROM users WHERE role = 'student'");
        $stmt->execute();
        $students = $stmt->get_result();
        while ($student = $students->fetch_assoc()):
      ?>
        <option value="<?= $student['id'] ?>">
          <?= htmlspecialchars($student['name'] . " " . $student['surname'] . " (" . $student['email'] . ")") ?>
        </option>
      <?php endwhile; ?>
    </select>

    <!-- Επιλογή θέματος -->
    <select name="topic_id" class="form-select" required>
      <option value="">-- Επιλογή Θέματος --</option>
      <?php
        $tstmt = $db->prepare("SELECT id, title FROM topics WHERE teacher_id = ? AND status = 'available'");
        $tstmt->bind_param("i", $teacher_id);
        $tstmt->execute();
        $topics = $tstmt->get_result();
        while ($topic = $topics->fetch_assoc()):
      ?>
        <option value="<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></option>
      <?php endwhile; ?>
    </select>

    <button type="submit" name="assign" class="btn btn-success">Ανάθεση</button>

  </form>
</div>



<script>
document.addEventListener("DOMContentLoaded", function() {
  const rowsPerPage = 3; // πόσα θέματα ανά σελίδα
  const rows = document.querySelectorAll("#topicsTable .topic-row");
  const totalRows = rows.length;
  const totalPages = Math.ceil(totalRows / rowsPerPage);

  let currentPage = 1;

  function showPage(page) {
    rows.forEach((row, index) => {
      row.style.display = "none";
      if (index >= (page - 1) * rowsPerPage && index < page * rowsPerPage) {
        row.style.display = "table-row";
      }
    });
    document.getElementById("pageInfo").innerText = `${page} / ${totalPages}`;
    document.getElementById("prevBtn").disabled = (page === 1);
    document.getElementById("nextBtn").disabled = (page === totalPages);
  }

  document.getElementById("prevBtn").addEventListener("click", function() {
    if (currentPage > 1) {
      currentPage--;
      showPage(currentPage);
    }
  });

  document.getElementById("nextBtn").addEventListener("click", function() {
    if (currentPage < totalPages) {
      currentPage++;
      showPage(currentPage);
    }
  });

  // Εμφάνιση της πρώτης σελίδας
  showPage(currentPage);
});
</script>


      <!-- Statistics Section -->
      <div class="content-box stats-container">
        <h3 class="main-heading">Στατιστικα</h3>
        
        <!-- Chart Navigation -->
        <div class="chart-nav">
          <button class="chart-nav-btn">
            <i class="fas fa-chevron-left"></i>
          </button>
          <button class="chart-nav-btn">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
        
        <canvas id="statsChart" style="max-height: 400px;"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Chart.js example
  const ctx = document.getElementById('statsChart');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Υπό Ανάθεση', 'Ενεργή', 'Υπό Εξέταση', 'Περατωμένη'],
      datasets: [{
        label: 'Αριθμός Διπλωματικών',
        data: [3, 5, 2, 4],
        backgroundColor: '#6A90C7',
        borderColor: '#5a7fb7',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          display: false 
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  });

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
