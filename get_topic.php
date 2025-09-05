<?php
require 'config.php';
session_start();

if (isset($_GET['id'])) {
    $topic_id = intval($_GET['id']);
    $teacher_id = $_SESSION['userid'];

    $stmt = $db->prepare("SELECT id, title, summary, pdf_path 
                          FROM topics 
                          WHERE id=? AND teacher_id=?");
    $stmt->bind_param("ii", $topic_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $topic = $result->fetch_assoc();

    echo json_encode($topic);
}
