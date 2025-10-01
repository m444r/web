<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['student_query'], $_POST['topic_id'])) {
    $student_input = $_POST['student_query'];
    $topic_id = $_POST['topic_id'];

    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'student' AND (am = ? OR CONCAT(name, ' ', surname) LIKE ?)");
    $search_term = "%$student_input%";
    $stmt->bind_param("ss", $student_input, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $message = "Δεν βρέθηκε φοιτητής με αυτά τα στοιχεία.";
    } else {
        $student = $result->fetch_assoc();
        $student_id = $student['id'];

        // Check if student already has a thesis assigned (excluding cancelled topics)
        $stmt = $db->prepare("SELECT id, title, status FROM topics WHERE assigned_to = ? AND status NOT IN ('cancelled', 'completed', 'available')");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $existing_thesis = $stmt->get_result()->fetch_assoc();

        if ($existing_thesis) {
            $message = "Ο φοιτητής έχει ήδη ανατεθεί σε άλλο θέμα: " . htmlspecialchars($existing_thesis['title']);
        } else {
            // Proceed with assignment
            try {
                // First, clear any existing cancelled assignments for this student
                $clear_stmt = $db->prepare("UPDATE topics SET assigned_to = NULL WHERE assigned_to = ? AND status = 'cancelled'");
                $clear_stmt->bind_param("i", $student_id);
                $clear_stmt->execute();
                $clear_stmt->close();
                
                // Now assign the new topic
                $stmt = $db->prepare("UPDATE topics SET assigned_to = ?, status = 'awaiting_committee', assigned_time = NOW() WHERE id = ? AND teacher_id = ?");
                $stmt->bind_param("iii", $student_id, $topic_id, $teacher_id);

                if ($stmt->execute()) {
                    $message = "Το θέμα ανατέθηκε προσωρινά.";
                } else {
                    // Check if it's a unique constraint violation
                    if ($db->errno === 1062) {
                        $message = "Ο φοιτητής έχει ήδη ανατεθεί σε άλλο θέμα. Κάθε φοιτητής μπορεί να έχει μόνο μία διπλωματική εργασία.";
                    } else {
                        $message = "Σφάλμα κατά την ανάθεση: " . $db->error;
                    }
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $message = "Ο φοιτητής έχει ήδη ανατεθεί σε άλλο θέμα. Κάθε φοιτητής μπορεί να έχει μόνο μία διπλωματική εργασία.";
                } else {
                    $message = "Σφάλμα κατά την ανάθεση: " . $e->getMessage();
                }
            }
        }
    }
}

// --- Όνομα καθηγητή ---
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

if (isset($_GET['search_student'])) {
    $term = "%" . $_GET['search_student'] . "%";

    $stmt = $db->prepare("SELECT id, am, name, surname, email 
                          FROM users 
                          WHERE role = 'student' 
                            AND (am LIKE ? OR name LIKE ? OR surname LIKE ?) 
                          LIMIT 10");
    $stmt->bind_param("sss", $term, $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            "label" => $row['am']." - ".$row['name']." ".$row['surname']." (".$row['email'].")",
            "value" => $row['id']
        ];
    }

    echo json_encode($students);
    exit;
}

