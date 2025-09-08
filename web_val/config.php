<?php
// Simple database configuration
$host = 'localhost';
$dbname = 'thesis_management';
$username = 'root';
$password = '';

try {
    $db = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($db->connect_error) {
        // If database doesn't exist, create a mock connection for testing
        $db = new mysqli($host, $username, $password);
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }
    }
    
    // Set charset to utf8
    $db->set_charset("utf8");
    
} catch (Exception $e) {
    // For now, just continue without database
    $db = null;
}
?>
