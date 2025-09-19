<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

// Handle form submission for creating new topic
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_topic'], $_POST['title'], $_POST['description'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    // Debug: Log the form submission
    error_log("Topic creation attempt - Title: " . $title . ", Description: " . $description);
    
    if (empty($title)) {
        $message = "Παρακαλώ εισάγετε τίτλο θέματος.";
    } elseif (empty($description)) {
        $message = "Παρακαλώ εισάγετε περιγραφή θέματος.";
    } else {
        // Handle file upload
        $pdf_path = null;
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
            if ($file_extension === 'pdf') {
                $filename = uniqid() . '_' . $_FILES['pdf_file']['name'];
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_path)) {
                    $pdf_path = $target_path;
                } else {
                    $message = "Σφάλμα κατά την αποθήκευση του αρχείου.";
                }
            } else {
                $message = "Μόνο αρχεία PDF επιτρέπονται.";
            }
        }
        
        if (empty($message)) {
            // Insert new topic into database
            $stmt = $db->prepare("INSERT INTO topics (title, summary, pdf_path, teacher_id, status, created_at) VALUES (?, ?, ?, ?, 'available', NOW())");
            if (!$stmt) {
                $message = "Σφάλμα στην προετοιμασία του query: " . $db->error;
            } else {
                $stmt->bind_param("sssi", $title, $description, $pdf_path, $teacher_id);
                
                if ($stmt->execute()) {
                    // Debug: Log successful insertion
                    error_log("Topic created successfully - ID: " . $db->insert_id);
                    // Redirect to refresh the page and show the new topic
                    header("Location: " . $_SERVER['PHP_SELF'] . "?created=1");
                    exit;
                } else {
                    // Debug: Log database error
                    error_log("Database error: " . $db->error);
                    $message = "Σφάλμα κατά τη δημιουργία του θέματος: " . $db->error;
                }
                $stmt->close();
            }
        }
    }
}

// Handle success/error messages from update_topic.php
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = "Το θέμα ενημερώθηκε επιτυχώς!";
} elseif (isset($_GET['created']) && $_GET['created'] == '1') {
    $message = "Το θέμα δημιουργήθηκε επιτυχώς!";
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'title_empty':
            $message = "Παρακαλώ εισάγετε τίτλο θέματος.";
            break;
        case 'summary_empty':
            $message = "Παρακαλώ εισάγετε περιγραφή θέματος.";
            break;
        case 'upload_failed':
            $message = "Σφάλμα κατά την αποθήκευση του αρχείου.";
            break;
        case 'invalid_file':
            $message = "Μόνο αρχεία PDF επιτρέπονται.";
            break;
        case 'update_failed':
            $message = "Σφάλμα κατά την ενημέρωση του θέματος.";
            break;
        default:
            $message = "Παρουσιάστηκε σφάλμα.";
    }
}

// AJAX request for getting topic data is now handled by get_topic.php

// Topic editing is now handled by update_topic.php

