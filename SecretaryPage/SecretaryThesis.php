<?php
session_start();
require_once "../config.php"; // adjust path if needed

// Development debug flag - set to false on production
$debug = true;

// Protect page: only secretary allowed
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.html");
    exit();
}

// === Collect filters ===
$statusFilter     = $_GET['status'] ?? 'all';       // ui values: all | active | examination | completed
$departmentFilter = $_GET['department'] ?? 'all';   // ui values: all | informatics | engineering (you may adapt)
$search           = trim($_GET['search'] ?? '');

// === Map UI status values to DB status values (topics.status enum from your dump) ===
// DB possible statuses include: 'completed','for_grade','available','confirmed','cancelled','for examination','awaiting_committee'
$status_map = [
    'active'     => ["confirmed", "available"],    // treat both confirmed/available as "active"
    'examination'=> ["for examination"],
    'completed'  => ["completed"],
];

// === Build base SQL aligned to your DB schema ===
// Use assigned_time if available, otherwise created_at as start_date.
$sql = "
    SELECT
      t.id,
      t.title,
      t.status,
      -- choose assigned_time if set and not zero-datetime; otherwise created_at
      CASE
        WHEN t.assigned_time IS NULL OR t.assigned_time IN ('0000-00-00 00:00:00','0000-00-00') THEN t.created_at
        ELSE t.assigned_time
      END AS start_date,
      t.assigned_to,
      t.teacher_id,
      s.name AS student_name,
      s.surname AS student_surname,
      sup.name AS supervisor_name,
      sup.surname AS supervisor_surname,
      s.city AS student_city -- fallback for department if actual 'department' column missing
    FROM topics t
    LEFT JOIN users s  ON t.assigned_to = s.id
    LEFT JOIN users sup ON t.teacher_id  = sup.id
    WHERE 1=1
";

// === Apply status filter ===
if ($statusFilter !== 'all') {
    if (isset($status_map[$statusFilter])) {
        $vals = array_map(function($v) use ($db) { return "'".mysqli_real_escape_string($db, $v)."'"; }, $status_map[$statusFilter]);
        $sql .= " AND t.status IN (" . implode(",", $vals) . ")";
    } else {
        // If mapping missing, treat as exact match
        $sql .= " AND t.status = '" . mysqli_real_escape_string($db, $statusFilter) . "'";
    }
}

// === Department filter: detect if users.department exists; if not, fallback to users.city ====
$deptColumn = null;
$colRes = $db->query("SHOW COLUMNS FROM users LIKE 'department'");
if ($colRes && $colRes->num_rows > 0) {
    $deptColumn = 'department';
} else {
    // fallback to city (many DBs from the dump do have 'city')
    $colRes2 = $db->query("SHOW COLUMNS FROM users LIKE 'city'");
    if ($colRes2 && $colRes2->num_rows > 0) {
        $deptColumn = 'city';
    }
}

if ($departmentFilter !== 'all' && $deptColumn !== null) {
    // sanitize
    $sql .= " AND s." . $deptColumn . " = '" . mysqli_real_escape_string($db, $departmentFilter) . "'";
}

// === Search filter (title OR student name/surname) ===
if (!empty($search)) {
    $safeSearch = mysqli_real_escape_string($db, $search);
    $sql .= " AND (t.title LIKE '%$safeSearch%' OR s.name LIKE '%$safeSearch%' OR s.surname LIKE '%$safeSearch%')";
}

$sql .= " ORDER BY start_date DESC, t.id DESC";

// Execute
$result = $db->query($sql);
if (!$result && $debug) {
    echo "<pre>SQL Error: " . htmlspecialchars($db->error) . "\nSQL: " . htmlspecialchars($sql) . "</pre>";
}

