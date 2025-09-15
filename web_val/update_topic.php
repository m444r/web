<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_topic'])) {
    $id = intval($_POST["id"]);
    $title = trim($_POST["title"]);
    $summary = trim($_POST["summary"]);
    $teacher_id = $_SESSION["userid"];

    // Validate input
    if (empty($title)) {
        header("Location: TeacherPage/TeacherCreateThesis.php?error=title_empty");
        exit;
    }
    
    if (empty($summary)) {
        header("Location: TeacherPage/TeacherCreateThesis.php?error=summary_empty");
        exit;
    }

    // Get the old pdf_path
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

    // Check if new PDF was uploaded
    if (isset($_FILES["pdf"]) && $_FILES["pdf"]["error"] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["pdf"]["name"], PATHINFO_EXTENSION));
        if ($file_extension === 'pdf') {
            $filename = uniqid() . "_" . basename($_FILES["pdf"]["name"]);
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_file)) {
                // Delete old PDF if it exists
                if ($oldPdf && file_exists($oldPdf)) {
                    unlink($oldPdf);
                }
                $pdf_path = $target_file;
            } else {
                header("Location: TeacherPage/TeacherCreateThesis.php?error=upload_failed");
                exit;
            }
        } else {
            header("Location: TeacherPage/TeacherCreateThesis.php?error=invalid_file");
            exit;
        }
    }

    // Update in database
    $stmt = $db->prepare("UPDATE topics SET title=?, summary=?, pdf_path=? WHERE id=? AND teacher_id=?");
    $stmt->bind_param("sssii", $title, $summary, $pdf_path, $id, $teacher_id);
    
    if ($stmt->execute()) {
        header("Location: TeacherPage/TeacherCreateThesis.php?updated=1");
    } else {
        header("Location: TeacherPage/TeacherCreateThesis.php?error=update_failed");
    }
    $stmt->close();
    exit;
}
?>
