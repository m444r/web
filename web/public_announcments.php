<?php
// announcments_page.php
// This file shows the announcements in styled HTML using announcments.css

// --- DB Connection ---
$host = "localhost";
$user = "root";      // change this
$pass = "";  // change this
$dbname = "web";    // change this

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// --- Fetch announcements (latest first) ---
$sql = "SELECT id, title, presenter, date, location, description 
        FROM announcements 
        ORDER BY date ASC";
$result = $conn->query($sql);

$announcements = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Ανακοινώσεις Διπλωματικών</title>
    <link rel="stylesheet" href="css/announcments.css">
</head>
<body>
    <!-- Top bar -->
    <div class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <div class="brand-logo">Π</div>
                <div class="brand-text">
                    <div class="brand-title">Παρουσιάσεις Διπλωματικών</div>
                    <div class="brand-tagline">Τμήμα Μηχανικών</div>
                </div>
            </div>
            <a href="login_page.php" class="login-btn">Σύνδεση</a>
        </div>
    </div>

    <!-- Container -->
    <div class="container">
        <h1>Ανακοινώσεις Παρουσίασης Διπλωματικών</h1>
        <p class="intro">Εδώ θα βρείτε όλες τις προγραμματισμένες παρουσιάσεις.</p>

        <ul class="announcements">
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $a): ?>
                    <li class="announcement">
                        <div class="announcement-header">
                            <h2 class="announcement-title"><?= htmlspecialchars($a['title']) ?></h2>
                            <span class="badge"><?= date("d/m/Y", strtotime($a['date'])) ?></span>
                        </div>
                        <div class="announcement-body">
                            <p><strong>Παρουσιαστής:</strong> <?= htmlspecialchars($a['presenter']) ?></p>
                            <p><strong>Τοποθεσία:</strong> <?= htmlspecialchars($a['location']) ?></p>
                            <p><?= nl2br(htmlspecialchars($a['description'])) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Δεν υπάρχουν προγραμματισμένες παρουσιάσεις.</p>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Footer -->
    <div class="site-footer">
        <div class="footer-inner">
            <span>© <?= date("Y") ?> Τμήμα Μηχανικών</span>
            <a href="login_page.php" class="footer-login">Σύνδεση</a>
        </div>
    </div>
</body>
</html>
