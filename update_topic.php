<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id      = intval($_POST["id"]);
    $title   = $_POST["title"];
    $summary = $_POST["summary"];
    $teacher_id = $_SESSION["userid"];

    // Βρίσκουμε το παλιό pdf_path
    $stmt = $db->prepare("SELECT pdf_path FROM topics WHERE id=? AND teacher_id=?");
    $stmt->bind_param("ii", $id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldPdf = "";
    if ($row = $result->fetch_assoc()) {
        $oldPdf = $row["pdf_path"];
    }
    $stmt->close();

    $pdf_path = $oldPdf;

    // Έλεγχος αν ανέβηκε νέο PDF
    if (isset($_FILES["pdf"]) && $_FILES["pdf"]["error"] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = uniqid() . "-" . basename($_FILES["pdf"]["name"]);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_file)) {
            // Διαγραφή παλιού PDF αν υπάρχει
            if ($oldPdf && file_exists($oldPdf)) {
                unlink($oldPdf);
            }
            $pdf_path = $target_file;
        }
    }

    // Update στη βάση
    $stmt = $db->prepare("UPDATE topics SET title=?, summary=?, pdf_path=? WHERE id=? AND teacher_id=?");
    $stmt->bind_param("sssii", $title, $summary, $pdf_path, $id, $teacher_id);
    $stmt->execute();
    $stmt->close();

    // Επιστροφή πίσω με μήνυμα
    header("Location: TeacherCreateThesis.php?updated=1");
    exit;
}
?>
