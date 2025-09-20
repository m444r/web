<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

// Handle note deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['note_id'])) {
    $note_id = (int)$_POST['note_id'];
    
    try {
        $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $note_id, $teacher_id);
        
        if ($stmt->execute()) {
            $message = "Η σημείωση διαγράφηκε επιτυχώς!";
        } else {
            $message = "Σφάλμα κατά τη διαγραφή της σημείωσης.";
        }
    } catch (Exception $e) {
        $message = "Σφάλμα κατά τη διαγραφή της σημείωσης.";
        error_log("Note delete error: " . $e->getMessage());
    }
}

// Note creation is now handled by addNote.php

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

// Get teacher's theses for note association (confirmed and for examination topics)
$theses = [];
    $stmt = $db->prepare("
        SELECT 
            t.id as thesis_id, 
            t.title, 
            u.name as student_name, 
            u.surname as student_surname
        FROM topics t
        LEFT JOIN users u ON u.id = t.assigned_to
        WHERE (t.teacher_id = ? OR t.id IN (
            SELECT cr.topic_id 
            FROM committee_requests cr 
            WHERE cr.teacher_id = ? AND cr.status = 'accepted'
        ))
        AND t.status IN ('confirmed', 'for examination')
        ORDER BY t.assigned_time DESC, t.id DESC
    ");
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $theses[] = $row;
}