// Prepare committee lookup stmt (prepared) to be used per topic
$cstmt = $db->prepare("
    SELECT u.name, u.surname, u.role
    FROM committee_requests cr
    JOIN users u ON cr.teacher_id = u.id
    WHERE cr.topic_id = ? AND cr.status = 'accepted'
");
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Προβολή ΔΕ - Γραμματεία</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/SecretaryThesis.css">
  <style>
    /* Small inline styles in case your CSS is missing while testing */
    .thesis-list { display:flex; flex-direction:column; gap:1rem; margin-top:1rem; }
    .thesis-card { border:1px solid #e0e0e0; padding:1rem; border-radius:8px; background:#fff; }
    .thesis-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; }
    .status-badge { padding:0.25rem 0.5rem; border-radius:0.25rem; font-size:0.9rem; }
    .status-badge.confirmed, .status-badge.available { background:#e6f4ea; color:#0b6623; }
    .status-badge['for examination'] { background:#fff3cd; color:#856404; }
    .status-badge.completed { background:#e9ecef; color:#333; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row flex-nowrap">
    <div class="col-auto col-md-3 col-xl-2 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container p-3">
        <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='SecretaryProfile.php'">
        <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Γραμματεία'); ?></div>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item"><a href="SecretaryDashboard.php" class="nav-link">Dashboard</a></li>
          <li class="nav-item"><a href="SecretaryThesis.php" class="nav-link active">Προβολή ΔΕ</a></li>
          <li class="nav-item"><a href="SecretaryDataInput.php" class="nav-link">Εισαγωγή δεδομένων</a></li>
          <li class="nav-item"><a href="SecretaryManageThesis.php" class="nav-link">Διαχείριση ΔΕ</a></li>
          <li class="nav-item mt-3"><a href="SecretaryProfile.php" class="nav-link">Προφίλ</a></li>
          <li class="nav-item"><a href="../logout.php" class="nav-link">Αποσύνδεση</a></li>
        </ul>
      </div>
    </div>

    <div class="col py-3">
      <div class="container">
        <header><h1>Προβολή Διπλωματικών Εργασιών</h1></header>
        <hr class="hr">

        <!-- Filters -->
        <form method="GET" id="filtersForm" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label for="status-filter" class="form-label">Κατάσταση</label>
            <select name="status" id="status-filter" class="form-select" onchange="this.form.submit()">
              <option value="all" <?php if($statusFilter==='all') echo 'selected'; ?>>Όλες</option>
              <option value="active" <?php if($statusFilter==='active') echo 'selected'; ?>>Ενεργές</option>
              <option value="examination" <?php if($statusFilter==='examination') echo 'selected'; ?>>Υπό Εξέταση</option>
              <option value="completed" <?php if($statusFilter==='completed') echo 'selected'; ?>>Ολοκληρωμένες</option>
              <option value="cancelled" <?php if($statusFilter==='cancelled') echo 'selected'; ?>>Ακυρωμένες</option>
            </select>
          </div>

          <div class="col-md-3">
            <label for="department-filter" class="form-label">Τμήμα (αν υπάρχει)</label>
            <select name="department" id="department-filter" class="form-select" onchange="this.form.submit()">
              <option value="all" <?php if($departmentFilter==='all') echo 'selected'; ?>>Όλα</option>
              <option value="informatics" <?php if($departmentFilter==='informatics') echo 'selected'; ?>>Πληροφορικής</option>
              <option value="engineering" <?php if($departmentFilter==='engineering') echo 'selected'; ?>>Μηχανολόγων</option>
            </select>
            <?php if ($deptColumn === null && $debug): ?>
              <small class="text-muted">No users.department column — filter uses fallback where possible.</small>
            <?php endif; ?>
          </div>

          <div class="col-md-4">
            <label for="search-input" class="form-label">Αναζήτηση</label>
            <input type="text" name="search" id="search-input" class="form-control" placeholder="Αναζήτηση τίτλου ή ονόματος φοιτητή..." value="<?php echo htmlspecialchars($search); ?>">
          </div>

          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Αναζήτηση</button>
          </div>
        </form>

        <!-- Thesis List -->
        <div class="thesis-list mt-4">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <?php
                $topicId = (int)$row['id'];
                $startRaw = $row['start_date'] ?? null;
                $startTime = strtotime($startRaw);
                $startDisplay = ($startTime && $startTime > 0) ? date("d/m/Y", $startTime) : "-";
                $daysPassed = ($startTime && $startTime > 0) ? floor((time() - $startTime) / 86400) : "-";

                // run prepared stmt to fetch committee members
                $committee = [];
                if ($cstmt) {
                    $cstmt->bind_param("i", $topicId);
                    $cstmt->execute();
                    $cres = $cstmt->get_result();
                    while ($c = $cres->fetch_assoc()) {
                        $committee[] = $c;
                    }
                } else if ($debug) {
                    // fallback raw query
                    $tmp = $db->query("SELECT u.name, u.surname, u.role FROM committee_requests cr JOIN users u ON cr.teacher_id = u.id WHERE cr.topic_id = $topicId AND cr.status='accepted'");
                    if ($tmp) {
                        while ($c = $tmp->fetch_assoc()) $committee[] = $c;
                    }
                }
              ?>
              <div class="thesis-card">
                <div class="thesis-header">
                  <h5 class="mb-0"><?php echo htmlspecialchars($row['title'] ?: '– χωρίς τίτλο –'); ?></h5>
                  <span class="status-badge <?php echo htmlspecialchars($row['status']); ?>">
                    <?php
                      // friendly label mapping
                      $label = $row['status'];
                      if ($row['status'] === 'confirmed' || $row['status'] === 'available') $label = 'Ενεργή';
                      elseif ($row['status'] === 'for examination') $label = 'Υπό Εξέταση';
                      elseif ($row['status'] === 'completed') $label = 'Περατωμένη';
                      elseif ($row['status'] === 'cancelled') $label = 'Ακυρωμένη';
                      elseif ($row['status'] === 'awaiting_committee') $label = 'Υπό Ανάθεση';
                      echo htmlspecialchars(mb_strtoupper($label, 'UTF-8'));
                    ?>
                  </span>
                </div>

                <div class="thesis-details mt-2">
                  <p class="mb-1"><strong>Φοιτητής:</strong> <?php echo htmlspecialchars(($row['student_name'] ?? '-') . ' ' . ($row['student_surname'] ?? '')); ?></p>
                  <p class="mb-1"><strong>Επιβλέπων:</strong> <?php echo htmlspecialchars(($row['supervisor_name'] ?? '-') . ' ' . ($row['supervisor_surname'] ?? '')); ?></p>
                  <p class="mb-1"><strong>Τμήμα:</strong> <?php echo htmlspecialchars($row['student_city'] ?? '-'); ?></p>
                  <p class="mb-1"><strong>Ημερομηνία Ανάθεσης:</strong> <?php echo $startDisplay; ?></p>
                  <p class="mb-0"><strong>Χρόνος που έχει περάσει:</strong> <?php echo htmlspecialchars($daysPassed === "-" ? "-" : $daysPassed . " ημέρες"); ?></p>
                </div>

                <div class="committee-info mt-3">
                  <h6>Μέλη Τριμελούς Επιτροπής:</h6>
                  <ul>
                    <?php if (!empty($committee)): ?>
                      <?php foreach ($committee as $m): ?>
                        <li><?php echo htmlspecialchars($m['name'] . ' ' . $m['surname'] . ' (' . $m['role'] . ')'); ?></li>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <li><em>Δεν υπάρχουν αποδεκτά μέλη</em></li>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="alert alert-info">Δεν βρέθηκαν διπλωματικές εργασίες με τα επιλεγμένα κριτήρια.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// cleanup
if ($cstmt) $cstmt->close();
?>