// Handle topic deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_topic'], $_POST['topic_id'])) {
    $topic_id = intval($_POST['topic_id']);
    
    // Debug: Log deletion attempt
    error_log("Topic deletion attempt - ID: " . $topic_id . ", Teacher ID: " . $teacher_id);
    
    // Verify the topic belongs to this teacher
    $verify_stmt = $db->prepare("SELECT id, pdf_path FROM topics WHERE id = ? AND teacher_id = ?");
    $verify_stmt->bind_param("ii", $topic_id, $teacher_id);
    $verify_stmt->execute();
    $topic = $verify_stmt->get_result()->fetch_assoc();
    
    if ($topic) {
        // Start transaction to ensure all deletions succeed or none do
        $db->begin_transaction();
        
        try {
            // Delete related records first (in order of foreign key dependencies)
            
            // Delete from committee_grades table
            $delete_grades = $db->prepare("DELETE FROM committee_grades WHERE topic_id = ?");
            $delete_grades->bind_param("i", $topic_id);
            $delete_grades->execute();
            $delete_grades->close();
            
            // Delete from committee_requests table
            $delete_requests = $db->prepare("DELETE FROM committee_requests WHERE topic_id = ?");
            $delete_requests->bind_param("i", $topic_id);
            $delete_requests->execute();
            $delete_requests->close();
            
            // Delete from notes table
            $delete_notes = $db->prepare("DELETE FROM notes WHERE topic_id = ?");
            $delete_notes->bind_param("i", $topic_id);
            $delete_notes->execute();
            $delete_notes->close();
            
            // Finally delete the topic itself
            $delete_stmt = $db->prepare("DELETE FROM topics WHERE id = ? AND teacher_id = ?");
            $delete_stmt->bind_param("ii", $topic_id, $teacher_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            // Commit the transaction
            $db->commit();
            
            // Debug: Log successful deletion
            error_log("Topic deleted successfully - ID: " . $topic_id);
            
            // Delete associated PDF file if it exists
            if (!empty($topic['pdf_path']) && file_exists($topic['pdf_path'])) {
                unlink($topic['pdf_path']);
            }
            $message = "Το θέμα διαγράφηκε επιτυχώς!";
            
        } catch (Exception $e) {
            // Rollback the transaction if any deletion fails
            $db->rollback();
            $message = "Σφάλμα κατά τη διαγραφή του θέματος: " . $e->getMessage();
        }
    } else {
        $message = "Το θέμα δεν βρέθηκε ή δεν έχετε δικαίωμα να το διαγράψετε.";
    }
    $verify_stmt->close();
}


// Pagination settings
$topics_per_page = 6;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $topics_per_page;

// Debug: Show database info on page
$debug_info = "";
$debug_count_stmt = $db->prepare("SELECT COUNT(*) as total FROM topics WHERE teacher_id = ?");
$debug_count_stmt->bind_param("i", $teacher_id);
$debug_count_stmt->execute();
$debug_total = $debug_count_stmt->get_result()->fetch_assoc()['total'];
$debug_count_stmt->close();

$debug_info .= "Total topics in DB: " . $debug_total . "<br>";
$debug_info .= "Current page: " . $current_page . "<br>";
$debug_info .= "Offset: " . $offset . "<br>";


// Get teacher name
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

// Get total count of topics
$count_stmt = $db->prepare("SELECT COUNT(*) as total FROM topics WHERE teacher_id = ?");
$count_stmt->bind_param("i", $teacher_id);
$count_stmt->execute();
$total_topics = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_topics / $topics_per_page);

// Debug: Log total topics count
error_log("Total topics in database for teacher " . $teacher_id . ": " . $total_topics);

// Debug: Get all topics for this teacher to see what's in the database
$debug_stmt = $db->prepare("SELECT id, title, created_at FROM topics WHERE teacher_id = ? ORDER BY created_at DESC");
$debug_stmt->bind_param("i", $teacher_id);
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();
$all_topics = [];
while ($row = $debug_result->fetch_assoc()) {
    $all_topics[] = $row;
}
error_log("All topics in database: " . print_r($all_topics, true));

