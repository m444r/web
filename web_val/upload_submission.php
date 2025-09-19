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
    $submission_type = trim($_POST['submission_type'] ?? '');
    $links = trim($_POST['links'] ?? '');
    $file_path = null;

    // Validation
    if (empty($submission_type)) {
        $_SESSION['upload_message'] = "❌ Παρακαλώ επιλέξτε τύπο υποβολής.";
        header("Location: StudentPage/StudentInvites.php");
        exit;
    }

    // Αν ανέβηκε αρχείο
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['submission_file'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "uploads/submissions/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = uniqid() . "-" . basename($file['name']);
            $target_file = $upload_dir . $filename;

            // Επιτρεπόμενοι τύποι (επεκτεταμένοι)
            $allowed_types = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];

            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['upload_message'] = "❌ Μη επιτρεπόμενος τύπος αρχείου. Επιτρέπονται: PDF, DOC, DOCX, PPT, PPTX";
                header("Location: StudentPage/StudentInvites.php");
                exit;
            }

            // Check file size (max 10MB)
            if ($file['size'] > 10 * 1024 * 1024) {
                $_SESSION['upload_message'] = "❌ Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 10MB";
                header("Location: StudentPage/StudentInvites.php");
                exit;
            }

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $file_path = $target_file;
            } else {
                $_SESSION['upload_message'] = "❌ Αποτυχία αποθήκευσης αρχείου.";
                header("Location: StudentPage/StudentInvites.php");
                exit;
            }
        } else {
            $_SESSION['upload_message'] = "❌ Σφάλμα ανέβασματος αρχείου. Κωδικός: " . $file['error'];
            header("Location: StudentPage/StudentInvites.php");
            exit;
        }
    }

    // Check if at least file or links are provided
    if (empty($file_path) && empty($links)) {
        $_SESSION['upload_message'] = "❌ Παρακαλώ ανεβάστε αρχείο ή προσθέστε συνδέσμους.";
        header("Location: StudentPage/StudentInvites.php");
        exit;
    }

    // Αποθήκευση στη βάση
    $stmt = $db->prepare("
        INSERT INTO student_submissions (student_id, topic_id, file_path, comments, submission_type, links, uploaded_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissss", $student_id, $topic_id, $file_path, $comments, $submission_type, $links);

    if ($stmt->execute()) {
        $_SESSION['upload_message'] = "✅ Η υποβολή καταχωρήθηκε με επιτυχία!";
        
        // If it's a draft submission, update topic status to 'for examination'
        if ($submission_type === 'draft') {
            $update_stmt = $db->prepare("UPDATE topics SET status = 'for examination' WHERE id = ? AND assigned_to = ?");
            $update_stmt->bind_param("ii", $topic_id, $student_id);
            $update_stmt->execute();
        }
    } else {
        $_SESSION['upload_message'] = "❌ Σφάλμα κατά την αποθήκευση στη βάση.";
    }

                header("Location: StudentPage/StudentInvites.php");
    exit;
}
?>
