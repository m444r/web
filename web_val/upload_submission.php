<?php
session_start();
require 'config.php';

if (!isset($_SESSION['userid'])) {
    header("Location: register.php");
    exit;
}

$student_id = $_SESSION['userid'];
$message = "";

// Έλεγχος POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_id = intval($_POST['topic_id']);
    $comments = trim($_POST['comments'] ?? '');
    $file_path = null;

    // Αν ανέβηκε αρχείο
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['submission_file'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "submissions/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = uniqid() . "-" . basename($file['name']);
            $target_file = $upload_dir . $filename;

            // Επιτρεπόμενοι τύποι
            $allowed_types = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['upload_message'] = "❌ Μη επιτρεπόμενος τύπος αρχείου.";
                header("Location: StudentPage/StudentTopics.php");
                exit;
            }

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $file_path = $target_file;
            } else {
                $_SESSION['upload_message'] = "❌ Αποτυχία αποθήκευσης αρχείου.";
                header("Location: StudentPage/StudentTopics.php");
                exit;
            }
        } else {
            $_SESSION['upload_message'] = "❌ Σφάλμα ανέβασματος αρχείου. Κωδικός: " . $file['error'];
            header("Location: StudentPage/StudentTopics.php");
            exit;
        }
    }

    // Αποθήκευση στη βάση (αρχεία μπορεί να είναι null)
    $stmt = $db->prepare("
        INSERT INTO student_submissions (student_id, topic_id, file_path, comments, uploaded_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiss", $student_id, $topic_id, $file_path, $comments);

    if ($stmt->execute()) {
        $_SESSION['upload_message'] = "✅ Η υποβολή καταχωρήθηκε με επιτυχία!";
    } else {
        $_SESSION['upload_message'] = "❌ Σφάλμα κατά την αποθήκευση στη βάση.";
    }

    header("Location: StudentPage/StudentTopics.php");
    exit;
}
?>
