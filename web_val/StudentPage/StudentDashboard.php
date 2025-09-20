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
$studentProfilePicture = "../icons/account.png"; // Default profile picture

// Check if profile_picture column exists
$check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column->num_rows > 0;

if ($has_profile_picture) {
    $stmt = $db->prepare("SELECT name, surname, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $studentName = $row['name'] . " " . $row['surname'];
    if ($has_profile_picture) {
        $studentProfilePicture = (!empty($row['profile_picture'])) ? "../" . $row['profile_picture'] : "../icons/account.png";
    }
}

$theses = [];
if ($student_id) {
    $stmt = $db->prepare("SELECT t.*, u.name AS teacher_name, u.surname AS teacher_surname  
                          FROM topics t 
                          JOIN users u ON t.teacher_id = u.id 
                          WHERE t.assigned_to = ? AND t.status IN ('awaiting_committee', 'confirmed', 'for examination', 'for_grade', 'completed')
                          ORDER BY t.id DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $theses[] = $row;
    }
}

if ($student_id) {
    // Παίρνουμε τις ενεργές διπλωματικές (status=confirmed)
    $stmt = $db->prepare("SELECT COUNT(*) as count, MIN(DATEDIFF(deadline, CURDATE())) as days
                          FROM topics
                          WHERE assigned_to = ? AND status = 'confirmed'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $active_count = intval($result['count']);
    if ($result['days'] !== null) {
        $days_remaining = max(0, intval($result['days'])); // Δεν εμφανίζουμε αρνητικές μέρες
    }
}

// Ενημέρωση exam_mode και exam_datetime από φοιτητή
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topic_id'])) {
    $topic_id = intval($_POST['topic_id']);
    $exam_mode = $_POST['exam_mode'] ?? null;
    $exam_datetime = $_POST['exam_datetime'] ?? null;

    if ($exam_mode && $exam_datetime) {
        // Ενημέρωση exam_mode και exam_datetime
        $stmt = $db->prepare("UPDATE topics SET exam_mode = ?, exam_datetime = ? WHERE id = ? AND assigned_to = ?");
        $stmt->bind_param("ssii", $exam_mode, $exam_datetime, $topic_id, $student_id);
        $stmt->execute();
        $stmt->close();

        // Ενημέρωση exam_location
        if($exam_mode === 'online'){
            $exam_location = 'http/hjfhfddhj';
        } else {
            $exam_location = 'Αίθουσα Γ';
        }

        $stmt = $db->prepare("UPDATE topics SET exam_location = ? WHERE id = ? AND assigned_to = ?");
        $stmt->bind_param("sii", $exam_location, $topic_id, $student_id);
        $stmt->execute();
        $stmt->close();

        $message = "Η καταχώρηση εξέτασης αποθηκεύτηκε επιτυχώς.";

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

?>





<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Φοιτητή</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/StudentDashboard.css">
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
            <a href="StudentDashboard.php"  class="active">
              <img src="../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentThesis.php" >
              <img src="../icons/thesis.png" alt="Thesis" class="nav-icon">
              Διπλωματική Εργασία
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentInvites.php">
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
            <a href="StudentProfile.php">
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

      <!-- Student Dashboard Content -->
      <div class="container">
        <header>
            <h1>Καλώς ήρθες, <?= htmlspecialchars($studentName) ?>!</h1>
        </header>
        <hr class="hr">
        

        <!-- Current Thesis Section -->
       <div class="thesis-section">
        <h2>Η Διπλωματική μου</h2>

        <?php if(empty($theses)): ?>
            <div class="no-thesis-card">
                <h3>Δεν έχετε ανατεθεί σε καμία διπλωματική εργασία</h3>
                <p>Επικοινωνήστε με τον επιβλέποντα καθηγητή σας για να σας ανατεθεί θέμα διπλωματικής εργασίας.</p>
            </div>
        <?php else: ?>
            <?php foreach($theses as $thesis): ?>
                <?php
                $status_info = [
                    'awaiting_committee' => ['title' => 'Υπό Ανάθεση - Επιλογή Επιτροπής', 'class' => 'warning', 'icon' => 'fas fa-users'],
                    'confirmed' => ['title' => 'Ενεργή Διπλωματική', 'class' => 'active', 'icon' => 'fas fa-play'],
                    'for examination' => ['title' => 'Υπό Εξέταση', 'class' => 'info', 'icon' => 'fas fa-search'],
                    'for_grade' => ['title' => 'Υπό Βαθμολόγηση', 'class' => 'info', 'icon' => 'fas fa-chart-line'],
                    'completed' => ['title' => 'Περατωμένη Διπλωματική', 'class' => 'success', 'icon' => 'fas fa-check-circle']
                ];
                $current_status = $status_info[$thesis['status']] ?? ['title' => 'Διπλωματική Εργασία', 'class' => 'pending', 'icon' => 'fas fa-file'];
                ?>
                <div class="thesis-card">
                    <div class="thesis-header">
                        <h3><?= htmlspecialchars($thesis['title']) ?></h3>
                        <span class="status-badge <?= $current_status['class'] ?>">
                            <i class="<?= $current_status['icon'] ?>"></i>
                            <?= $current_status['title'] ?>
                        </span>
                    </div>
                    <div class="thesis-details">
                        <p><strong>Επιβλέπων:</strong> <?= htmlspecialchars($thesis['teacher_name'].' '.$thesis['teacher_surname']) ?></p>
                        <?php if (!empty($thesis['confirmed_time'])): ?>
                            <p><strong>Ημερομηνία Έναρξης:</strong> <?= date('d/m/Y', strtotime($thesis['confirmed_time'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($thesis['deadline'])): ?>
                            <p><strong>Προθεσμία Υποβολής:</strong> <?= date('d/m/Y', strtotime($thesis['deadline'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($thesis['exam_datetime'])): ?>
                            <p><strong>Ημερομηνία Εξέτασης:</strong> <?= date('d/m/Y H:i', strtotime($thesis['exam_datetime'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($thesis['exam_location'])): ?>
                            <p><strong>Τοποθεσία Εξέτασης:</strong> <?= htmlspecialchars($thesis['exam_location']) ?></p>
                        <?php endif; ?>
                        <p><strong>Τελικός Βαθμός:</strong> <?= htmlspecialchars($thesis['final_grade']) ?></p>

                    </div>

                    <!-- Status-based actions -->
                    <div class="thesis-actions">
                        <?php if ($thesis['status'] == 'awaiting_committee'): ?>
                            <a href="StudentInvites.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> Επιλογή Επιτροπής
                            </a>
                        <?php elseif ($thesis['status'] == 'confirmed'): ?>
                            <a href="StudentInvites.php" class="btn btn-success">
                                <i class="fas fa-upload"></i> Ανάρτηση Πρόχειρου
                            </a>
                            <a href="StudentManageThesis.php#exam-section" class="btn btn-info">
                                <i class="fas fa-calendar"></i> Καταχώρηση Εξέτασης
                            </a>
                        <?php elseif ($thesis['status'] == 'for examination'): ?>
                            <a href="StudentInvites.php" class="btn btn-success">
                                <i class="fas fa-upload"></i> Ανάρτηση Αρχείων
                            </a>
                            <a href="StudentManageThesis.php#exam-section" class="btn btn-info">
                                <i class="fas fa-edit"></i> Ενημέρωση Εξέτασης
                            </a>
                        <?php elseif ($thesis['status'] == 'for_grade'): ?>
                            <a href="StudentManageThesis.php#library-section" class="btn btn-warning">
                                <i class="fas fa-link"></i> Σύνδεσμος Βιβλιοθήκης
                            </a>
                        <?php elseif ($thesis['status'] == 'completed'): ?>
                            <a href="StudentManageThesis.php#protocol-section" class="btn btn-secondary">
                                <i class="fas fa-file-alt"></i> Προβολή Πρακτικού
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

        <!-- Calendar Section -->
        <div class="calendar-section">
            <h2>Ημερολόγιο Διπλωματικής</h2>
            <div class="calendar-container">
                <div class="calendar-header">
                    <button id="prevMonth" class="calendar-nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h3 id="currentMonth"></h3>
                    <button id="nextMonth" class="calendar-nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-grid" id="calendarGrid">
                    <!-- Calendar will be populated by JavaScript -->
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

    // Calendar functionality
    let currentDate = new Date();
    const calendarGrid = document.getElementById('calendarGrid');
    const currentMonthElement = document.getElementById('currentMonth');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    // Thesis data from PHP
    const thesisData = <?= json_encode($theses) ?>;

    function renderCalendar() {
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();
      
      // Update month display
      const monthNames = [
        'Ιανουάριος', 'Φεβρουάριος', 'Μάρτιος', 'Απρίλιος', 'Μάιος', 'Ιούνιος',
        'Ιούλιος', 'Αύγουστος', 'Σεπτέμβριος', 'Οκτώβριος', 'Νοέμβριος', 'Δεκέμβριος'
      ];
      currentMonthElement.textContent = `${monthNames[month]} ${year}`;

      // Clear calendar
      calendarGrid.innerHTML = '';

      // Add day headers
      const dayHeaders = ['Δευ', 'Τρί', 'Τετ', 'Πέμ', 'Παρ', 'Σάβ', 'Κυρ'];
      dayHeaders.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
      });

      // Get first day of month and number of days
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const daysInMonth = lastDay.getDate();
      const startingDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1; // Monday = 0

      // Add empty cells for days before month starts
      for (let i = 0; i < startingDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        calendarGrid.appendChild(emptyDay);
      }

      // Add days of the month
      for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;

        // Check if this day has thesis events
        const dayDate = new Date(year, month, day);
        const events = getEventsForDate(dayDate);
        
        if (events.length > 0) {
          dayElement.classList.add('has-events');
          events.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = `calendar-event ${event.type}`;
            eventElement.textContent = event.title;
            eventElement.title = event.description;
            dayElement.appendChild(eventElement);
          });
        }

        // Highlight today
        const today = new Date();
        if (dayDate.toDateString() === today.toDateString()) {
          dayElement.classList.add('today');
        }

        calendarGrid.appendChild(dayElement);
      }
    }

    function getEventsForDate(date) {
      const events = [];
      const dateString = date.toISOString().split('T')[0];

      thesisData.forEach(thesis => {
        // Check deadline
        if (thesis.deadline) {
          const deadline = new Date(thesis.deadline);
          if (deadline.toDateString() === date.toDateString()) {
            events.push({
              type: 'deadline',
              title: 'Προθεσμία',
              description: `Προθεσμία υποβολής: ${thesis.title}`
            });
          }
        }

        // Check exam date
        if (thesis.exam_datetime) {
          const examDate = new Date(thesis.exam_datetime);
          if (examDate.toDateString() === date.toDateString()) {
            events.push({
              type: 'exam',
              title: 'Εξέταση',
              description: `Εξέταση διπλωματικής: ${thesis.title}`
            });
          }
        }

        // Check confirmed time (start date)
        if (thesis.confirmed_time) {
          const startDate = new Date(thesis.confirmed_time);
          if (startDate.toDateString() === date.toDateString()) {
            events.push({
              type: 'start',
              title: 'Έναρξη',
              description: `Έναρξη διπλωματικής: ${thesis.title}`
            });
          }
        }
      });

      return events;
    }

    // Event listeners
    prevMonthBtn.addEventListener('click', () => {
      currentDate.setMonth(currentDate.getMonth() - 1);
      renderCalendar();
    });

    nextMonthBtn.addEventListener('click', () => {
      currentDate.setMonth(currentDate.getMonth() + 1);
      renderCalendar();
    });

    // Initial render
    renderCalendar();
  });
</script>
</body>
</html>