// Get teacher's topics for display with pagination
$topics = [];
$stmt = $db->prepare("SELECT * FROM topics WHERE teacher_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $teacher_id, $topics_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Log the query parameters and results
error_log("Topics query - Teacher ID: " . $teacher_id . ", Limit: " . $topics_per_page . ", Offset: " . $offset);
$topic_count = 0;
while ($row = $result->fetch_assoc()) {
    $topics[] = $row;
    $topic_count++;
}
error_log("Found " . $topic_count . " topics for display");

// Debug: Add to debug info
$debug_info .= "Topics query executed - Found: " . $topic_count . " topics<br>";
$debug_info .= "Topics array after query: " . print_r($topics, true) . "<br>";
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
    <link rel="stylesheet" href="../css/TeacherCreateThesis.css?v=<?php echo time(); ?>">
    <style>
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            background-color: #6A90C7;
            color: white;
            border-radius: 12px 12px 0 0;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #6A90C7;
            box-shadow: 0 0 0 0.2rem rgba(106, 144, 199, 0.25);
        }
        .btn-primary {
            background-color: #6A90C7;
            border-color: #6A90C7;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #5a7fb7;
            border-color: #5a7fb7;
        }
        .btn-secondary {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        /* Pagination Styles */
        
        /* Custom scrollbar styling - invisible by default, visible on hover */
        .topics-grid::-webkit-scrollbar {
            width: 8px;
        }
        
        .topics-grid::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .topics-grid::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .topics-grid:hover::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
        }
        
        .topics-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.4);
        }
        
        /* Firefox scrollbar styling */
        .topics-grid {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
        }
        
        .topics-grid:hover {
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }
        
        .topics-pagination {
            flex-shrink: 0; /* Never shrink */
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            padding: 20px 0;
            height: 60px; /* Fixed height for pagination */
        }
        
        .pagination-btn {
            background: none;
            border: none;
            color: #6A90C7;
            font-size: 18px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background-color: #6A90C7;
            color: white;
            transform: scale(1.05);
        }
        
        .pagination-btn:disabled {
            color: #ccc;
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .pagination-info {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            min-width: 50px;
            text-align: center;
        }
        
        .alert-top {
            position: fixed;
            top: 20px;
            left: calc(16.66667% + (83.33333% / 2));
            transform: translateX(-50%);
            z-index: 1050;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .alert-top {
                left: 50%;
            }
        }
    </style>
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
            <a href="TeacherStats.php">
              <img src="../icons/stats.png" alt="Statistics" class="nav-icon">
              Στατιστικα
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherCreateThesis.php" class="active">
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

      <!-- Thesis Creation Content -->
      <div class="container">
        <header>
            <h1>Θέματα Διπλωματικών</h1>
        </header>
        <hr class="hr">
       
        <!-- Form for creating new topic -->
        <div class="create-topic">
            <h2>Δημιουργία Νέου Θέματος</h2>
            <?php if (!empty($message)): ?>
                <?php 
                $alert_class = (isset($_GET['updated']) && $_GET['updated'] == '1') ? 'alert-success' : 'alert-danger';
                ?>
                <div class="alert <?= $alert_class ?> alert-top"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Τίτλος Θέματος:</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Περιγραφή:</label>
                    <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group file-upload-submit-row">
                    <div class="form-group">
                        <label for="pdf_file">Αρχείο PDF:</label>
                        <input type="file" id="pdf_file" name="pdf_file" accept=".pdf">
                    </div>
                    <button type="submit" name="create_topic" class="create-topic-btn">Δημιουργία Θέματος</button>
                </div>
            </form>
        </div>
        
        <!-- List of existing topics -->
        <div class="topics-list">
            <div class="section-header">
                <h2>Τα Θέματά μου</h2>
            </div>
            
            <div class="topics-grid">
                
                <?php if (empty($topics)): ?>
                    <div class="no-topics">
                        <p>Δεν έχετε δημιουργήσει ακόμα θέματα διπλωματικών.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                        <div class="topic-card">
                            <div class="topic-header">
                                <h3><?= htmlspecialchars($topic['title']) ?></h3>
                            </div>
                            <i class="fas fa-edit edit-icon" data-topic-id="<?= $topic['id'] ?>" title="Επεξεργασία"></i>
                            <i class="fas fa-trash delete-icon" data-topic-id="<?= $topic['id'] ?>" title="Διαγραφή"></i>
                            <p><?= htmlspecialchars($topic['summary']) ?></p>
                            <?php if (!empty($topic['pdf_path'])): ?>
                                <?php 
                                // Extract filename from path
                                $filename = basename($topic['pdf_path']);
                                ?>
                                <a href="../view_pdf.php?file=<?= urlencode($filename) ?>" target="_blank">Προβολή PDF</a>
                            <?php endif; ?>
                            <div class="topic-status">
                                <span class="status-badge status-<?= $topic['status'] ?>">
                                    <?php
                                    switch($topic['status']) {
                                        case 'available': echo 'Διαθέσιμο'; break;
                                        case 'assigned': echo 'Ανατεθειμένο'; break;
                                        case 'archived': echo 'Αρχειοθετημένο'; break;
                                        default: echo ucfirst($topic['status']);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Topic Modal -->
<div class="modal fade" id="editTopicModal" tabindex="-1" aria-labelledby="editTopicModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editTopicModalLabel">Επεξεργασία Θέματος</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="../update_topic.php" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" id="edit_topic_id" name="id">
          <div class="form-group">
            <label for="edit_title">Τίτλος Θέματος:</label>
            <input type="text" id="edit_title" name="title" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="edit_description">Περιγραφή:</label>
            <textarea id="edit_description" name="summary" class="form-control" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label for="edit_pdf_file">Νέο Αρχείο PDF (Προαιρετικά):</label>
            <div id="current_pdf_info" class="mb-2" style="display: none;">
              <small class="text-muted">Τρέχον αρχείο: <span id="current_pdf_name"></span></small>
            </div>
            <input type="file" id="edit_pdf_file" name="pdf" class="form-control" accept=".pdf">
            <small class="form-text text-muted">Αφήστε κενό για να διατηρήσετε το υπάρχον αρχείο</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ακύρωση</button>
          <button type="submit" name="edit_topic" class="btn btn-primary">Ενημέρωση Θέματος</button>
        </div>
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

    // Handle edit icon clicks
    const editIcons = document.querySelectorAll('.edit-icon');
    editIcons.forEach(icon => {
      icon.addEventListener('click', function() {
        const topicId = this.getAttribute('data-topic-id');
        openEditModal(topicId);
      });
    });

    // Handle delete icon clicks
    const deleteIcons = document.querySelectorAll('.delete-icon');
    deleteIcons.forEach(icon => {
      icon.addEventListener('click', function() {
        const topicId = this.getAttribute('data-topic-id');
        const topicTitle = this.closest('.topic-card').querySelector('h3').textContent;
        confirmDelete(topicId, topicTitle);
      });
    });
  });

  function openEditModal(topicId) {
    // Fetch topic data from server
    fetch(`../get_topic.php?id=${topicId}`)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert('Σφάλμα: ' + data.error);
          return;
        }
        
        // Populate the modal with topic data
        document.getElementById('edit_topic_id').value = data.id;
        document.getElementById('edit_title').value = data.title;
        document.getElementById('edit_description').value = data.summary;
        
        // Show current PDF info if exists
        if (data.pdf_path) {
          const filename = data.pdf_path.split('/').pop();
          document.getElementById('current_pdf_name').textContent = filename;
          document.getElementById('current_pdf_info').style.display = 'block';
        } else {
          document.getElementById('current_pdf_info').style.display = 'none';
        }
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('editTopicModal'));
        modal.show();
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Σφάλμα κατά τη φόρτωση των δεδομένων του θέματος.');
      });
  }

  function confirmDelete(topicId, topicTitle) {
    if (confirm(`Είστε σίγουροι ότι θέλετε να διαγράψετε το θέμα "${topicTitle}";\n\nΑυτή η ενέργεια δεν μπορεί να αναιρεθεί!`)) {
      // Create a form to submit the delete request
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';
      
      const topicIdInput = document.createElement('input');
      topicIdInput.type = 'hidden';
      topicIdInput.name = 'topic_id';
      topicIdInput.value = topicId;
      
      const deleteInput = document.createElement('input');
      deleteInput.type = 'hidden';
      deleteInput.name = 'delete_topic';
      deleteInput.value = '1';
      
      form.appendChild(topicIdInput);
      form.appendChild(deleteInput);
      document.body.appendChild(form);
      form.submit();
    }
  }

  function goToPage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
  }
</script>
</body>
</html> 