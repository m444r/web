<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";
/*
if (isset($_POST['assign'])) {
    $assigned_to = $_POST['assigned_to'] ?? null;
    $topic_id = $_POST['topic_id'] ?? null;

    if ($assigned_to && $topic_id) {
        $stmt = $db->prepare("UPDATE topics SET assigned_to = ?, status = 'awaiting_committee' WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("iii", $assigned_to, $topic_id, $teacher_id);
        if (!$stmt->execute()) {
            die("Update failed: " . $stmt->error);
        } else {
            echo "<div class='alert alert-success'>✅ Ανάθεση έγινε!</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>❌ Δεν στάλθηκαν σωστά τα δεδομένα</div>";
    }
}


*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['student_query'], $_POST['topic_id'])) {
    $student_input = $_POST['student_query'];
    $topic_id = $_POST['topic_id'];

    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'student' AND (am = ? OR CONCAT(name, ' ', surname) LIKE ?)");
    $search_term = "%$student_input%";
    $stmt->bind_param("ss", $student_input, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $message = "❌ Δεν βρέθηκε φοιτητής με αυτά τα στοιχεία.";
    } else {
        $student = $result->fetch_assoc();
        $student_id = $student['id'];

        $stmt = $db->prepare("UPDATE topics SET assigned_to = ?, status = 'awaiting_committee', assigned_time = NOW() WHERE id = ? AND teacher_id = ?");

        $stmt->bind_param("iii", $student_id, $topic_id, $teacher_id);

      //  $stmt = $db->prepare ("INSERT INTO committee_requests (topic_id, teacher_id, status) VALUES (? , ? ,'pending')"); //???


        if ($stmt->execute()) {
            $message = "✅ Το θέμα ανατέθηκε προσωρινά.";
        } else {
            $message = "❌ Σφάλμα κατά την ανάθεση.";
        }
    }
}

$stmt = $db->prepare("SELECT t.id, t.title, t.status, u.am AS student_am, u.name AS student_name, u.surname 
                        FROM topics t 
                        LEFT JOIN users u ON t.assigned_to = u.id
                        WHERE t.teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$topics = $stmt->get_result();

// --- Όνομα καθηγητή ---
$teacherName = "";
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
}

if (isset($_GET['search_student'])) {
    require 'config.php';
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


$status_counts = [
    'available'   => 0,
    'confirmed'     => 0,
    'for examination'       => 0,
    'completed'  => 0
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
    LEFT JOIN committee_requests cm ON cm.topic_id = t.id
    WHERE t.status = 'for_grade' 
      AND (t.teacher_id = ? OR cm.teacher_id = ?)
    GROUP BY t.id
");
$stmt->bind_param("ii", $teacher_id, $teacher_id);
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

    echo "<div class='alert alert-success'>✅ Βαθμός καταχωρήθηκε: ".round($final_grade,2)."</div>";
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
  <link rel="stylesheet" href="css/TeacherDashboard.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='TeacherProfile.php'">
        
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
          <li class="nav-spacing">
            <a href="TeacherNotes.php">
              <img src="list.png" alt="Thesis List" class="nav-icon">
              Οι σημειώσεις μου
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="TeacherSettings.php">
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
        <label>Φοιτητής (ΑΜ ή Όνομα):</label>
        <input type="text" name="student_query" required>
          <!-- εδώ θα εμφανίζονται τα αποτελέσματα -->
        <div id="student_results" class="list-group mt-1"></div>



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



<div class="content-box">
  <h3 class="main-heading">Καταχώρηση Βαθμού</h3>

  <form method="post">
    <label>Επιλέξτε Διπλωματική:</label>
    <select name="topic_id" required>
      <option value="">-- Επιλέξτε Θέμα --</option>
      <?php while ($topic = $exam_topics->fetch_assoc()): ?>
        <option value="<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></option>
      <?php endwhile; ?>
    </select>

    <div class="criteria-grid">
      <div>
        <label>Ποιότητα Δ.Ε. και εκπλήρωση στόχων (60%)</label>
        <input type="number" name="criterion1" min="0" max="10" step="0.5" required>
      </div>
      <div>
        <label>Χρονικό διάστημα εκπόνησης (15%)</label>
        <input type="number" name="criterion2" min="0" max="10" step="0.5" required>
      </div>
      <div>
        <label>Ποιότητα κειμένου και παραδοτέων (15%)</label>
        <input type="number" name="criterion3" min="0" max="10" step="0.5" required>
      </div>
      <div>
        <label>Συνολική εικόνα παρουσίασης (10%)</label>
        <input type="number" name="criterion4" min="0" max="10" step="0.5" required>
      </div>
    </div>

    <button type="submit" name="submit_grade"class="btn btn-success">Υποβολή Βαθμού</button>
  </form>
</div>



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
    const statusData = <?= json_encode(array_values($status_counts)) ?>;

  // Chart.js example
  const ctx = document.getElementById('statsChart');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Υπό Ανάθεση', 'Ενεργή', 'Υπό Εξέταση', 'Περατωμένη'],
      datasets: [{
        label: 'Αριθμός Διπλωματικών',
        data: statusData,   // παίρνει τα δεδομένα από PHP
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
</body>
</html>
