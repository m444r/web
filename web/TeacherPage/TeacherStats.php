<?php
session_start();
require '../config.php'; 

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$teacher_id = $_SESSION['userid'];

$teacherName = "";
$teacherProfilePicture = "../icons/account.png"; // Default profile picture

// Check if profile_picture column exists
$check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column->num_rows > 0;

if ($has_profile_picture) {
    $stmt = $db->prepare("SELECT name, surname, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
}

$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
    if ($has_profile_picture) {
        $teacherProfilePicture = (!empty($row['profile_picture'])) ? "../" . $row['profile_picture'] : "../icons/account.png";
    }
}

// Μέσος χρόνος περάτωσης (μήνες)
$stmt = $db->prepare("
    SELECT AVG(TIMESTAMPDIFF(MONTH, t.confirmed_time, t.exam_datetime)) AS avg_months
    FROM topics t
    WHERE t.teacher_id = ? AND t.status='completed'
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($avg_completion_supervisor);
$stmt->fetch();
$stmt->close();

$stmt = $db->prepare("
    SELECT AVG(TIMESTAMPDIFF(MONTH, t.confirmed_time, t.exam_datetime)) AS avg_months
    FROM topics t
    JOIN committee_requests cr ON cr.topic_id = t.id
    WHERE cr.teacher_id = ? 
      AND cr.status='accepted'
      AND t.status='completed'
      AND t.teacher_id != ?
");
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$stmt->bind_result($avg_completion_committee);
$stmt->fetch();
$stmt->close();

// --- Μέσος βαθμός ως Επιβλέπων ---
$stmt = $db->prepare("
  SELECT AVG(cg.grade) AS avg_supervisor_grade
  FROM committee_grades cg
  JOIN topics t ON t.id = cg.topic_id
  WHERE t.teacher_id = ?            -- ο καθηγητής είναι επιβλέπων
    AND cg.teacher_id = ?           -- και ο ίδιος έδωσε τον βαθμό
    AND cg.grade IS NOT NULL
    AND t.status = 'completed'
");
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$stmt->bind_result($avg_supervisor_grade);
$stmt->fetch();
$stmt->close();

// --- Μέσος βαθμός ως Μέλος Τριμελούς (όχι επιβλέπων) ---
$stmt = $db->prepare("
  SELECT AVG(cg.grade) AS avg_committee_grade
  FROM committee_grades cg
  JOIN topics t ON t.id = cg.topic_id
  JOIN committee_requests cr ON cr.topic_id = t.id
  WHERE cr.teacher_id = ?              -- είναι μέλος
    AND cr.status = 'accepted'         -- μόνο οι αποδεκτές συμμετοχές
    AND t.teacher_id <> ?              -- να μην είναι επιβλέπων
    AND cg.grade IS NOT NULL
    AND t.status = 'completed'
");
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$stmt->bind_result($avg_committee_grade);
$stmt->fetch();
$stmt->close();

// Ασφαλείς default τιμές & μορφοποίηση για JS
$avg_supervisor_grade = $avg_supervisor_grade !== null ? round((float)$avg_supervisor_grade, 2) : 0;
$avg_committee_grade  = $avg_committee_grade  !== null ? round((float)$avg_committee_grade, 2)  : 0;

// Πλήθος διπλωματικών
$stmt = $db->prepare("SELECT COUNT(*) FROM topics t WHERE t.teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($count_supervisor);
$stmt->fetch();
$stmt->close();

$stmt = $db->prepare("
    SELECT COUNT(DISTINCT t.id)
    FROM topics t
    JOIN committee_requests cr ON cr.topic_id = t.id
    WHERE cr.teacher_id = ? 
      AND cr.status='accepted'
      AND t.teacher_id != ?
");
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$stmt->bind_result($count_committee);
$stmt->fetch();
$stmt->close();

// Ασφαλείς default τιμές
$count_supervisor = $count_supervisor ?? 0;
$count_committee = $count_committee ?? 0;
$avg_completion_supervisor = $avg_completion_supervisor ?? 0;
$avg_completion_committee = $avg_completion_committee ?? 0;
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Στατιστικά Διπλωματικών</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/TeacherStats.css?v=<?php echo time(); ?>">
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
            <a href="TeacherStats.php" class="active">
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
            <a href="TeacherProfile.php">
              <img src="../icons/setting.png" alt="Settings" class="nav-icon">
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

      <!-- Statistics Content -->
      <div class="container">
        <header class="header">
            <div class="header-title"><b>Στατιστικά Διπλωματικών</b></div>
        </header>
        <hr class="hr">
        <main>
            <div class="stats-column spaced-cards">
                <div class="stat-card">
                    <div class="stat-card-title">Μεσος Χρονος Περατωσης</div>
                    <div class="chart-container">
                        <canvas id="completionTimeChart"></canvas>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Μεσος Βαθμος</div>
                    <div class="chart-container">
                        <canvas id="gradeChart"></canvas>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-title">Συνολικο Πληθος</div>
                    <div class="chart-container">
                        <canvas id="totalThesesChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Chart.js code
  // Create gradient functions
  function createGradient(ctx, color1, color2) {
      const gradient = ctx.createLinearGradient(0, 0, 0, 400);
      gradient.addColorStop(0, color1);
      gradient.addColorStop(1, color2);
      return gradient;
  }

  const completionTimeData = {
      labels: ['Επιβλεπων', 'Τριμελης'],
      datasets: [{
          label: 'Επιβλεπων',
          data: [<?= $avg_completion_supervisor ?>, 0],
          backgroundColor: '#C7F0C7',
          borderColor: '#B7E0B7',
          borderWidth: 0,
          maxBarThickness: 32,
          hoverBackgroundColor: '#B7E0B7',
          hoverBorderColor: '#A7D0A7',
          hoverBorderWidth: 3
      }, {
          label: 'Τριμελης',
          data: [0, <?= $avg_completion_committee ?>],
          backgroundColor: '#E6D7FF',
          borderColor: '#D6C7FF',
          borderWidth: 0,
          maxBarThickness: 32,
          hoverBackgroundColor: '#D6C7FF',
          hoverBorderColor: '#C6B7FF',
          hoverBorderWidth: 3
      }]
  };
  const gradeData = {
      labels: ['Επιβλεπων', 'Τριμελης'],
      datasets: [{
          label: 'Επιβλεπων',
          data: [<?= $avg_supervisor_grade ?>, 0],
          backgroundColor: '#B8E6FF',
          borderColor: '#A8D6FF',
          borderWidth: 0,
          maxBarThickness: 32,
          hoverBackgroundColor: '#A8D6FF',
          hoverBorderColor: '#98C6FF',
          hoverBorderWidth: 3
      }, {
          label: 'Τριμελης',
          data: [0, <?= $avg_committee_grade ?>],
          backgroundColor: '#FFD1DC',
          borderColor: '#FFC1CC',
          borderWidth: 0,
          maxBarThickness: 32,
          hoverBackgroundColor: '#FFC1CC',
          hoverBorderColor: '#FFB1BC',
          hoverBorderWidth: 3
      }]
  };
  const totalThesesData = {
      labels: ['Επιβλεπων', 'Τριμελης'],
      datasets: [{
          label: 'Επιβλεπων',
          data: [<?= $count_supervisor ?>, 0],
          backgroundColor: '#FFCCCC',
          borderColor: '#FFBCBC',
          borderWidth: 0,
          maxBarThickness: 32,
          hoverBackgroundColor: '#FFBCBC',
          hoverBorderColor: '#FFACAC',
          hoverBorderWidth: 3
      }, {
          label: 'Τριμελης',
          data: [0, <?= $count_committee ?>],
          backgroundColor: '#C7F0C7',
          borderColor: '#B7E0B7',
          borderWidth: 0,
          maxBarThickness: 32,
          hoverBackgroundColor: '#B7E0B7',
          hoverBorderColor: '#A7D0A7',
          hoverBorderWidth: 3
      }]
  };
  const chartConfig = {
      type: 'bar',
      options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { 
                  display: true,
                  position: 'bottom',
                  align: 'center',
                  labels: {
                      usePointStyle: true,
                      pointStyle: 'rect',
                      padding: 25,
                      font: {
                          size: 14,
                          weight: '600',
                          family: 'Inter, sans-serif'
                      },
                      color: '#2c3e50',
                      boxWidth: 20,
                      boxHeight: 12
                  },
                  title: {
                      display: false
                  }
              },
              tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: '#fff',
              bodyColor: '#fff',
              borderColor: '#6A90C7',
              borderWidth: 1,
              cornerRadius: 8,
              displayColors: false,
              titleFont: {
                  size: 14,
                  weight: 'bold'
              },
              bodyFont: {
                  size: 13
              },
              padding: 12,
              callbacks: {
                  title: function(context) {
                      return context[0].label;
                  },
                  label: function(context) {
                      const chart = context.chart;
                      const chartId = chart.canvas.id;
                      let unit = '';
                      
                      if (chartId === 'completionTimeChart') {
                          unit = ' μήνες';
                      } else if (chartId === 'gradeChart') {
                          unit = ' βαθμός';
                      } else if (chartId === 'totalThesesChart') {
                          unit = ' διπλωματικές';
                      }
                      
                      return context.dataset.label + ': ' + context.parsed.x + unit;
                  }
              }
          }
          },
          animation: {
              duration: 800,
              easing: 'easeInOutQuart',
              delay: (context) => {
                  return context.dataIndex * 50;
              }
          },
          interaction: {
              intersect: true,
              mode: 'nearest'
          },
          elements: {
              bar: {
                  borderRadius: 6,
                  borderSkipped: false
              }
          },
          layout: {
              padding: {
                  top: 10,
                  bottom: 10
              }
          },
          scales: {
              x: {
                  beginAtZero: true,
                  grid: { 
                      color: 'rgba(224, 231, 239, 0.5)',
                      drawBorder: false
                  },
                  ticks: { 
                      color: '#555',
                      font: {
                          size: 12,
                          weight: '500'
                      }
                  }
              },
              y: {
                  grid: { display: false },
                  ticks: { 
                      display: false,
                      color: '#555'
                  }
              }
          },
          barPercentage: 0.7,
          categoryPercentage: 0.8
      }
  };
  const completionTimeChart = new Chart(
      document.getElementById('completionTimeChart'),
      { ...chartConfig, data: completionTimeData }
  );
  const gradeChart = new Chart(
      document.getElementById('gradeChart'),
      { ...chartConfig, data: gradeData }
  );
  const totalThesesChart = new Chart(
      document.getElementById('totalThesesChart'),
      { ...chartConfig, data: totalThesesData }
  );

  // --- Legend hover effect ---
  function addLegendHoverEffect(legendSelector, chart, colorActive, colorInactive, borderActive, borderInactive) {
      const legend = legendSelector;
      if (!legend) return;
      const items = legend.querySelectorAll('.legend-item');
      items.forEach((item, idx) => {
          item.addEventListener('mouseenter', () => {
              chart.data.datasets[0].backgroundColor = chart.data.datasets[0].backgroundColor.map((c, i) =>
                  i === idx ? colorActive[i] : colorInactive[i]
              );
              chart.data.datasets[0].borderColor = chart.data.datasets[0].borderColor.map((c, i) =>
                  i === idx ? borderActive[i] : borderInactive[i]
              );
              chart.update();
          });
          item.addEventListener('mouseleave', () => {
              chart.data.datasets[0].backgroundColor = colorActive;
              chart.data.datasets[0].borderColor = borderActive;
              chart.update();
          });
      });
  }
  // Colors for active/inactive
  const primaryBlue = '#6A90C7', secondaryBlue = '#8B9DC3';
  const primaryBlueFaded = 'rgba(106, 144, 199, 0.5)', secondaryBlueFaded = 'rgba(139, 157, 195, 0.5)';
  // Add effect for each chart
  addLegendHoverEffect(
      document.querySelectorAll('.legend-centered')[0],
      completionTimeChart,
      [primaryBlue, secondaryBlue],
      [primaryBlueFaded, secondaryBlueFaded],
      [primaryBlue, secondaryBlue],
      [primaryBlueFaded, secondaryBlueFaded]
  );
  addLegendHoverEffect(
      document.querySelectorAll('.legend-centered')[1],
      gradeChart,
      [primaryBlue, secondaryBlue],
      [primaryBlueFaded, secondaryBlueFaded],
      [primaryBlue, secondaryBlue],
      [primaryBlueFaded, secondaryBlueFaded]
  );
  addLegendHoverEffect(
      document.querySelectorAll('.legend-centered')[2],
      totalThesesChart,
      [primaryBlue, secondaryBlue],
      [primaryBlueFaded, secondaryBlueFaded],
      [primaryBlue, secondaryBlue],
      [primaryBlueFaded, secondaryBlueFaded]
  );

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