// --- Λίστα θεμάτων ---
$topics = [];
$stmt = $db->prepare("SELECT * FROM topics WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $topics[] = $row;

$status_counts = [
    'available' => 0,
    'confirmed' => 0,
    'for examination' => 0,
    'for_grade' => 0,
    'awaiting_committee' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$stmt = $db->prepare("
    SELECT status, COUNT(*) as total
    FROM topics
    WHERE teacher_id = ? 
    GROUP BY status
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = (int)$row['total'];
}

$stmt->close();

$stmt = $db->prepare("
    SELECT t.id, t.title
    FROM topics t
    LEFT JOIN committee_requests cm 
        ON cm.topic_id = t.id 
        AND cm.teacher_id = ? 
        AND cm.status='accepted'
    LEFT JOIN committee_grades cg 
        ON cg.topic_id = t.id 
        AND cg.teacher_id = ?
    WHERE t.status = 'for examination'
      AND (t.teacher_id = ? OR cm.teacher_id IS NOT NULL)
      AND cg.teacher_id IS NULL
    GROUP BY t.id
");
$stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
$stmt->execute();
$exam_topics = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grade'])) {
    $topic_id = intval($_POST['topic_id']);
    $teacher_id = intval($_SESSION['userid']);
    $c1 = floatval($_POST['criterion1']);
    $c2 = floatval($_POST['criterion2']);
    $c3 = floatval($_POST['criterion3']);
    $c4 = floatval($_POST['criterion4']);

    // Υπολογισμός τελικού σταθμισμένου βαθμού
    $final_grade = $c1 * 0.6 + $c2 * 0.15 + $c3 * 0.15 + $c4 * 0.1;

    // Ενημέρωση στον πίνακα committee_grades
    $stmt2 = $db->prepare("
        INSERT INTO committee_grades (topic_id, teacher_id, grade, submitted_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE grade = VALUES(grade), submitted_at = NOW()
    ");
    $stmt2->bind_param("iid", $topic_id, $teacher_id, $final_grade);
    $stmt2->execute();

    $message = "Βαθμός καταχωρήθηκε: " . round($final_grade, 2);
    header("Location: TeacherDashboard.php");
}

// Βρίσκουμε όλους τους καθηγητές που πρέπει να βαθμολογήσουν (unique)
$stmt3 = $db->prepare("
    SELECT DISTINCT teacher_id
    FROM (
        SELECT teacher_id FROM committee_requests WHERE topic_id = ? AND status='accepted'
        UNION
        SELECT teacher_id FROM topics WHERE id = ?
    ) AS all_teachers
");
$stmt3->bind_param("ii", $topic_id, $topic_id);
$stmt3->execute();
$result = $stmt3->get_result();

$teachers_to_grade = [];
while ($row = $result->fetch_assoc()) {
    $teachers_to_grade[] = $row['teacher_id'];
}
$stmt3->close();

// Πόσοι έχουν υποβάλει βαθμό
$stmt4 = $db->prepare("
    SELECT COUNT(DISTINCT teacher_id) as total_grades
    FROM committee_grades
    WHERE topic_id = ?
");
$stmt4->bind_param("i", $topic_id);
$stmt4->execute();
$total_grades = $stmt4->get_result()->fetch_assoc()['total_grades'];
$stmt4->close();

// Αν όλοι οι καθηγητές έχουν υποβάλει βαθμό
if (count($teachers_to_grade) == $total_grades) {
    // Υπολογισμός μέσου όρου
    $stmt5 = $db->prepare("
        SELECT AVG(grade) as avg_grade
        FROM committee_grades
        WHERE topic_id = ?
    ");
    $stmt5->bind_param("i", $topic_id);
    $stmt5->execute();
    $avg_grade = $stmt5->get_result()->fetch_assoc()['avg_grade'];
    $stmt5->close();

    // Ενημέρωση του Topic
    $stmt6 = $db->prepare("
        UPDATE topics
        SET status='completed', final_grade=?
        WHERE id=?
    ");
    $stmt6->bind_param("di", $avg_grade, $topic_id);
    $stmt6->execute();
    $stmt6->close();
}
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
      <link rel="stylesheet" href="../css/TeacherDashboard.css?v=<?php echo time(); ?>">
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
            <a href="#" class="active">
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

      <!-- Teacher Dashboard Content -->
      <div class="container">
        <header>
            <h1>Καλώς ήρθες, <?= htmlspecialchars($teacherName) ?>!</h1>
        </header>
        <hr class="hr">


        <!-- Thesis Section -->
        <div class="thesis-section">
            <h2>Τα Θεματα μου</h2>
            <div class="thesis-table-container">
              <table class="thesis-table">
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
                      <tr class="thesis-row">
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td>
                          <?php if ($row['pdf_path']): ?>
                            <?php 
                            // Extract filename from path
                            $filename = basename($row['pdf_path']);
                            ?>
                            <a href="../view_pdf.php?file=<?= urlencode($filename) ?>" target="_blank" class="btn btn-sm view-pdf-btn">Προβολή</a>
                          <?php else: ?>
                            -
                          <?php endif; ?>
                        </td>
                        <td><?= $row['created_at'] ?></td>
                      </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
              
              <!-- Pagination -->
              <div class="thesis-pagination">
                <button class="pagination-btn">
                  <i class="fas fa-chevron-left"></i>
                </button>
                <span class="pagination-info">1/1</span>
                <button class="pagination-btn">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </div>
            </div>
        </div>

        <!-- Grade Entry and Student Assignment Cards -->
        <div class="thesis-section">
            <div class="cards-container">
                <!-- Grade Entry Card -->
                <div class="thesis-card">
                    <h3>Καταχωριση Βαθμου</h3>
                    <form class="thesis-form" method="post">
                        <div class="form-group">
                            <label for="thesis-select">Επιλέξτε Διπλωματική:</label>
                            <select name="topic_id" id="thesis-select" class="form-input" required>
                                <option value="">Επιλέξτε διπλωματική εργασία</option>
                                <?php while ($topic = $exam_topics->fetch_assoc()): ?>
                                    <option value="<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quality-grade">Ποιότητα Δ.Ε. και εκπλήρωση στόχων (60%):</label>
                            <input type="number" name="criterion1" id="quality-grade" class="form-input" placeholder="Βαθμός ποιότητας" min="0" max="10" step="0.5" required>
                        </div>
                        <div class="form-group">
                            <label for="time-grade">Χρονικό διάστημα εκπόνησης (15%):</label>
                            <input type="number" name="criterion2" id="time-grade" class="form-input" placeholder="Βαθμός χρόνου" min="0" max="10" step="0.5" required>
                        </div>
                        <div class="form-group">
                            <label for="text-grade">Ποιότητα κειμένου και παραδοτέων (15%):</label>
                            <input type="number" name="criterion3" id="text-grade" class="form-input" placeholder="Βαθμός κειμένου" min="0" max="10" step="0.5" required>
                        </div>
                        <div class="form-group">
                            <label for="presentation-grade">Συνολική εικόνα παρουσίασης (10%):</label>
                            <input type="number" name="criterion4" id="presentation-grade" class="form-input" placeholder="Βαθμός παρουσίασης" min="0" max="10" step="0.5" required>
                        </div>
                        <button type="submit" name="submit_grade" class="submit-btn">Υποβολη Βαθμου</button>
                    </form>
                </div>

                <!-- Student Assignment Card -->
                <div class="thesis-card">
                    <h3>Ανάθεση σε Φοιτητές</h3>
                    <?php if (!empty($message)): ?>
                        <?php 
                        // Determine alert type based on message content
                        $alertClass = 'alert-info'; // Default to info (blue)
                        if (strpos($message, 'Σφάλμα') !== false || strpos($message, 'δεν βρέθηκε') !== false || strpos($message, 'ήδη ανατεθεί') !== false) {
                            $alertClass = 'alert-danger'; // Red for errors
                        } elseif (strpos($message, 'ανατέθηκε') !== false || strpos($message, 'Βαθμός καταχωρήθηκε') !== false) {
                            $alertClass = 'alert-success'; // Blue for success
                        }
                        ?>
                        <div class="alert <?= $alertClass ?> alert-top"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <form class="thesis-form" method="post">
                        <div class="form-group">
                            <label for="student-select">Φοιτητής (ΑΜ ή Όνομα):</label>
                            <input type="text" name="student_query" id="student-select" class="form-input" placeholder="Εισάγετε ΑΜ ή όνομα φοιτητή" required>
                        </div>
                        <div class="form-group">
                            <label for="topic-select">Επιλογή Θεματος:</label>
                            <select name="topic_id" id="topic-select" class="form-input" required>
                                <option value="">Επιλέξτε θέμα διπλωματικής</option>
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
                        </div>
                        <button type="submit" name="assign" class="submit-btn">Ανάθεση</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="thesis-section">
            <div class="stats-container">
                <div class="stats-header">
                    <h2>Στατιστικά ΔΕ</h2>
                </div>
                <div style="height: 400px; max-height: 400px; width: 100%; max-width: 100%; overflow: hidden;">
                    <canvas id="statsChart" style="height: 400px; max-height: 400px; width: 100%; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Combine similar statuses and filter out zeros
  const statusData = [
    <?= $status_counts['available'] ?>,
    <?= $status_counts['confirmed'] ?>,
    <?= $status_counts['for examination'] + $status_counts['for_grade'] + $status_counts['awaiting_committee'] ?>,
    <?= $status_counts['completed'] ?>,
    <?= $status_counts['cancelled'] ?>
  ];
  
  // Create gradient function for dashboard chart
  function createDashboardGradient(ctx, color1, color2) {
      const gradient = ctx.createLinearGradient(0, 0, 0, 400);
      gradient.addColorStop(0, color1);
      gradient.addColorStop(1, color2);
      return gradient;
  }
  
  // Chart.js enhanced example
  const ctx = document.getElementById('statsChart');
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['Υπό Ανάθεση', 'Ενεργή', 'Υπό Εξέταση', 'Περατωμένη', 'Ακυρωμένη'],
      datasets: [{
        label: 'Διπλωματικές',
        data: statusData,
        backgroundColor: [
          '#B8E6FF',
          '#FFB6C1', 
          '#E6D7FF',
          '#C7F0C7',
          '#FFCCCC'
        ],
        borderColor: [
          '#A8D6FF',
          '#FFA0B0',
          '#D6C7FF', 
          '#B7E0B7',
          '#FFBCBC'
        ],
        borderWidth: 2,
        hoverBackgroundColor: [
          '#A8D6FF',
          '#FFA0B0',
          '#D6C7FF',
          '#B7E0B7', 
          '#FFBCBC'
        ],
        hoverBorderColor: [
          '#98C6FF',
          '#FF90A0',
          '#C6B7FF',
          '#A7D0A7',
          '#FFACAC'
        ],
        hoverBorderWidth: 3
      }]
    },
    options: {
      responsive: false,
      maintainAspectRatio: true,
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
              const value = context.parsed;
              if (value === 0) {
                return 'Δεν υπάρχουν θέματα';
              }
              return 'Αριθμός: ' + value;
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
          borderRadius: 8,
          borderSkipped: false
        }
      },
      layout: {
        padding: {
          top: 10,
          bottom: 10
        }
      },
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
    
    // Auto-hide alert messages after 3 seconds
    const alertMessages = document.querySelectorAll('.alert');
    alertMessages.forEach(alert => {
      setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
          alert.remove();
        }, 500);
      }, 3000);
    });
  });

  // Pagination functionality for topics table
  document.addEventListener("DOMContentLoaded", function() {
    const rowsPerPage = 3; // Number of topics per page
    const rows = document.querySelectorAll(".thesis-table .thesis-row");
    const totalRows = rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);

    let currentPage = 1;

    function showPage(page) {
      // Hide all rows
      rows.forEach((row, index) => {
        row.style.display = "none";
        // Show rows for current page
        if (index >= (page - 1) * rowsPerPage && index < page * rowsPerPage) {
          row.style.display = "table-row";
        }
      });
      
      // Update pagination info
      const pageInfo = document.querySelector(".pagination-info");
      if (pageInfo) {
        pageInfo.textContent = `${page} / ${totalPages}`;
      }
      
      // Update button states
      const prevBtn = document.querySelector(".thesis-pagination .pagination-btn:first-child");
      const nextBtn = document.querySelector(".thesis-pagination .pagination-btn:last-child");
      
      if (prevBtn) prevBtn.disabled = (page === 1);
      if (nextBtn) nextBtn.disabled = (page === totalPages);
    }

    // Previous button click
    const prevBtn = document.querySelector(".thesis-pagination .pagination-btn:first-child");
    if (prevBtn) {
      prevBtn.addEventListener("click", function() {
        if (currentPage > 1) {
          currentPage--;
          showPage(currentPage);
        }
      });
    }

    // Next button click
    const nextBtn = document.querySelector(".thesis-pagination .pagination-btn:last-child");
    if (nextBtn) {
      nextBtn.addEventListener("click", function() {
        if (currentPage < totalPages) {
          currentPage++;
          showPage(currentPage);
        }
      });
    }

    // Show first page initially
    showPage(currentPage);
  });
</script>
</body>
</html>
