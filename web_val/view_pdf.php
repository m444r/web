<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION["userid"])) {
    header("Location: login_page.php");
    exit;
}

// Get the file parameter
$file = $_GET['file'] ?? '';

if (empty($file)) {
    die("No file specified");
}

// Security: Only allow files from uploads directory
$file = basename($file); // Remove any path traversal attempts
$file_path = "uploads/" . $file;

// Check if file exists
if (!file_exists($file_path)) {
    die("File not found");
}

// Check if it's a PDF file
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
if ($file_extension !== 'pdf') {
    die("Invalid file type");
}

// Set headers for PDF viewing
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . filesize($file_path));

// Output the file
readfile($file_path);
exit;
?>
