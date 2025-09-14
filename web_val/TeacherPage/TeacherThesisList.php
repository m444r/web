<?php
session_start();
require '../config.php'; 

$teacher_id = $_SESSION['userid']; 

$status_filter = $_GET['status'] ?? 'all';
$role_filter   = $_GET['role'] ?? 'all';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

$params = [];        
$types  = "";  

$query = "
  SELECT 
    t.id, 
    t.title, 
    t.status, 
    t.deadline, 
    s.name AS student_name, 
    s.surname AS student_surname,
    u.name AS supervisor_name, 
    u.surname AS supervisor_surname,
    GROUP_CONCAT(
        DISTINCT CONCAT(
            c.name, ' ', c.surname,
            ' | Πρόσκληση: ', DATE_FORMAT(cr.requested_at, '%Y-%m-%d'),
            ' | Κατάσταση: ', cr.status,
            IF(cr.responded_at IS NOT NULL, CONCAT(' | Απόκριση: ', DATE_FORMAT(cr.responded_at, '%Y-%m-%d')), '')
        )
        ORDER BY c.surname ASC SEPARATOR ';;'
    ) AS committee_members,
    MAX(t.created_at) AS created_at,
    MAX(t.confirmed_time) AS confirmed_time
FROM topics t
LEFT JOIN users s ON s.id = t.assigned_to  
JOIN users u ON u.id = t.teacher_id
LEFT JOIN committee_requests cr 
       ON cr.topic_id = t.id AND cr.status='accepted'
LEFT JOIN users c ON c.id = cr.teacher_id
WHERE (t.teacher_id = ? 
   OR EXISTS (
        SELECT 1 
        FROM committee_requests cr2
        WHERE cr2.topic_id = t.id 
          AND cr2.teacher_id = ? 
          AND cr2.status = 'accepted'
   ))
";

$types  = "ii";  
$params = [$teacher_id, $teacher_id];


// --- Φίλτρο ρόλου ---
if ($role_filter === 'supervisor') {
    $query .= " AND t.teacher_id = ? ";
    $types  .= "i";
    $params[] = $teacher_id;
}
elseif ($role_filter === 'committee') {
    $query .= " AND EXISTS (
        SELECT 1 
        FROM committee_requests cr3
        WHERE cr3.topic_id = t.id 
          AND cr3.teacher_id = ? 
          AND cr3.status = 'accepted'
    )";
    $types  .= "i";
    $params[] = $teacher_id;
}


// --- Φίλτρο κατάστασης --- 
if ($status_filter !== 'all') {
  $query .= " AND t.status = ?";
  $types  .= "s";
  $params[] = $status_filter;
}

$query .= " GROUP BY t.id ORDER BY t.created_at DESC";

// --- Pagination ---
$cards_per_page = 6;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $cards_per_page;

// Create a separate count query for pagination
$count_query = "
  SELECT COUNT(DISTINCT t.id)
  FROM topics t
  LEFT JOIN users s ON s.id = t.assigned_to  
  JOIN users u ON u.id = t.teacher_id
  LEFT JOIN committee_requests cr 
         ON cr.topic_id = t.id AND cr.status='accepted'
  LEFT JOIN users c ON c.id = cr.teacher_id
  WHERE (t.teacher_id = ? 
     OR EXISTS (
          SELECT 1 
          FROM committee_requests cr2
          WHERE cr2.topic_id = t.id 
            AND cr2.teacher_id = ? 
            AND cr2.status = 'accepted'
     ))
";

// Add the same filters to count query
if ($role_filter === 'supervisor') {
    $count_query .= " AND t.teacher_id = ? ";
}
elseif ($role_filter === 'committee') {
    $count_query .= " AND EXISTS (
        SELECT 1 
        FROM committee_requests cr3
        WHERE cr3.topic_id = t.id 
          AND cr3.teacher_id = ? 
          AND cr3.status = 'accepted'
    )";
}

if ($status_filter !== 'all') {
    $count_query .= " AND t.status = ?";
}

