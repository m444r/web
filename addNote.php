<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topic_id'], $_POST['note_text'])) {
    $teacher_id = $_SESSION['userid'];
    $topic_id   = intval($_POST['topic_id']);
    $note_text  = trim($_POST['note_text']);

    if (strlen($note_text) > 300) {
        $_SESSION['error_msg'] = "Η σημείωση δεν μπορεί να ξεπερνά τους 300 χαρακτήρες.";
        header("Location: TeacherThesisList.php");
        exit;
    }

   

    // Εισαγωγή της σημείωσης
    $stmt = $db->prepare("INSERT INTO notes (topic_id, teacher_id, note_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $topic_id, $teacher_id, $note_text);
    if (!$stmt->execute()) {
        die("Insert failed: " . $stmt->error);
    }
    $stmt->close();

    $_SESSION['success_msg'] = "Η σημείωση προστέθηκε επιτυχώς.";
    header("Location: TeacherThesisList.php");
    exit;
}
?>
