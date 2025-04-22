<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$assigned_to = $_SESSION["userid"];
$message = "";

// Ανακτάμε τα θέματα που έχουν ανατεθεί στον συγκεκριμένο φοιτητή
$query = "SELECT t.id, t.title, t.summary, t.status, t.teacher_id, t.pdf_path,  t.assigned_time,
                 u.name AS supervisor_name
          FROM topics t
          LEFT JOIN users u ON t.teacher_id = u.id
          WHERE t.assigned_to = (SELECT id FROM users WHERE role = 'student' AND id = ?)";



$stmt = $db->prepare($query);
$stmt->bind_param("i", $assigned_to);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($topic = $result->fetch_assoc()) {
        echo "<h3>Τίτλος Θέματος: " . htmlspecialchars($topic['title']) . "</h3>";
        echo "<p><strong>Περιγραφή:</strong> " . htmlspecialchars($topic['summary']) . "</p>";
        echo "<p><strong>Κατάσταση:</strong> " . htmlspecialchars($topic['status']) . "</p>";
        echo "<p><strong>Επιβλέπων:</strong> " . htmlspecialchars($topic['supervisor_name']) . "</p>";
        
        if ($topic['pdf_path']) {
            echo "<p><strong>Συνημμένο Αρχείο:</strong> <a href='http://localhost/web/" . htmlspecialchars($topic['pdf_path']) . "' target='_blank'>Δείτε το αρχείο</a></p>";
        }

        $assigned_date = new DateTime($topic['assigned_time']);
        $now = new DateTime();
        $interval = $assigned_date->diff($now);
        echo "<p><strong>Χρόνος Από Ανάθεση:</strong> " . $interval->days . " μέρες</p>";
        
    }
} else {
    echo "<p>❌ Δεν έχει ανατεθεί κάποιο θέμα στον φοιτητή.</p>";
}
?>