$count_stmt = $db->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_cards = $count_stmt->get_result()->fetch_assoc()['COUNT(DISTINCT t.id)'];
$total_pages = ceil($total_cards / $cards_per_page);

// Debug output removed

// Add LIMIT and OFFSET to main query
$query .= " LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $cards_per_page;
$params[] = $offset;

// --- Prepare & bind --- 
$stmt = $db->prepare($query);
if (!$stmt) {
  die("Prepare failed: " . $db->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_grading'])) {
    $topic_id = intval($_POST['topic_id']);
    $teacher_id = $_SESSION['userid']; // ο logged-in καθηγητής

    if ($topic_id <= 0) {
        echo "<div class='alert alert-danger alert-top'>Άκυρο topic_id: $topic_id</div>";
    } else {
        // Μόνο αν ο επιβλέπων είναι ο ίδιος
        $stmt = $db->prepare("
            UPDATE topics 
            SET status = 'for_grade' 
            WHERE id = ? AND status = 'for examination' AND teacher_id = ?
        ");
        if (!$stmt) {
            die("Prepare failed: " . $db->error);
        }

        $stmt->bind_param("ii", $topic_id, $teacher_id);

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            echo "<div class='alert alert-success alert-top'>Η υποβολή βαθμού ενεργοποιήθηκε!</div>";
        } else {
            echo "<div class='alert alert-danger alert-top'>Δεν έχετε δικαίωμα ή το topic δεν είναι για εξέταση!</div>";
        }
    }
}

// Handle grade submission
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

    echo "<div class='alert alert-success alert-top'>Βαθμός καταχωρήθηκε: ".round($final_grade,2)."</div>";
}

