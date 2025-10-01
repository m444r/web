<?php
require_once "config.php"; // mysqli $db connection

// --- Διαβάζουμε παραμέτρους
$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;
$format = strtolower($_GET['format'] ?? 'html');

// validate dates
$startDate = $start && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) ? $start : '2000-01-01';
$endDate   = $end && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) ? $end : date('Y-m-d');

// --- Παράδειγμα query
$sql = "
    SELECT t.id, t.title, t.exam_datetime, t.exam_location,
           s.name AS student_name, s.surname AS student_surname,
           sup.name AS supervisor_name, sup.surname AS supervisor_surname
    FROM topics t
    LEFT JOIN users s ON t.assigned_to = s.id
    LEFT JOIN users sup ON t.teacher_id = sup.id
    WHERE DATE(t.exam_datetime) BETWEEN ? AND ?
    ORDER BY t.exam_datetime ASC
";


$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'student' => trim(($row['student_name'] ?? '') . " " . ($row['student_surname'] ?? '')),
        'supervisor' => trim(($row['supervisor_name'] ?? '') . " " . ($row['supervisor_surname'] ?? '')),
        'date' => $row['presentation_date'],
        'room' => $row['room'] ?? '-'
    ];
}

// --- Κατέβασμα JSON
if ($format === 'json') {
    $filename = "announcements_" . date("Ymd_His") . ".json";
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($announcements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Κατέβασμα XML
if ($format === 'xml') {
    $filename = "announcements_" . date("Ymd_His") . ".xml";
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $xml = new SimpleXMLElement('<announcements/>');

    foreach ($announcements as $a) {
        $item = $xml->addChild('announcement');
        foreach ($a as $key => $value) {
            $item->addChild($key, htmlspecialchars($value));
        }
    }
    echo $xml->asXML();
    exit;
}

// --- Default: HTML προβολή
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title>Ανακοινώσεις Παρουσίασης ΔΕ</title>
  <link rel="stylesheet" href="announcements.css">
</head>
<body>
  <div class="container">
    <h1>Ανακοινώσεις Παρουσίασης ΔΕ</h1>
    <a href="login_page.php" class="login-btn">Login</a>
    <?php if (empty($announcements)): ?>
      <p>Δεν βρέθηκαν ανακοινώσεις στο επιλεγμένο διάστημα.</p>
    <?php else: ?>
      <ul class="announcements">
        <?php foreach ($announcements as $a): ?>
          <li class="announcement">
            <h3><?= htmlspecialchars($a['title']) ?></h3>
            <p><strong>Φοιτητής:</strong> <?= htmlspecialchars($a['student']) ?></p>
            <p><strong>Επιβλέπων:</strong> <?= htmlspecialchars($a['supervisor']) ?></p>
            <p><strong>Ημερομηνία:</strong> <?= htmlspecialchars($a['date']) ?></p>
            <p><strong>Αίθουσα:</strong> <?= htmlspecialchars($a['room']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</body>
</html>