// Get teacher's notes
$notes = [];
try {
    // First, let's check what columns exist in the notes table
    $stmt = $db->prepare("DESCRIBE notes");
    $stmt->execute();
    $result = $stmt->get_result();
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Debug: Log the columns we found
    error_log("Notes table columns: " . implode(', ', $columns));
    
    // Also show this info on the page for debugging
    $debug_info = "Notes table columns: " . implode(', ', $columns);
    
    // Use the correct column names from your database
    $author_column = 'author_id';  // Your table uses author_id
    $thesis_column = 'thesis_id';    // Your table uses thesis_id
    $content_column = 'content';  // Your table uses content
    
    $stmt = $db->prepare("
        SELECT n.*, t.title as thesis_title, u.name as student_name, u.surname as student_surname
        FROM notes n
        LEFT JOIN topics t ON t.id = n.topic_id
        LEFT JOIN users u ON u.id = t.assigned_to
        WHERE n.teacher_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->bind_param("i", $teacher_id);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $note_count = 0;
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
        $note_count++;
    }
    error_log("Retrieved $note_count notes from database");
} catch (Exception $e) {
    // If notes table doesn't exist or has issues, create empty array
    $notes = [];
    error_log("Notes table error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σημειώσεις - Teacher Notes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/TeacherNotes.css?v=<?php echo time(); ?>">
    <style>
        .notification.info {
            background: #17a2b8;
        }
        
        .notes-section {
            margin-top: 3rem;
        }
        
        .notes-header {
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notes-navigation {
            display: flex;
            gap: 8px;
        }
        
        .nav-arrow {
            background: none;
            border: none;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #333;
        }
        
        .nav-arrow:hover {
            color: #000;
            transform: translateY(-1px);
        }
        
        .nav-arrow.disabled {
            color: #ccc;
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .nav-arrow.disabled:hover {
            color: #ccc;
            transform: none;
        }
        
        .nav-arrow i {
            font-size: 16px;
        }
        
        .notes-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .note-card {
            width: 100%;
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
            <a href="TeacherNotes.php" class="active">
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

      <!-- Notes Content -->
      <div class="container">
        <header>
            <h1>Σημειώσεις</h1>
        </header>
        <hr class="hr">

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-info alert-top"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- New Note Section -->
        <div class="notes-section">
            <h2>Νέα Σημείωση</h2>
            
            <form class="note-form" method="POST" action="../addNote.php">
                <div class="form-group">
                    <label for="note-title">Τίτλος:</label>
                    <input type="text" id="note-title" name="note_title" placeholder="Εισάγετε τίτλο σημείωσης..." required>
                </div>
                <div class="form-group">
                    <label for="note-content">Περιεχόμενο:</label>
                    <textarea id="note-content" name="note_text" rows="6" placeholder="Γράψτε τη σημείωσή σας εδώ..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="thesis-select">Σχετική Διπλωματική:</label>
                    <select id="thesis-select" name="topic_id" required>
                        <option value="">Επιλέξτε διπλωματική...</option>
                        <?php foreach ($theses as $thesis): ?>
                            <option value="<?= $thesis['thesis_id'] ?>">
                                <?= htmlspecialchars($thesis['title'] . ' - ' . $thesis['student_name'] . ' ' . $thesis['student_surname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Αποθήκευση
                    </button>
                    <button type="reset" class="btn-clear">
                        <i class="fas fa-eraser"></i> Καθαρισμός
                    </button>
                </div>
            </form>
        </div>

        <!-- Notes List Section -->
        <div class="notes-section">
            <div class="notes-header">
                <h2>Οι Σημειώσεις μου</h2>
                <div class="notes-navigation">
                    <button class="nav-arrow prev-arrow" onclick="previousNotes()">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="nav-arrow next-arrow" onclick="nextNotes()">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <div class="notes-list" id="notes-list">
                <!-- Notes will be loaded here by JavaScript -->
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

  // Load notes on page load
  document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn) {
      mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('show');
      });
    }
  });


  function editNote(noteId) {
    // Find the note in the current page
    const noteCard = document.querySelector(`[onclick*="editNote(${noteId})"]`).closest('.note-card');
    const noteContent = noteCard.querySelector('.note-content').textContent;
    const noteTitle = noteCard.querySelector('h3').textContent;
    
    // Fill the form with the note data
    document.getElementById('note-title').value = noteTitle;
    document.getElementById('note-content').value = noteContent;
    
    // Scroll to the form
    document.querySelector('.note-form').scrollIntoView({ behavior: 'smooth' });
    
    // Show notification
    showNotification('Σημείωση φορτώθηκε για επεξεργασία. Αφού κάνετε τις αλλαγές, πατήστε "Αποθήκευση".', 'info');
  }

  function deleteNote(noteId) {
    if (confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή τη σημείωση;')) {
      // Create a form to submit the delete request
      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'delete';
      
      const noteIdInput = document.createElement('input');
      noteIdInput.type = 'hidden';
      noteIdInput.name = 'note_id';
      noteIdInput.value = noteId;
      
      form.appendChild(actionInput);
      form.appendChild(noteIdInput);
      document.body.appendChild(form);
      form.submit();
    }
  }

  let currentPage = 1;
  const notesPerPage = 4;
  let allNotes = [];

  function previousNotes() {
    if (currentPage > 1) {
      currentPage--;
      displayNotes();
    }
  }

  function nextNotes() {
    const totalPages = Math.ceil(allNotes.length / notesPerPage);
    if (currentPage < totalPages) {
      currentPage++;
      displayNotes();
    }
  }

  function updateNavigationArrows() {
    const totalPages = Math.ceil(allNotes.length / notesPerPage);
    const prevArrow = document.querySelector('.prev-arrow');
    const nextArrow = document.querySelector('.next-arrow');
    
    // Update previous arrow
    if (currentPage <= 1) {
      prevArrow.classList.add('disabled');
    } else {
      prevArrow.classList.remove('disabled');
    }
    
    // Update next arrow
    if (currentPage >= totalPages) {
      nextArrow.classList.add('disabled');
    } else {
      nextArrow.classList.remove('disabled');
    }
  }

  function displayNotes() {
    const startIndex = (currentPage - 1) * notesPerPage;
    const endIndex = startIndex + notesPerPage;
    const notesToShow = allNotes.slice(startIndex, endIndex);
    
    const notesList = document.getElementById('notes-list');
    if (notesToShow.length === 0) {
      notesList.innerHTML = '<div class="no-notes"><p>Δεν έχετε δημιουργήσει ακόμα σημειώσεις.</p></div>';
    } else {
      notesList.innerHTML = notesToShow.map(note => `
        <div class="note-card">
          <div class="note-header">
            <h3>${note.note_text ? note.note_text.substring(0, 50) + (note.note_text.length > 50 ? '...' : '') : 'Σημείωση'}</h3>
            <div class="note-actions">
              <button class="btn-delete" onclick="deleteNote(${note.id})">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <div class="note-content">
            ${note.note_text ? note.note_text.replace(/\n/g, '<br>') : ''}
          </div>
          <div class="note-footer">
            <span class="note-date">${new Date(note.created_at).toLocaleDateString('el-GR')} ${new Date(note.created_at).toLocaleTimeString('el-GR', {hour: '2-digit', minute: '2-digit'})}</span>
            ${note.thesis_title ? `<span class="note-category">${note.thesis_title}</span>` : ''}
          </div>
        </div>
      `).join('');
    }
    
    // Update navigation arrows after displaying notes
    updateNavigationArrows();
  }

  // Load notes on page load
  document.addEventListener('DOMContentLoaded', function() {
    // Get all notes from PHP and convert to JavaScript array
    allNotes = <?= json_encode($notes) ?>;
    displayNotes();
    
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

  function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
      notification.remove();
    }, 3000);
  }
</script>
</body>
</html>