if (isset($_GET['action']) && $_GET['action'] === 'get_grades' && isset($_GET['topic_id'])) {
    $tid = intval($_GET['topic_id']);
    $stmtGrades = $db->prepare("
        SELECT u.name, u.surname, cg.grade
        FROM committee_grades cg
        JOIN users u ON u.id = cg.teacher_id
        WHERE cg.topic_id = ?
    ");
    $stmtGrades->bind_param("i", $tid);
    $stmtGrades->execute();
    $resGrades = $stmtGrades->get_result();
    $gradesArr = [];
    while ($g = $resGrades->fetch_assoc()) {
        $gradesArr[] = htmlspecialchars($g['name'].' '.$g['surname']).': '.round($g['grade'],2);
    }
    echo implode('<br>', $gradesArr);
    exit;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Λίστα Διπλωματικών</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/TeacherThesisList.css?v=<?php echo time(); ?>">
    <style>
        .alert-top {
            position: fixed;
            top: 20px;
            left: calc(16.66667% + (83.33333% / 2));
            transform: translateX(-50%);
            z-index: 1050;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            padding: 16px 24px;
            font-weight: 500;
            animation: slideDown 0.4s ease-out;
            border: none;
            font-size: 14px;
            transition: opacity 0.5s ease;
        }
        
        .alert-top.alert-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .alert-top.alert-danger {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            color: white;
        }
        
        @keyframes slideDown {
            from {
                transform: translateX(-50%) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .alert-top {
                left: 50%;
            }
        }
    </style>
</head>

<script>
document.getElementById("saveBtn").addEventListener("click", function () {
    let topic_id = document.getElementById("modal-topic-id").value;
    let exam_datetime = document.getElementById("modal-exam-datetime").value;
    let deadline = document.getElementById("modal-deadline").value;
    let exam_mode = document.getElementById("modal-exam-mode").value;

    fetch("updateExam.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "topic_id=" + encodeURIComponent(topic_id) +
              "&exam_datetime=" + encodeURIComponent(exam_datetime) +
              "&deadline=" + encodeURIComponent(deadline) +
              "&exam_mode=" + encodeURIComponent(exam_mode)
    })
    .then(response => response.text())
    .then(data => {
        alert(data); // το μήνυμα από την PHP
        location.reload(); // ανανέωση της λίστας μετά το update
    })
    .catch(error => console.error("Σφάλμα:", error));
});
</script>
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
            <a href="TeacherThesisList.php" class="active">
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

      <!-- Thesis List Content -->
      <div class="container">
        <header>
            <h1>Λίστα Διπλωματικών</h1>
        </header>
        <hr class="hr">

        <!-- Filters -->
        <form method="get" class="filters-section">
          <div class="filter-group">
            <label for="status-filter">Κατάσταση:</label>
            <select id="status-filter" name="status" class="form-select" onchange="this.form.submit()">
              <option value="all" <?= $status_filter==='all'?'selected':'' ?>>Όλες</option>
              <option value="available" <?= $status_filter==='available'?'selected':'' ?>>Υπό Ανάθεση</option>
              <option value="confirmed" <?= $status_filter==='confirmed'?'selected':'' ?>>Ενεργή</option>
              <option value="for examination" <?= $status_filter==='for examination'?'selected':'' ?>>Υπό Εξέταση</option>
              <option value="completed" <?= $status_filter==='completed'?'selected':'' ?>>Περατωμένη</option>
              <option value="cancelled" <?= $status_filter==='cancelled'?'selected':'' ?>>Ακυρωμένη</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="role-filter">Ρόλος:</label>
            <select id="role-filter" name="role" class="form-select" onchange="this.form.submit()">
              <option value="all" <?= $role_filter==='all'?'selected':'' ?>>Όλοι</option>
              <option value="supervisor" <?= $role_filter==='supervisor'?'selected':'' ?>>Επιβλέπων</option>
              <option value="committee" <?= $role_filter==='committee'?'selected':'' ?>>Μέλος Τριμελούς</option>
            </select>
          </div>
          <div class="export-buttons">
            <button class="export-btn" id="export-csv">
              <i class="fas fa-file-csv"></i> Εξαγωγή CSV
            </button>
            <button class="export-btn" id="export-json">
              <i class="fas fa-file-code"></i> Εξαγωγή JSON
            </button>
          </div>
        </form>

        <!-- Thesis List -->
        <div class="thesis-list">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <div class="thesis-card">
                  <?php
                  
                  $topic_id = $row['id'];
                  $stmtSub = $db->prepare("
                      SELECT s.student_id, u.name, u.surname, s.file_path, s.comments, s.uploaded_at
                      FROM student_submissions s
                      JOIN users u ON u.id = s.student_id
                      WHERE s.topic_id = ?
                      ORDER BY s.uploaded_at DESC
                  ");
                  $stmtSub->bind_param("i", $topic_id);
                  $stmtSub->execute();
                  $submissionsResult = $stmtSub->get_result();
                  ?>

                <div class="d-flex justify-content-between align-items-center">
                  <h3><?= htmlspecialchars($row['title']) ?></h3>
                  <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </div>
                <div class="thesis-details mt-2">
                  <p><strong>Φοιτητής:</strong> <?= htmlspecialchars($row['student_name']." ".$row['student_surname']) ?></p>
                  <p><strong>Επιβλέπων:</strong> <?= htmlspecialchars($row['supervisor_name']." ".$row['supervisor_surname']) ?></p>
                  <p><strong>Τριμελής:</strong> <?= $row['committee_members'] ? htmlspecialchars($row['committee_members']) : '—' ?></p>
                  <?php if ($row['confirmed_time']): ?>
                    <p><strong>Επιβεβαιώθηκε:</strong> <?= htmlspecialchars($row['confirmed_time']) ?></p>
                  <?php endif; ?>
                </div>
                <div class="thesis-timeline">
                  <h4>Χρονολόγιο Ενεργειών</h4>
                  <ul>
                    <li><?= htmlspecialchars($row['confirmed_time']) ?> - Έναρξη διπλωματικής</li>
                    <?php if ($submissionsResult->num_rows > 0): ?>
                        <?php while ($sub = $submissionsResult->fetch_assoc()): ?>
                            <li>
                                <strong><?= htmlspecialchars($sub['name'] . ' ' . $sub['surname']) ?>:</strong>
                                <?php if (!empty($sub['file_path'])): ?>
                                    <a href="<?= htmlspecialchars($sub['file_path']) ?>" target="_blank"><?= basename($sub['file_path']) ?></a> - 
                                <?php endif; ?>
                                <?= htmlspecialchars($sub['comments'])  ?>
                                <small>(<?= date("d/m/Y H:i", strtotime($sub['uploaded_at'])) ?>)</small>
                            </li>
                        <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>

               
                </div>
                <div class="thesis-actions">
                  <!-- Κουμπί -->
                  <button type="button" class="action-btn view-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#detailsModal"
                    data-id="<?= $row['id'] ?>"
                    data-title="<?= htmlspecialchars($row['title']) ?>"
                    data-student="<?= htmlspecialchars($row['student_name'].' '.$row['student_surname']) ?>"
                    data-supervisor="<?= htmlspecialchars($row['supervisor_name'].' '.$row['supervisor_surname']) ?>"
                    data-committee="<?= htmlspecialchars($row['committee_members'] ?? '') ?>"
                    data-confirmed="<?= htmlspecialchars($row['confirmed_time'] ?? '') ?>"
                    data-deadline="<?= $row['deadline'] ? htmlspecialchars(date('Y-m-d', strtotime($row['deadline']))) : '' ?>">
                    <i class="fas fa-eye"></i> Προβολή Λεπτομερειών
                  </button>

                </div> 
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p>Δεν βρέθηκαν διπλωματικές με τα επιλεγμένα φίλτρα.</p>
          <?php endif; ?>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
          <div class="topics-pagination">
            <button class="pagination-btn" onclick="goToPage(<?= max(1, $current_page - 1) ?>)" <?= $current_page <= 1 ? 'disabled' : '' ?>>
              <i class="fas fa-chevron-left"></i>
            </button>
            <span class="pagination-info"><?= $current_page ?>/<?= $total_pages ?></span>
            <button class="pagination-btn" onclick="goToPage(<?= min($total_pages, $current_page + 1) ?>)" <?= $current_page >= $total_pages ? 'disabled' : '' ?>>
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        <?php endif; ?>

          <!-- Modal -->
         <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <form method="post" action="updateExam.php">
                  <div class="modal-header">
                    <h5 class="modal-title">Λεπτομέρειες Διπλωματικής</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="topic_id" id="modal-topic-id">
                    <p><strong>Τίτλος:</strong> <span id="modal-title"></span></p>
                    <p><strong>Φοιτητής:</strong> <span id="modal-student"></span></p>
                    <p><strong>Επιβλέπων:</strong> <span id="modal-supervisor"></span></p>
                    <p><strong>Τριμελής:</strong> <span id="modal-committee"></span></p>
                    <p><strong>Επιβεβαιώθηκε:</strong> <span id="modal-confirmed"></span></p>
                    <p><strong>Βαθμοί Τριμελούς:</strong> 
                        <span id="modal-grades">Φόρτωση...</span>
                    </p>


                  
                    <div class="mb-3">
                      <label for="deadline" class="form-label"><strong>Προθεσμία Υποβολής</strong></label>
                      <input type="date" class="form-control" name="deadline" id="modal-deadline">
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Κλείσιμο</button>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                  </div> 


                </form>
                
              <div class="modal-body">
              
                <p><strong>Διαχείριση Διπλωματικής:</strong> </p>
                <div class="d-flex gap-2 mt-2">
                <!-- ===== Ακύρωση ανάθεσης ===== -->
                <form method="post" action="updateExam.php" 
                      onsubmit="return confirm('Επιβεβαιώνετε την ακύρωση της ανάθεσης;');" 
                      class="mt-2">
                <input type="hidden" name="cancel_topic_id" id="cancel-topic-id">
                <button type="submit" name="cancel_topic_id" value="" class="btn btn-secondary ">Ακύρωση Ανάθεσης</button>
                </form>

                <?php if (!empty($_SESSION['cancel_message'])): ?>
                  <div class="alert alert-info alert-top"><?= $_SESSION['cancel_message']; ?></div>
                  <?php unset($_SESSION['cancel_message']); ?>
                <?php endif; ?>

                 <form method="post" action="updateExam.php" class="mt-2">
                    <input type="hidden" name="topic_id" id="for-examination-topic-id">
                    <input type="hidden" name="set_for_examination" value="1">
                    <button type="submit" class="btn btn-secondary">Υπό Εξέταση</button>
                  </form>

                  <form method="post" class="mt-2">
                      <input type="hidden" name="topic_id" id="modal-activate-topic-id">
                      <input type="hidden" name="activate_grading" value="1">
                      <button type="button" class="btn btn-success" id="activate-grading-btn">Υποβολη Βαθμου</button>
                  </form>
                  </div>

                  <!-- Grade Submission Form -->
                  <div class="mt-4" id="grade-form-section" style="display: none;">
                    <h5 class="mb-3">Καταχώρηση Βαθμού</h5>
                    <form method="post" id="grade-form">
                        <input type="hidden" name="topic_id" id="grade-topic-id">
                        
                        <div class="mb-3">
                            <label for="quality-grade" class="form-label">Ποιότητα Δ.Ε. και εκπλήρωση στόχων (60%):</label>
                            <input type="number" name="criterion1" id="quality-grade" class="form-control" placeholder="Βαθμός ποιότητας" min="0" max="10" step="0.5" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="time-grade" class="form-label">Χρονικό διάστημα εκπόνησης (15%):</label>
                            <input type="number" name="criterion2" id="time-grade" class="form-control" placeholder="Βαθμός χρόνου" min="0" max="10" step="0.5" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="text-grade" class="form-label">Ποιότητα κειμένου και παραδοτέων (15%):</label>
                            <input type="number" name="criterion3" id="text-grade" class="form-control" placeholder="Βαθμός κειμένου" min="0" max="10" step="0.5" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="presentation-grade" class="form-label">Συνολική εικόνα παρουσίασης (10%):</label>
                            <input type="number" name="criterion4" id="presentation-grade" class="form-control" placeholder="Βαθμός παρουσίασης" min="0" max="10" step="0.5" required>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="submit_grade" class="btn btn-success">Υποβολη Βαθμου</button>
                            <button type="button" class="btn btn-secondary" onclick="hideGradeForm()">Ακύρωση</button>
                        </div>
                    </form>
                  </div>


                  <!-- ===== Ακύρωση λόγω καθυστέρησης ===== -->
                  <form method="post" action="updateExam.php" 
                        onsubmit="return confirm('Επιβεβαιώνετε την ακύρωση λόγω καθυστέρησης;');" 
                        class="mt-2">
                  <input type="hidden" name="cancel_topic_delay_id" id="cancel-topic-delay-id">
                    
                    <div class="mb-2">
                      <label for="assembly_number" class="form-label">Αριθμός Γ.Σ.</label>
                      <input type="text" class="form-control" name="assembly_number" id="assembly_number" required>
                    </div>
                    <div class="mb-2">
                      <label for="assembly_year" class="form-label">Έτος Γ.Σ.</label>
                      <input type="text" class="form-control" name="assembly_year" id="assembly_year" required>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">Ακύρωση λόγω Καθυστέρησης</button>
                  </form>
          
              
              </div>



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
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Βρίσκουμε όλα τα thesis-card
    function getThesisData() {
        let cards = document.querySelectorAll(".thesis-card");
        let data = [];
        
        cards.forEach(card => {
            let title = card.querySelector("h3")?.innerText || "";
            let status = card.querySelector(".status-badge")?.innerText || "";
            let student = card.querySelector(".thesis-details p:nth-child(1)")?.innerText.replace("Φοιτητής: ", "") || "";
            let supervisor = card.querySelector(".thesis-details p:nth-child(2)")?.innerText.replace("Επιβλέπων: ", "") || "";
            let committee = card.querySelector(".thesis-details p:nth-child(3)")?.innerText.replace("Τριμελής: ", "") || "";
            let confirmed = card.querySelector(".thesis-details p:nth-child(4)")?.innerText.replace("Επιβεβαιώθηκε: ", "") || "";

            data.push({
                title,
                status,
                student,
                supervisor,
                committee,
                confirmed
            });
        });

        return data;
    }

    // Εξαγωγή σε CSV
    function exportCSV(data) {
        let csv = "Τίτλος;Κατάσταση;Φοιτητής;Επιβλέπων;Τριμελής;Επιβεβαιώθηκε\n";
        data.forEach(row => {
            csv += `${row.title};${row.status};${row.student};${row.supervisor};${row.committee};${row.confirmed}\n`;
        });

        let blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        let url = URL.createObjectURL(blob);
        let a = document.createElement("a");
        a.href = url;
        a.download = "thesis_list.csv";
        a.click();
    }

    // Εξαγωγή σε JSON
    function exportJSON(data) {
        let json = JSON.stringify(data, null, 2);
        let blob = new Blob([json], { type: "application/json" });
        let url = URL.createObjectURL(blob);
        let a = document.createElement("a");
        a.href = url;
        a.download = "thesis_list.json";
        a.click();
    }

    // Συνδέουμε τα κουμπιά
    document.getElementById("export-csv").addEventListener("click", (e) => {
        e.preventDefault();
        exportCSV(getThesisData());
    });

    document.getElementById("export-json").addEventListener("click", (e) => {
        e.preventDefault();
        exportJSON(getThesisData());
    });
});
</script>
<script>

  document.addEventListener("DOMContentLoaded", () => {
  const detailsModal = document.getElementById('detailsModal');

  detailsModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const topicId = button.getAttribute('data-id');

    // Γεμίζουμε όλα τα hidden inputs με το topicId
    document.getElementById('modal-topic-id').value = topicId;
    document.getElementById('cancel-topic-delay-id').value = topicId;
    document.getElementById('for-examination-topic-id').value = topicId;

    // Λοιπά πεδία
    document.getElementById('modal-title').textContent = button.getAttribute('data-title') || '';
    document.getElementById('modal-student').textContent = button.getAttribute('data-student') || '';
    document.getElementById('modal-supervisor').textContent = button.getAttribute('data-supervisor') || '';
    document.getElementById('modal-committee').textContent = button.getAttribute('data-committee') || '';
    document.getElementById('modal-confirmed').textContent = button.getAttribute('data-confirmed') || '';

    const deadline = button.getAttribute('data-deadline') || '';
    document.getElementById('modal-deadline').value = deadline;

    detailsModal.querySelector("button[name='cancel_topic_id']").value = topicId;

    document.getElementById('activate-grading-btn').onclick = function() {
        document.getElementById('modal-activate-topic-id').value = topicId;
    };

  });
});

detailsModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const topicId = button.getAttribute('data-id');

    document.getElementById('modal-grades').innerHTML = 'Φόρτωση...';

    fetch(`?action=get_grades&topic_id=${topicId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('modal-grades').innerHTML = data || '—';
        })
        .catch(err => {
            console.error(err);
            document.getElementById('modal-grades').innerHTML = 'Σφάλμα φόρτωσης';
        });
});


  function goToPage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
  }

  // Grade form functions
  function showGradeForm(topicId) {
    document.getElementById('grade-topic-id').value = topicId;
    document.getElementById('grade-form-section').style.display = 'block';
    // Scroll to the form
    document.getElementById('grade-form-section').scrollIntoView({ behavior: 'smooth' });
  }

  function hideGradeForm() {
    document.getElementById('grade-form-section').style.display = 'none';
    // Clear form
    document.getElementById('grade-form').reset();
  }

  // Update the activate grading button to show the grade form instead
  document.addEventListener('DOMContentLoaded', function() {
    const activateGradingBtn = document.getElementById('activate-grading-btn');
    if (activateGradingBtn) {
      activateGradingBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const topicId = document.getElementById('modal-activate-topic-id').value;
        showGradeForm(topicId);
      });
    }
  });
</script>
</body>
</html>