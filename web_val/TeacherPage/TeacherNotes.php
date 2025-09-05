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
    <link rel="stylesheet" href="../css/TeacherNotes.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='profile.html'">
        
        <!-- User name link -->
        <div class="user-name">
          Βαλια Παναγοπουλου
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="TeacherDashboard.html">
              <img src="../icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherStats.html">
              <img src="../icons/stats.png" alt="Statistics" class="nav-icon">
              Στατιστικα
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherCreateThesis.html">
              <img src="../icons/file.png" alt="Thesis Topics" class="nav-icon">
              Θεματα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherThesisList.html">
              <img src="../list.png" alt="Thesis List" class="nav-icon">
              Λιστα ΔΕ
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherNotes.html" class="active">
              <img src="../icons/wirte.png" alt="Notes" class="nav-icon">
              Σημειωσεις
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherAssignTopic.html">
              <img src="../icons/invitation.png" alt="Assign Topic" class="nav-icon">
              Αναθεση Θεματος
            </a>
          </li>
          <li class="nav-spacing">
            <a href="TeacherInvites.html">
              <img src="../icons/invitation.png" alt="Invitations" class="nav-icon">
              Προσκλησεις
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="settings.html">
              <img src="../icons/setting.png" alt="Settings" class="nav-icon">
              Ρυθμισεις
            </a>
          </li>
          <li class="nav-spacing">
            <a href="../login_page.html" class="logout">
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

        <!-- New Note Section -->
        <div class="notes-section">
            <h2>Νέα Σημείωση</h2>
            <div class="note-form">
                <div class="form-group">
                    <label for="note-title">Τίτλος:</label>
                    <input type="text" id="note-title" placeholder="Εισάγετε τίτλο σημείωσης...">
                </div>
                <div class="form-group">
                    <label for="note-content">Περιεχόμενο:</label>
                    <textarea id="note-content" rows="6" placeholder="Γράψτε τη σημείωσή σας εδώ..."></textarea>
                </div>
                <div class="form-group">
                    <label for="note-category">Κατηγορία:</label>
                    <select id="note-category">
                        <option value="general">Γενικές</option>
                        <option value="thesis">Διπλωματικές</option>
                        <option value="meeting">Συνάντησεις</option>
                        <option value="reminder">Υπενθυμίσεις</option>
                        <option value="personal">Προσωπικές</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button class="btn-save" onclick="saveNote()">
                        <i class="fas fa-save"></i> Αποθήκευση
                    </button>
                    <button class="btn-clear" onclick="clearForm()">
                        <i class="fas fa-eraser"></i> Καθαρισμός
                    </button>
                </div>
            </div>
        </div>

        <!-- Notes List Section -->
        <div class="notes-section">
            <div class="notes-header">
                <h2>Οι Σημειώσεις μου</h2>
                <div class="notes-filters">
                    <select id="filter-category" onchange="filterNotes()">
                        <option value="all">Όλες οι κατηγορίες</option>
                        <option value="general">Γενικές</option>
                        <option value="thesis">Διπλωματικές</option>
                        <option value="meeting">Συνάντησεις</option>
                        <option value="reminder">Υπενθυμίσεις</option>
                        <option value="personal">Προσωπικές</option>
                    </select>
                    <input type="text" id="search-notes" placeholder="Αναζήτηση σημειώσεων..." onkeyup="searchNotes()">
                </div>
            </div>
            
            <div class="notes-list" id="notes-list">
                <!-- Notes will be dynamically added here -->
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sample notes data (in a real app, this would come from a database)
  let notes = [
    {
      id: 1,
      title: "Συνάντηση με φοιτητή Μαρία",
      content: "Συζήτηση για την εξέλιξη της διπλωματικής. Χρειάζεται περισσότερη εργασία στο κεφάλαιο 3.",
      category: "meeting",
      date: "2024-01-15",
      timestamp: "14:30"
    },
    {
      id: 2,
      title: "Υπενθύμιση - Προθεσμία υποβολής",
      content: "Οι φοιτητές πρέπει να υποβάλουν το πρώτο κεφάλαιο μέχρι 20/01/2024.",
      category: "reminder",
      date: "2024-01-10",
      timestamp: "09:15"
    },
    {
      id: 3,
      title: "Ιδέες για νέα θέματα διπλωματικών",
      content: "- AI για ανάλυση δεδομένων\n- Blockchain εφαρμογές\n- IoT ασφάλεια\n- Machine Learning για προγνωστικά μοντέλα",
      category: "thesis",
      date: "2024-01-08",
      timestamp: "16:45"
    }
  ];

  // Load notes on page load
  document.addEventListener('DOMContentLoaded', function() {
    loadNotes();
    
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn) {
      mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('show');
      });
    }
  });

  function saveNote() {
    const title = document.getElementById('note-title').value.trim();
    const content = document.getElementById('note-content').value.trim();
    const category = document.getElementById('note-category').value;

    if (!title || !content) {
      alert('Παρακαλώ συμπληρώστε τίτλο και περιεχόμενο.');
      return;
    }

    const newNote = {
      id: Date.now(),
      title: title,
      content: content,
      category: category,
      date: new Date().toISOString().split('T')[0],
      timestamp: new Date().toLocaleTimeString('el-GR', { hour: '2-digit', minute: '2-digit' })
    };

    notes.unshift(newNote);
    loadNotes();
    clearForm();
    
    // Show success message
    showNotification('Η σημείωση αποθηκεύτηκε επιτυχώς!', 'success');
  }

  function clearForm() {
    document.getElementById('note-title').value = '';
    document.getElementById('note-content').value = '';
    document.getElementById('note-category').value = 'general';
  }

  function loadNotes() {
    const notesList = document.getElementById('notes-list');
    const filterCategory = document.getElementById('filter-category').value;
    const searchTerm = document.getElementById('search-notes').value.toLowerCase();

    let filteredNotes = notes;

    // Filter by category
    if (filterCategory !== 'all') {
      filteredNotes = filteredNotes.filter(note => note.category === filterCategory);
    }

    // Filter by search term
    if (searchTerm) {
      filteredNotes = filteredNotes.filter(note => 
        note.title.toLowerCase().includes(searchTerm) || 
        note.content.toLowerCase().includes(searchTerm)
      );
    }

    if (filteredNotes.length === 0) {
      notesList.innerHTML = '<div class="no-notes">Δεν βρέθηκαν σημειώσεις.</div>';
      return;
    }

    notesList.innerHTML = filteredNotes.map(note => `
      <div class="note-card" data-category="${note.category}">
        <div class="note-header">
          <h3>${note.title}</h3>
          <div class="note-actions">
            <button class="btn-edit" onclick="editNote(${note.id})">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-delete" onclick="deleteNote(${note.id})">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        <div class="note-content">${note.content.replace(/\n/g, '<br>')}</div>
        <div class="note-footer">
          <span class="note-category">${getCategoryName(note.category)}</span>
          <span class="note-date">${formatDate(note.date)} - ${note.timestamp}</span>
        </div>
      </div>
    `).join('');
  }

  function editNote(id) {
    const note = notes.find(n => n.id === id);
    if (note) {
      document.getElementById('note-title').value = note.title;
      document.getElementById('note-content').value = note.content;
      document.getElementById('note-category').value = note.category;
      
      // Remove the old note and scroll to form
      notes = notes.filter(n => n.id !== id);
      document.querySelector('.note-form').scrollIntoView({ behavior: 'smooth' });
    }
  }

  function deleteNote(id) {
    if (confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή τη σημείωση;')) {
      notes = notes.filter(note => note.id !== id);
      loadNotes();
      showNotification('Η σημείωση διαγράφηκε επιτυχώς!', 'success');
    }
  }

  function filterNotes() {
    loadNotes();
  }

  function searchNotes() {
    loadNotes();
  }

  function getCategoryName(category) {
    const categories = {
      'general': 'Γενικές',
      'thesis': 'Διπλωματικές',
      'meeting': 'Συνάντησεις',
      'reminder': 'Υπενθυμίσεις',
      'personal': 'Προσωπικές'
    };
    return categories[category] || category;
  }

  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('el-GR');
  }

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
