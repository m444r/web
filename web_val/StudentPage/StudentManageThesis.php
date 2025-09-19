<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../register.php");
    exit;
}

$assigned_to = $_SESSION["userid"];
$message = "";

// Φέρνουμε το όνομα φοιτητή και profile picture
$studentProfilePicture = "../icons/account.png"; // Default profile picture

// Check if profile_picture column exists
$check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
$has_profile_picture = $check_column->num_rows > 0;

if ($has_profile_picture) {
    $stmt = $db->prepare("SELECT name, surname, profile_picture FROM users WHERE id = ?");
} else {
    $stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
}

$stmt->bind_param("i", $assigned_to);
$stmt->execute();
$result = $stmt->get_result();
$studentName = "";
if ($row = $result->fetch_assoc()) {
    $studentName = $row['name'] . " " . $row['surname'];
    if ($has_profile_picture) {
        $studentProfilePicture = (!empty($row['profile_picture'])) ? "../" . $row['profile_picture'] : "../icons/account.png";
    }
}



// Φόρτωση καθηγητών
$teachers = $db->query("SELECT id, name, surname FROM users WHERE role='teacher'");

// Αν ο φοιτητής έστειλε πρόσκληση
if (isset($_POST['teacher_id']) && !empty($topics)) {
    $teacher_id = intval($_POST['teacher_id']);
    $topic = $topics[0]; // Use the first topic
    
    // Έλεγξε αν έχει ξανασταλεί πρόσκληση
    $stmt = $db->prepare("SELECT id FROM committee_requests WHERE topic_id=? AND teacher_id=?");
    $stmt->bind_param("ii", $topic['id'], $teacher_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    
    if (!$exists) {
        $status = "pending";
        $stmt = $db->prepare("INSERT INTO committee_requests (topic_id, teacher_id, status) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $topic['id'], $teacher_id, $status);
        $stmt->execute();
    }
}

// Φόρτωση διπλωματικών με awaiting_committee και confirmed
$topics = [];
$topics_stmt = $db->prepare("
    SELECT t.id, t.title, t.status, t.deadline, u.name, u.surname, t.summary, t.pdf_path, t.confirmed_time, t.exam_mode, t.exam_datetime, t.exam_location, t.library_link
    FROM topics t
    JOIN users u ON t.teacher_id = u.id
    WHERE t.assigned_to = ? AND t.status IN ('awaiting_committee', 'confirmed', 'for examination', 'for_grade', 'completed')
    ORDER BY t.id DESC
");
$topics_stmt->bind_param("i", $assigned_to);
$topics_stmt->execute();
$topics_stmt->bind_result($tid, $ttitle, $tstatus, $tdeadline, $supname, $supsurname, $summary, $pdf_path, $confirmed_time, $exam_mode, $exam_datetime, $exam_location, $library_link);
while ($topics_stmt->fetch()) {
    $topics[] = [
        'id' => $tid,
        'title' => $ttitle,
        'status' => $tstatus,
        'deadline' => $tdeadline,
        'supervisor_name' => $supname,
        'supervisor_surname' => $supsurname,
        'summary' => $summary,
        'pdf_path' => $pdf_path,
        'confirmed_time' => $confirmed_time,
        'exam_mode' => $exam_mode,
        'exam_datetime' => $exam_datetime,
        'exam_location' => $exam_location,
        'library_link' => $library_link
    ];
}
$topics_stmt->close();

$student_id = $assigned_to;

// Handle exam registration
if (isset($_POST['exam_topic_id']) && isset($_POST['exam_datetime']) && isset($_POST['exam_mode'])) {
    $topic_id = intval($_POST['exam_topic_id']);
    $exam_datetime = $_POST['exam_datetime'];
    $exam_mode = $_POST['exam_mode'];
    $exam_location = $_POST['exam_location'] ?? '';
    
    $stmt = $db->prepare("UPDATE topics SET exam_mode = ?, exam_datetime = ?, exam_location = ? WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("sssii", $exam_mode, $exam_datetime, $exam_location, $topic_id, $assigned_to);
    
    if ($stmt->execute()) {
        $message = "Οι πληροφορίες εξέτασης καταχωρήθηκαν επιτυχώς!";
    } else {
        $message = "Σφάλμα κατά την καταχώρηση των πληροφοριών εξέτασης.";
    }
}

// Handle library link submission
if (isset($_POST['library_topic_id']) && isset($_POST['library_link'])) {
    $topic_id = intval($_POST['library_topic_id']);
    $library_link = $_POST['library_link'];
    
    $stmt = $db->prepare("UPDATE topics SET library_link = ? WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("sii", $library_link, $topic_id, $assigned_to);
    
    if ($stmt->execute()) {
        $message = "Ο σύνδεσμος βιβλιοθήκης καταχωρήθηκε επιτυχώς!";
    } else {
        $message = "Σφάλμα κατά την καταχώρηση του συνδέσμου βιβλιοθήκης.";
    }
}

// Αν ο φοιτητής έστειλε προσκλήσεις
if (isset($_POST['topic_id']) && isset($_POST['teacher_ids'])) {
    $topic_id = intval($_POST['topic_id']);
    $teacher_ids = $_POST['teacher_ids']; // array
    $invitations_sent = 0;

    foreach ($teacher_ids as $teacher_id) {
        $teacher_id = intval($teacher_id);

        // Έλεγχος αν υπάρχει ήδη η πρόσκληση
        $stmt = $db->prepare("SELECT id FROM committee_requests WHERE topic_id=? AND teacher_id=?");
        $stmt->bind_param("ii", $topic_id, $teacher_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();

        if (!$exists) {
            $status = "pending";
            $stmt = $db->prepare("INSERT INTO committee_requests (topic_id, teacher_id, status) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $topic_id, $teacher_id, $status);
            if ($stmt->execute()) {
                $invitations_sent++;
            }
        }
    }
    
    if ($invitations_sent > 0) {
        $message = "Στάλθηκαν $invitations_sent προσκλήσεις επιτυχώς!";
    }
    
    // Έλεγχος αν έχουν γίνει δεκτές τουλάχιστον 2 προσκλήσεις
    $stmt = $db->prepare("SELECT COUNT(*) as accepted_count FROM committee_requests WHERE topic_id = ? AND status = 'accepted'");
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['accepted_count'] >= 2) {
        // Αυτόματη ενημέρωση κατάστασης σε 'confirmed'
        $stmt = $db->prepare("UPDATE topics SET status = 'confirmed', confirmed_time = NOW() WHERE id = ?");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        
        // Κρατάμε μόνο τις πρώτες 2 αποδεκτές προσκλήσεις, ακυρώνουμε τις υπόλοιπες
        $stmt = $db->prepare("
            UPDATE committee_requests 
            SET status = 'cancelled' 
            WHERE topic_id = ? AND status = 'accepted' 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM committee_requests 
                    WHERE topic_id = ? AND status = 'accepted' 
                    ORDER BY accepted_at ASC 
                    LIMIT 2
                ) as first_two
            )
        ");
        $stmt->bind_param("ii", $topic_id, $topic_id);
        $stmt->execute();
        
        // Ακύρωση των υπόλοιπων pending προσκλήσεων
        $stmt = $db->prepare("UPDATE committee_requests SET status = 'cancelled' WHERE topic_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        
        $message = "Η διπλωματική εργασία είναι πλέον ενεργή! Κρατήθηκαν οι πρώτες 2 αποδεκτές προσκλήσεις.";
    }
}





?>




<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Διπλωματικής - Φοιτητής</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/StudentManageThesis.css">
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
            <a href="StudentDashboard.php">
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
            <a href="StudentManageThesis.php" class="active">
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

      <!-- Student Manage Thesis Content -->
      <div class="container">
        <header>
            <h1>Διαχείριση Διπλωματικής Εργασίας</h1>
        </header>
        <hr class="hr">
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

       <?php 
       // Filter out confirmed status topics before checking if we have any to display
       $displayable_topics = array_filter($topics, function($topic) {
           return $topic['status'] != 'confirmed';
       });
       ?>
       
        <?php if (!empty($topics)): ?>
        <div class="thesis-status">
       <?php foreach ($topics as $topic): ?>
        <div class="status-card">
            <?php
            $status_title = "";
            $status_class = "pending";
            switch($topic['status']) {
                case 'awaiting_committee':
                    $status_title = "Υπό Ανάθεση - Επιλογή Επιτροπής";
                    $status_class = "warning";
                    break;
                case 'for examination':
                    $status_title = "Υπό Εξέταση";
                    $status_class = "info";
                    break;
                case 'for_grade':
                    $status_title = "Υπό Βαθμολόγηση";
                    $status_class = "info";
                    break;
                case 'completed':
                    $status_title = "Περατωμένη Διπλωματική";
                    $status_class = "success";
                    break;
                default:
                    $status_title = "Διπλωματική Εργασία";
                    $status_class = "pending";
            }
            ?>
            <h3><?= $status_title ?></h3>
            
            <div class="status-info">
                <span class="status-label">Τίτλος:</span>
                <span class="status-value"><?= htmlspecialchars($topic['title']) ?></span>
            </div>

            <div class="status-info">
                <span class="status-label">Περίληψη:</span>
                <span class="status-badge"> <?= htmlspecialchars($topic['summary']) ?></span>
            </div>

            <div class="status-info">
                <span class="status-label">Επιβλέπων:</span>
                <span class="status-value"><?= htmlspecialchars($topic['supervisor_name']." ".$topic['supervisor_surname']) ?></span>
            </div>

            <div class="status-info">
                <span class="status-label">Κατάσταση:</span>
                <span class="status-badge <?= $status_class ?>">
                    <?= htmlspecialchars($topic['status']) ?>
                </span>
            </div>

            <div class="status-info">
              <span class="status-label">Προβολή PDF :</span>
              <?php if (!empty($topic['pdf_path'])): ?>
                  <a href="<?= htmlspecialchars($topic['pdf_path']) ?>" 
                    target="_blank" 
                    class="status-badge">
                      Άνοιγμα PDF
                  </a>
              <?php else: ?>
                  <span class="status-badge">Δεν έχει ανέβει PDF</span>
              <?php endif; ?>
          </div>

            <?php if (!empty($topic['confirmed_time'])): ?>
            <div class="status-info">
                <span class="status-label">Επιβεβαιώθηκε:</span>
                <span class="status-value"><?= date("d/m/Y H:i", strtotime($topic['confirmed_time'])) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($topic['deadline'])): ?>
            <div class="status-info">
                <span class="status-label">Προθεσμία:</span>
                <span class="status-value"><?= date("d/m/Y", strtotime($topic['deadline'])) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($topic['exam_datetime'])): ?>
            <div class="status-info">
                <span class="status-label">Ημερομηνία Εξέτασης:</span>
                <span class="status-value"><?= date("d/m/Y H:i", strtotime($topic['exam_datetime'])) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($topic['exam_location'])): ?>
            <div class="status-info">
                <span class="status-label">Τοποθεσία Εξέτασης:</span>
                <span class="status-value"><?= htmlspecialchars($topic['exam_location']) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($topic['library_link'])): ?>
            <div class="status-info">
                <span class="status-label">Σύνδεσμος Βιβλιοθήκης:</span>
                <a href="<?= htmlspecialchars($topic['library_link']) ?>" target="_blank" class="status-badge">Άνοιγμα Συνδέσμου</a>
            </div>
            <?php endif; ?>

            <!-- Status-based actions -->
            <div class="thesis-actions mt-3">
                <?php if ($topic['status'] == 'awaiting_committee'): ?>
                    <a href="StudentInvites.php#committee-section" class="btn btn-primary">
                        <i class="fas fa-users"></i> Επιλογή Επιτροπής
                    </a>
                <?php elseif ($topic['status'] == 'confirmed'): ?>
                    <a href="#upload-section" class="btn btn-success">
                        <i class="fas fa-upload"></i> Ανάρτηση Πρόχειρου
                    </a>
                    <a href="#exam-section" class="btn btn-info">
                        <i class="fas fa-calendar"></i> Καταχώρηση Εξέτασης
                    </a>
                <?php elseif ($topic['status'] == 'for examination'): ?>
                    <a href="#upload-section" class="btn btn-success">
                        <i class="fas fa-upload"></i> Ανάρτηση Αρχείων
                    </a>
                    <a href="#exam-section" class="btn btn-info">
                        <i class="fas fa-edit"></i> Ενημέρωση Εξέτασης
                    </a>
                <?php elseif ($topic['status'] == 'for_grade'): ?>
                    <a href="#library-section" class="btn btn-warning">
                        <i class="fas fa-link"></i> Σύνδεσμος Βιβλιοθήκης
                    </a>
                <?php elseif ($topic['status'] == 'completed'): ?>
                    <a href="#protocol-section" class="btn btn-secondary">
                        <i class="fas fa-file-alt"></i> Προβολή Πρακτικού
                    </a>
                <?php endif; ?>
            </div>
        </div>
      <?php endforeach; ?>
        <!-- Exam Registration Section -->
        <?php 
        $exam_topics = array_filter($topics, function($topic) {
            return in_array($topic['status'], ['confirmed', 'for examination']);
        });
        if (!empty($exam_topics)): ?>
        <div class="thesis-section" id="exam-section">
            <div class="status-card">
                <h3>Καταχώρηση Εξέτασης</h3>
                <p class="text-muted">Καταχωρήστε την ημερομηνία, ώρα και τοποθεσία της εξέτασης.</p>
                <form method="POST">
                    <div class="mb-3">
                        <label for="exam_topic" class="form-label">Επιλέξτε Διπλωματική</label>
                        <select name="exam_topic_id" id="exam_topic" class="form-select" required>
                            <option value="">-- Επιλέξτε Διπλωματική --</option>
                            <?php foreach ($exam_topics as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="exam_datetime" class="form-label">Ημερομηνία και Ώρα Εξέτασης</label>
                        <input type="datetime-local" name="exam_datetime" id="exam_datetime" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="exam_mode" class="form-label">Τρόπος Εξέτασης</label>
                        <select name="exam_mode" id="exam_mode" class="form-select" required>
                            <option value="">-- Επιλέξτε τρόπο εξέτασης --</option>
                            <option value="in_person">Δια ζώσης</option>
                            <option value="online">Διαδικτυακά</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="exam_location" class="form-label">Τοποθεσία/Σύνδεσμος</label>
                        <input type="text" name="exam_location" id="exam_location" class="form-control" 
                               placeholder="Αίθουσα εξέτασης ή σύνδεσμος συνδιάσκεψης">
                        <small class="form-text text-muted">Για δια ζώσης: αίθουσα εξέτασης. Για διαδικτυακά: σύνδεσμος συνδιάσκεψης.</small>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Καταχώρηση Εξέτασης</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- File Upload Section -->
        <?php 
        $upload_topics = array_filter($topics, function($topic) {
            return in_array($topic['status'], ['confirmed', 'for examination']);
        });
        if (!empty($upload_topics)): ?>
        <div class="thesis-section" id="upload-section">
            <div class="status-card">
                <h3>Ανάρτηση Αρχείων ΔΕ</h3>
                <p class="text-muted">Ανεβάστε αρχεία και υλικό για την διπλωματική σας εργασία.</p>
                
                <?php foreach($upload_topics as $row): ?>
                    <div class="topic-card mb-4">
                        <h4>Θέμα: <?= htmlspecialchars($row['title']) ?></h4>
                        
                        <?php if(!empty($row['pdf_path'])): ?>
                            <a href="<?= htmlspecialchars($row['pdf_path']) ?>" target="_blank" class="btn btn-sm btn-primary mb-3">Προβολή PDF</a>
                        <?php endif; ?>

                        <!-- File upload form -->
                        <form action="../upload_submission.php" method="POST" enctype="multipart/form-data" class="mt-2">
                            <input type="hidden" name="topic_id" value="<?= $row['id'] ?>">

                            <div class="mb-3">
                                <label for="comments_<?= $row['id'] ?>" class="form-label">Σχόλια:</label>
                                <textarea name="comments" id="comments_<?= $row['id'] ?>" class="form-control" rows="3" placeholder="Γράψε εδώ τα σχόλιά σου..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="submission_file_<?= $row['id'] ?>" class="form-label">Αρχείο:</label>
                                <input type="file" name="submission_file" id="submission_file_<?= $row['id'] ?>" accept=".pdf,.doc,.docx" class="form-control">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-success">Ανέβασμα αρχείου</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>

                <!-- Previous submissions -->
                <div class="mt-4">
                    <h4>Προηγούμενες υποβολές:</h4>
                    <?php
                    $stmt2 = $db->prepare("
                        SELECT s.file_path, s.uploaded_at, t.title, s.comments
                        FROM student_submissions s
                        INNER JOIN topics t ON s.topic_id = t.id
                        WHERE s.student_id = ? AND t.status IN ('confirmed', 'for examination')
                        ORDER BY s.uploaded_at DESC
                    ");
                    $stmt2->bind_param("i", $student_id);
                    $stmt2->execute();
                    $submissionsResult = $stmt2->get_result();

                    if($submissionsResult->num_rows > 0){
                        echo '<div class="submissions-grid">';
                        while($sub = $submissionsResult->fetch_assoc()){
                            echo '<div class="submission-card">';
                            echo '<div class="submission-title">' . htmlspecialchars($sub['title']) . '</div>';
                            if(!empty($sub['file_path'])){
                                echo 'Αρχείο: <a class="submission-link" href="'.htmlspecialchars($sub['file_path']).'" target="_blank">'.basename($sub['file_path']).'</a>';
                            } else {
                                echo '<span class="submission-meta">Δεν έχει ανέβει αρχείο</span>';
                            }
                            $comments = trim($sub['comments']) !== '' ? htmlspecialchars($sub['comments']) : '<em>Δεν υπάρχουν σχόλια.</em>';
                            echo 'Σχόλια: <div>' .  $comments . '</div>';
                            echo '<div class="submission-meta">' . date('d/m/Y H:i', strtotime($sub['uploaded_at'])) . '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo "<p>Δεν έχετε κάνει ακόμα υποβολές για αυτό το θέμα.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Library Link Section -->
        <?php 
        $library_topics = array_filter($topics, function($topic) {
            return in_array($topic['status'], ['for_grade', 'completed']);
        });
        if (!empty($library_topics)): ?>
        <div class="thesis-section" id="library-section">
            <h2>Σύνδεσμος Βιβλιοθήκης</h2>
            <p class="text-muted">Καταχωρήστε το σύνδεσμο προς το αποθετήριο της βιβλιοθήκης (Νημερτής).</p>
            <form method="POST">
                <div class="mb-3">
                    <label for="library_topic" class="form-label">Επιλέξτε Διπλωματική</label>
                    <select name="library_topic_id" id="library_topic" class="form-select" required>
                        <option value="">-- Επιλέξτε Διπλωματική --</option>
                        <?php foreach ($library_topics as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="library_link" class="form-label">Σύνδεσμος Βιβλιοθήκης</label>
                    <input type="url" name="library_link" id="library_link" class="form-control" 
                           placeholder="https://nemertes.lis.upatras.gr/..." required>
                    <small class="form-text text-muted">Σύνδεσμος προς το τελικό κείμενο της διπλωματικής στο αποθετήριο της βιβλιοθήκης.</small>
                </div>

                <button type="submit" class="btn btn-primary">Καταχώρηση Συνδέσμου</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Exam Protocol Section -->
        <?php 
        $protocol_topics = array_filter($topics, function($topic) {
            return $topic['status'] == 'completed';
        });
        if (!empty($protocol_topics)): ?>
        <div class="thesis-section" id="protocol-section">
            <h2>Πρακτικό Εξέτασης</h2>
            <p class="text-muted">Προβολή του πρακτικού εξέτασης σε μορφή HTML.</p>
            <?php foreach ($protocol_topics as $t): ?>
                <div class="protocol-card mb-3">
                    <h4><?= htmlspecialchars($t['title']) ?></h4>
                    <a href="view_exam_protocol.php?topic_id=<?= $t['id'] ?>" target="_blank" class="btn btn-secondary">
                        <i class="fas fa-file-alt"></i> Προβολή Πρακτικού
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
  <?php else: ?>
      <div class="no-thesis-card">
        <div class="no-thesis-icon">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <h4>Δεν έχετε διπλωματική εργασία</h4>
        <p>Επικοινωνήστε με τον καθηγητή σας για ανάθεση θέματος.</p>
      </div>
  <?php endif; ?>





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
