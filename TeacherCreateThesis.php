<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

// ------------------ Insert Νέου Θέματος ------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $summary = $_POST['description']; // textarea από τη φόρμα

    $pdf_path = null;

    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = uniqid() . "-" . basename($_FILES["pdf_file"]["name"]);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_file)) {
            $pdf_path = $target_file;
        } else {
            $message = "⚠️ Αποτυχία στην αποθήκευση του αρχείου PDF.";
        }
    }

    $stmt = $db->prepare("INSERT INTO topics (title, summary, pdf_path, teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $summary, $pdf_path, $teacher_id);

    if ($stmt->execute()) {
        $message = "✅ Το θέμα καταχωρήθηκε με επιτυχία!";
    } else {
        $message = "❌ Σφάλμα κατά την καταχώρηση.";
    }
}

// ------------------ Εμφάνιση Ονόματος Καθηγητή ------------------
$teacherName = "";
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacherName = $row['name'] . " " . $row['surname'];
}

// ------------------ Φέρνουμε τα θέματα του καθηγητή ------------------
$stmt = $db->prepare("SELECT * FROM topics WHERE teacher_id = ? AND status='available' ORDER BY created_at DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$topicsResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Θεμάτων Διπλωματικών</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/TeacherCreateThesis.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='TeacherProfile.php'">
        <div class="user-name">
          <?= htmlspecialchars($teacherName) ?>
        </div>
        <div class="name-separator"></div>
        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="TeacherDashboard.php"><img src="icons/menu.png" class="nav-icon"> Dashboard</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherStats.php"><img src="icons/stats.png" class="nav-icon"> Στατιστικα</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherCreateThesis.php" class="active"><img src="icons/file.png" class="nav-icon"> Θεματα ΔΕ</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherThesisList.php"><img src="list.png" class="nav-icon"> Λιστα ΔΕ</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherInvites.php"><img src="icons/invitation.png" class="nav-icon"> Προσκλησεις</a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherNotes.php">
              <img src="list.png" alt="Thesis List" class="nav-icon">
              Οι σημειώσεις μου
            </a>
          </li>
          <div class="nav-separator"></div>
          <li class="nav-spacing">
            <a href="TeacherSettings.php"><img src="icons/setting.png" class="nav-icon"> Ρυθμισεις</a>
          </li>
          <li class="nav-spacing">
            <a href="logout.php" class="logout"><img src="icons/logout.png" class="nav-icon"> Αποσυνδεση</a>
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
        <header class="header">
          <div class="header-title">Θέματα Διπλωματικών</div>
        </header>
        <hr class="hr">

        <!-- Success/Error message -->
        <?php if (!empty($message)): ?>
          <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <!-- Form -->
        <div class="create-topic">
          <h2>Δημιουργία Νέου Θέματος</h2>
          <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
              <label for="title">Τίτλος Θέματος:</label>
              <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
              <label for="description">Περιγραφή:</label>
              <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group file-upload-submit-row">
              <div class="form-group">
                <label for="pdf_file">Αρχείο PDF:</label>
                <input type="file" id="pdf_file" name="pdf_file" accept=".pdf">
              </div>
              <button type="submit" class="create-topic-btn">Δημιουργία Θέματος</button>
            </div>
          </form>
        </div>

        <!-- Topics List -->
        
       <div class="topics-list mt-4">
  <h2>Τα Θέματά μου</h2>
  <?php while ($row = $topicsResult->fetch_assoc()): ?>
    <div class="topic-card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><?= htmlspecialchars($row['summary']) ?></p>
      <?php if ($row['pdf_path']): ?>
        <a href="<?= $row['pdf_path'] ?>" target="_blank">Προβολή PDF</a>
      <?php endif; ?>
      <div class="text-muted small"><?= $row['created_at'] ?></div>

      <!-- edit icon με data attributes -->
      <i class="fas fa-edit edit-icon"
         data-bs-toggle="modal"
         data-bs-target="#editTopicModal"
         data-id="<?= $row['id'] ?>"
         data-title="<?= htmlspecialchars($row['title']) ?>"
         data-summary="<?= htmlspecialchars($row['summary']) ?>"
         data-pdf="<?= $row['pdf_path'] ?>"></i>
    </div>
  <?php endwhile; ?>
</div>

<!-- Bootstrap Modal -->
<div class="modal fade" id="editTopicModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="update_topic.php" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Επεξεργασία Θέματος</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit-id">

        <div class="mb-3">
          <label for="edit-title" class="form-label">Τίτλος</label>
          <input type="text" class="form-control" name="title" id="edit-title" required>
        </div>

        <div class="mb-3">
          <label for="edit-summary" class="form-label">Περίληψη</label>
          <textarea class="form-control" name="summary" id="edit-summary" rows="4" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">PDF</label>
          <input type="file" class="form-control" name="pdf">
          <div id="current-pdf" class="mt-2"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Άκυρο</button>
        <button type="submit" class="btn btn-primary">Αποθήκευση</button>
      </div>
    </form>
  </div>
</div>

      </div>
      
    </div>
  </div>
</div>




<script>
document.addEventListener("DOMContentLoaded", function() {
  var editModal = document.getElementById('editTopicModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;

    var id = button.getAttribute('data-id');
    var title = button.getAttribute('data-title');
    var summary = button.getAttribute('data-summary');
    var pdf = button.getAttribute('data-pdf');

    // Βάζουμε τιμές στη φόρμα
    editModal.querySelector('#edit-id').value = id;
    editModal.querySelector('#edit-title').value = title;
    editModal.querySelector('#edit-summary').value = summary;

    var pdfDiv = editModal.querySelector('#current-pdf');
    pdfDiv.innerHTML = pdf ? `<a href="${pdf}" target="_blank">Τρέχον PDF</a>` : "<em>Δεν υπάρχει PDF</em>";
  });
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap JS (με Popper) -->
<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* Debug helper — βάλτο πριν από οποιοδήποτε άλλο custom script */
(function(){
  // global error logger
  window.addEventListener('error', function(e){
    console.error('Global JS error:', e.message, 'at', e.filename + ':' + e.lineno);
  });

  // safety: ensure handlers εκτελούνται αφού φορτώσει το DOM
  document.addEventListener('DOMContentLoaded', function () {
    // Εξασφαλίζουμε ότι τα topic-card έχουν clickable children
    document.querySelectorAll('.topic-card').forEach(el => {
      el.style.pointerEvents = 'auto';
    });

    // Event delegation — δουλεύει πάντα ακόμα κι αν icons προστίθενται δυναμικά
    document.addEventListener('click', function (ev) {
      // ψάχνουμε για <i class="edit-icon"> ή κουμπί με class .edit-trigger
      const trigger = ev.target.closest('.edit-icon, .edit-trigger');
      if (!trigger) return;

      ev.preventDefault();
      ev.stopPropagation();

      const topicId = trigger.dataset.topicId;
      console.log('DEBUG: edit clicked — topicId=', topicId, 'element=', trigger);

      // Γέμισμα προσωρινό (βλέπουμε αν ανοίγει modal άμεσα)
      try {
        const idField = document.getElementById('edit_topic_id');
        const titleField = document.getElementById('edit_title');
        const summaryField = document.getElementById('edit_summary');
        const currentPdf = document.getElementById('current_pdf');

        if (idField) idField.value = topicId || '';
        if (titleField) titleField.value = 'DEBUG TEST - title for ' + (topicId||'?');
        if (summaryField) summaryField.value = 'DEBUG TEST - summary';
        if (currentPdf) currentPdf.textContent = 'DEBUG: temporary content';

        if (typeof bootstrap === 'undefined') {
          console.warn('Bootstrap JS not found (window.bootstrap is undefined). The modal cannot be shown by bootstrap.');
          alert('Debug: Το Bootstrap JS φαίνεται να μην είναι φορτωμένο. Έλεγξε console/network.');
          return;
        }
        // show modal immediately
        const modalEl = document.getElementById('editModal');
        if (!modalEl) {
          console.error('Debug: δεν βρέθηκε #editModal στο DOM');
        } else {
          const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.show();
        }

      } catch (err) {
        console.error('Debug fill modal error:', err);
      }

      // τώρα δοκιμάζουμε το fetch (αν υπάρχει get_topic.php) — αλλά δεν εξαρτόμαστε από αυτό
      fetch('get_topic.php?id=' + encodeURIComponent(topicId), { cache: 'no-store' })
        .then(resp => {
          console.log('DEBUG fetch status:', resp.status, resp.statusText);
          // try to parse JSON safely
          return resp.text().then(text => {
            console.log('DEBUG fetch raw response text:', text);
            try {
              return JSON.parse(text);
            } catch (err) {
              throw new Error('Invalid JSON response: ' + err.message);
            }
          });
        })
        .then(data => {
          console.log('DEBUG fetch parsed JSON:', data);
          // αν επιτυγχάνει, αντικαθιστούμε τα πεδία με πραγματικά δεδομένα
          if (data && typeof data === 'object') {
            if (document.getElementById('edit_title')) document.getElementById('edit_title').value = data.title || '';
            if (document.getElementById('edit_summary')) document.getElementById('edit_summary').value = data.summary || '';
            if (document.getElementById('current_pdf')) {
              document.getElementById('current_pdf').innerHTML = data.pdf_path
                ? `<a href="${data.pdf_path}" target="_blank">Τρέχον PDF</a>`
                : 'Δεν υπάρχει PDF';
            }
          }
        })
        .catch(err => {
          console.warn('DEBUG fetch error (get_topic.php):', err);
        });
    }); // document click
  }); // DOMContentLoaded
})(); // IIFE
</script>-->


</body>
</html>
