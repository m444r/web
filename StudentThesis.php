<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$student_id = $_SESSION["userid"];
$message = "";

$studentName = "";
$stmt = $db->prepare("SELECT name, surname FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $studentName = $row['name'] . " " . $row['surname'];
}
$stmt->close();


// Προετοιμασμένο query για ασφάλεια
// Φέρνουμε τα topics του φοιτητή
$sql = "SELECT t.*, u.name AS teacher_name, u.surname AS teacher_surname
        FROM topics t
        JOIN users u ON u.id = t.teacher_id
        WHERE t.assigned_to = ?";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$topics = $stmt->get_result();

?>




<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διπλωματικεσ Μου</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/StudentDashboard.css">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">
    <!-- Sidebar -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar collapse d-md-block" id="sidebarMenu">
      <div class="sidebar-container">
        
        <!-- Profile pic -->
        <img src="icons/account.png" alt="Profile" class="profile-avatar" onclick="window.location.href='StudentProfile.php'">
        
        <!-- User name link -->
        <div class="user-name">
           <?= htmlspecialchars($studentName) ?>
        </div>
        
        <!-- Name separator -->
        <div class="name-separator"></div>

        <ul class="nav nav-pills flex-column mb-auto w-100">
          <li class="nav-item nav-spacing">
            <a href="StudentDashboard.php" >
              <img src="icons/menu.png" alt="Dashboard" class="nav-icon">
              Dashboard
            </a>
          </li>
          <li class="nav-item nav-spacing">
            <a href="StudentThesis.php" class="active">
              <img src="icons/menu.png" alt="Dashboard" class="nav-icon">
              Τρέχουσα Διπλωματικη
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentTopics.php">
              <img src="icons/file.png" alt="Topics" class="nav-icon">
              Ανάρτηση Αρχείων
            </a>
          </li>
          <li class="nav-spacing">
            <a href="StudentManageThesis.php">
              <img src="icons/invitation.png" alt="Manage Thesis" class="nav-icon">
              Προσκλήσεις
            </a>
          </li>
          
          <div class="nav-separator"></div>
          
          <li class="nav-spacing">
            <a href="StudentProfile.php">
              <img src="icons/setting.png" alt="Profile" class="nav-icon">
              Προφίλ
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

<!-- Main content -->
    <div class="container">
         <header>
            <h1>Τρέχουσα Διπλωματική</h1>
        </header>
        <hr class="hr">      <?php if ($topics->num_rows > 0): ?>
        <?php while ($row = $topics->fetch_assoc()): ?>
          <div class="card mb-3 p-3">
            <h5><?= htmlspecialchars($row["title"]) ?></h5>
            <p><?= htmlspecialchars($row["summary"]) ?></p>
            <p><strong>Επιβλέπων:</strong> 
                <?= htmlspecialchars($row["teacher_name"] . " " . $row["teacher_surname"]) ?>
            </p>
            <?php if (!empty($row["pdf_path"])): ?>
                <p><a href="<?= htmlspecialchars($row["pdf_path"]) ?>" target="_blank">📄 Προβολή PDF</a></p>
            <?php endif; ?>

            <!-- Confirmed time -->
            <p><strong>Ημερομηνία Επιβεβαίωσης:</strong> 
                <?= htmlspecialchars($row["confirmed_time"]) ?>
            </p>

            <!-- Επιτροπή -->
            <h6>Μέλη Επιτροπής:</h6>
            <ul>
                <?php
                $committee_sql = "SELECT cr.*, u.name, u.surname
                                    FROM committee_requests cr
                                    JOIN users u ON u.id = cr.teacher_id
                                    WHERE cr.topic_id = ? AND cr.status = 'accepted'";
                $cstmt = $db->prepare($committee_sql);
                $cstmt->bind_param("i", $row["id"]);
                $cstmt->execute();
                $committee = $cstmt->get_result();

                if ($committee->num_rows > 0):
                    while ($c = $committee->fetch_assoc()):
                ?>
                    <li>
                        <?= htmlspecialchars($c["name"] . " " . $c["surname"]) ?> 
                        (Αίτημα: <?= htmlspecialchars($c["requested_at"]) ?>, 
                        Απόκριση: <?= htmlspecialchars($c["responded_at"]) ?>)
                    </li>
                <?php
                    endwhile;
                else:
                    echo "<li>Δεν έχουν οριστεί μέλη επιτροπής.</li>";
                endif;
                $cstmt->close();
                ?>
            </ul>

            <span class="badge bg-info"><?= htmlspecialchars($row["status"]) ?></span>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>Δεν βρέθηκαν διπλωματικές.</p>
      <?php endif; ?>
    </div>





</div>
  </div>
    </body>
</html>