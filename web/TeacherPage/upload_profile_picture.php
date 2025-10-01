<?php
session_start();
require '../config.php';

// Check if user is logged in
if (!isset($_SESSION["userid"])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$teacher_id = $_SESSION["userid"];

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error. Error code: ' . ($_FILES['profile_picture']['error'] ?? 'no file')]);
    exit;
}

$file = $_FILES['profile_picture'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Validate file size (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'teacher_' . $teacher_id . '_' . time() . '.' . $file_extension;
$file_path = $upload_dir . $filename;

// Debug: Log file information
error_log("Upload attempt - File: " . $file['name'] . ", Size: " . $file['size'] . ", Type: " . $file['type']);
error_log("Target path: " . $file_path);

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Update database with profile picture path
    $relative_path = 'uploads/profile_pictures/' . $filename;
    
    // First, check if profile_picture column exists, if not, we'll add it
    $check_column = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if ($check_column->num_rows == 0) {
        // Add profile_picture column to users table
        $db->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
    }
    
    // Update user's profile picture
    $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $relative_path, $teacher_id);
    
    if ($stmt->execute()) {
        error_log("Database update successful for user ID: " . $teacher_id);
        
        // Delete old profile picture if it exists
        $old_stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $old_stmt->bind_param("i", $teacher_id);
        $old_stmt->execute();
        $result = $old_stmt->get_result();
        if ($row = $result->fetch_assoc() && $row['profile_picture']) {
            $old_file = '../' . $row['profile_picture'];
            if (file_exists($old_file) && $old_file !== $file_path) {
                unlink($old_file);
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile picture updated successfully',
            'image_path' => $relative_path
        ]);
    } else {
        // If database update fails, delete the uploaded file
        unlink($file_path);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
?>
