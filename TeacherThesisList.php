<?php
session_start();
require 'config.php'; 

$teacher_id = $_SESSION['userid']; 

$status_filter = $_GET['status'] ?? 'all';
$role_filter   = $_GET['role'] ?? 'all';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
if ($role_filter === 'committee') {
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

// --- Prepare & bind --- 
$stmt = $db->prepare($query);
if (!$stmt) {
  die("Prepare failed: " . $db->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


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
    <link rel="stylesheet" href="css/TeacherThesisList.css">
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
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='profile.php'">
        
        <!-- User name link -->
        <div class="user-name">
          <?= htmlspecialchars($teacherName) ?>
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="TeacherDashboard.php">
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
            <a href="TeacherThesisList.php" class="active">
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
                    <li>15/02/2024 - Υποβολή πρώτου κεφαλαίου</li>
                  </ul>
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

         <form method="post" action="addNote.php">
<input type="hidden" name="topic_id" value="<?= $row['id'] ?>">
    <textarea name="note_text" maxlength="300" placeholder="Προσθήκη σημείωσης..." required></textarea>
    <button type="submit">Αποθήκευση</button>
</form>



                  <button class="action-btn download-btn">
                    <i class="fas fa-download"></i> Λήψη Αρχείου
                  </button>
                </div> 
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p>Δεν βρέθηκαν διπλωματικές με τα επιλεγμένα φίλτρα.</p>
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

                  
                    <div class="mb-3">
                      <label for="deadline" class="form-label">Προθεσμία Υποβολής</label>
                      <input type="date" class="form-control" name="deadline" id="modal-deadline">
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Κλείσιμο</button>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                  </div> 

                </form>



                <!-- ===== Ακύρωση ανάθεσης ===== -->
                <form method="post" action="updateExam.php" 
                      onsubmit="return confirm('Επιβεβαιώνετε την ακύρωση της ανάθεσης;');" 
                      class="mt-2">
                  <input type="hidden" name="cancel_topic_id" id="cancel-topic-id">
                <button type="submit" name="cancel_topic_id" value="" class="btn btn-warning w-100">Ακύρωση Ανάθεσης</button>
                </form>

                <?php if (!empty($_SESSION['cancel_message'])): ?>
                  <div class="alert alert-info"><?= $_SESSION['cancel_message']; ?></div>
                  <?php unset($_SESSION['cancel_message']); ?>
                <?php endif; ?>


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
  
  <button type="submit" class="btn btn-danger w-100">Ακύρωση λόγω Καθυστέρησης</button>
</form>
 </button>
        
          <form method="post" action="updateExam.php" class="mt-2">
            <input type="hidden" name="topic_id" id="for-examination-topic-id">
            <input type="hidden" name="set_for_examination" value="1">
            <button type="submit" class="btn btn-warning w-100">Υπό Εξέταση</button>
          </form>


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
  });
});

  /*
document.addEventListener("DOMContentLoaded", () => {
  const detailsModal = document.getElementById('detailsModal');


  detailsModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;

    const topicId = button.getAttribute('data-id');
    document.getElementById('modal-topic-id').value = topicId;

    document.getElementById('modal-topic-id').value = button.getAttribute('data-id');
    document.getElementById('modal-title').textContent = button.getAttribute('data-title') || '';
    document.getElementById('modal-student').textContent = button.getAttribute('data-student') || '';
    document.getElementById('modal-supervisor').textContent = button.getAttribute('data-supervisor') || '';
    document.getElementById('modal-committee').textContent = button.getAttribute('data-committee') || '';
    document.getElementById('modal-confirmed').textContent = button.getAttribute('data-confirmed') || '';

    // 👉 Φόρτωση deadline στο input
    const deadline = button.getAttribute('data-deadline') || '';
    document.getElementById('modal-deadline').value = deadline;

    detailsModal.querySelector("button[name='cancel_topic_id']").value = topicId;
    detailsModal.querySelector("button[name='cancel_topic']").value = topicId;

    document.getElementById('modal-topic-id').value = topicId;
    document.getElementById('cancel-topic-delay-id').value = topicId; // << εδώ είναι το κρίσιμο

    document.getElementById('for-examination-topic-id').value = topicId;

    var forExamModal = document.getElementById('forExaminationModal');
forExamModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var topicId = button.getAttribute('data-topic-id');
    var input = forExamModal.querySelector('#forExamination-topic-id');
    input.value = topicId;
});

  });
});*/

</script>


</body>
</html> 
