<?php
session_start();
require_once "../config.php";


$debug = true;


if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.php");
    exit();
}


if (!isset($db) || !$db) {
    die("Database connection not established.");
}


$statusFilter     = $_GET['status'] ?? 'all';
$departmentFilter = $_GET['department'] ?? 'all';
$search           = trim($_GET['search'] ?? '');


$status_map = [
    'active'     => ["confirmed", "available"],
    'examination'=> ["for examination"],
    'completed'  => ["completed"],
];


$deptColumn = null;
$colRes = $db->query("SHOW COLUMNS FROM users LIKE 'department'");
if ($colRes && $colRes->num_rows > 0) {
    $deptColumn = 'department';
} else {
    $colRes2 = $db->query("SHOW COLUMNS FROM users LIKE 'city'");
    if ($colRes2 && $colRes2->num_rows > 0) {
        $deptColumn = 'city';
    }
}


$sql = "
    SELECT
      t.id,
      t.title,
      t.status,
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
      s.city AS student_city
    FROM topics t
    LEFT JOIN users s  ON t.assigned_to = s.id
    LEFT JOIN users sup ON t.teacher_id  = sup.id
    WHERE 1=1
";


$types = "";
$params = [];


if ($statusFilter !== 'all') {
    if (isset($status_map[$statusFilter])) {
        $placeholders = implode(',', array_fill(0, count($status_map[$statusFilter]), '?'));
        $sql .= " AND t.status IN ($placeholders)";
        foreach ($status_map[$statusFilter] as $val) {
            $types .= "s";
            $params[] = $val;
        }
    } else {
        $sql .= " AND t.status = ?";
        $types .= "s";
        $params[] = $statusFilter;
    }
}


if ($departmentFilter !== 'all' && $deptColumn !== null) {
    $sql .= " AND s.$deptColumn = ?";
    $types .= "s";
    $params[] = $departmentFilter;
}


if (!empty($search)) {
    $sql .= " AND (t.title LIKE CONCAT('%', ?, '%') OR s.name LIKE CONCAT('%', ?, '%') OR s.surname LIKE CONCAT('%', ?, '%'))";
    $types .= "sss";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$sql .= " AND t.status IN ('confirmed','available','for examination')";

$sql .= " ORDER BY start_date DESC, t.id DESC";

$stmt = $db->prepare($sql);
if ($stmt === false) {
    die($debug ? "SQL Prepare Error: " . htmlspecialchars($db->error) : "Database error.");
}
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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
    .thesis-list { display:flex; flex-direction:column; gap:1rem; margin-top:1rem; }
    .thesis-card { border:1px solid #e0e0e0; padding:1rem; border-radius:8px; background:#fff; }
    .thesis-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; }
    .status-badge { padding:0.25rem 0.5rem; border-radius:0.25rem; font-size:0.9rem; }
    .status-badge.confirmed, .status-badge.available { background:#e6f4ea; color:#0b6623; }
    .status-badge.for-examination { background:#fff3cd; color:#856404; }
    .status-badge.completed { background:#e9ecef; color:#333; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        <img src="../icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='SecretaryProfile.html'">
        <div class="user-name">Γραμματεία</div>
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing"><a href="SecretaryDashboard.php"><img src="../icons/menu.png" class="nav-icon">Dashboard</a></li>
          <li class="nav-spacing"><a href="SecretaryThesis.php" class="active"><img src="../icons/file.png" class="nav-icon">Προβολή ΔΕ</a></li>
          <li class="nav-spacing"><a href="SecretaryDataInput.html"><img src="../icons/graph.png" class="nav-icon">Εισαγωγή δεδομένων</a></li>
          <li class="nav-spacing"><a href="SecretaryManageThesis.php"><img src="../icons/stats.png" class="nav-icon">Διαχείριση ΔΕ</a></li>
          <div class="nav-separator"></div>
          <li class="nav-spacing"><a href="SecretaryProfile.html"><img src="../icons/setting.png" class="nav-icon">Προφίλ</a></li>
          <li class="nav-spacing"><a href="../login_page.php" class="logout"><img src="../icons/logout.png" class="nav-icon">Αποσύνδεση</a></li>
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
              <option value="all" <?= $statusFilter==='all' ? 'selected' : '' ?>>Όλες</option>
              <option value="active" <?= $statusFilter==='active' ? 'selected' : '' ?>>Ενεργές</option>
              <option value="examination" <?= $statusFilter==='examination' ? 'selected' : '' ?>>Υπό Εξέταση</option>
            </select>
          </div>

          
          <div class="col-md-4">
            <label for="search-input" class="form-label">Αναζήτηση</label>
            <input type="text" name="search" id="search-input" class="form-control"
              placeholder="Αναζήτηση τίτλου ή ονόματος φοιτητή..."
              value="<?= htmlspecialchars($search) ?>">
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
                $startDisplay = ($startTime && $startTime > 0) ? date("d/m/Y", $startTime) : "— Δεν έχει ανατεθεί —";
                $daysPassed = ($startTime && $startTime > 0) ? floor((time() - $startTime) / 86400) : "-";

                $committee = [];
                if ($cstmt) {
                    $cstmt->bind_param("i", $topicId);
                    $cstmt->execute();
                    $cres = $cstmt->get_result();
                    while ($c = $cres->fetch_assoc()) $committee[] = $c;
                }
              ?>
              <div class="thesis-card">
                <div class="thesis-header">
                  <h5 class="mb-0"><?= htmlspecialchars($row['title'] ?: '– χωρίς τίτλο –') ?></h5>
                  <span class="status-badge <?= str_replace(' ', '-', htmlspecialchars($row['status'])) ?>">
                    <?php
                      $label = $row['status'];
                      if (in_array($label, ['confirmed', 'available'])) $label = 'Ενεργή';
                      elseif ($label === 'for examination') $label = 'Υπό Εξέταση';
                      elseif ($label === 'completed') $label = 'Περατωμένη';
                      elseif ($label === 'cancelled') $label = 'Ακυρωμένη';
                      elseif ($label === 'awaiting_committee') $label = 'Υπό Ανάθεση';
                      echo htmlspecialchars(mb_strtoupper($label, 'UTF-8'));
                    ?>
                  </span>
                </div>

                <div class="thesis-details mt-2">
                  <p><strong>Φοιτητής:</strong> <?= htmlspecialchars(($row['student_name'] ?? '-') . ' ' . ($row['student_surname'] ?? '')) ?></p>
                  <p><strong>Επιβλέπων:</strong> <?= htmlspecialchars(($row['supervisor_name'] ?? '-') . ' ' . ($row['supervisor_surname'] ?? '')) ?></p>
                  <p><strong>Τμήμα:</strong> <?= htmlspecialchars($row['student_city'] ?? '-') ?></p>
                  <p><strong>Ημερομηνία Ανάθεσης:</strong> <?= $startDisplay ?></p>
                  <p><strong>Χρόνος που έχει περάσει:</strong> <?= $daysPassed === "-" ? "-" : $daysPassed . " ημέρες" ?></p>
                </div>

                <div class="committee-info mt-3">
                  <h6>Μέλη Τριμελούς Επιτροπής:</h6>
                  <ul>
                    <?php if (!empty($committee)): ?>
                      <?php foreach ($committee as $m): ?>
                        <li><?= htmlspecialchars($m['name'] . ' ' . $m['surname'] . ' (' . $m['role'] . ')') ?></li>
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
if ($cstmt) $cstmt->close();
if ($result) $result->free();
$stmt->close();
$db->close();
?>