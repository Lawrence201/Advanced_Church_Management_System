<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Change if different
define('DB_PASS', '');      // Change if you have a password
define('DB_NAME', 'church_management_system');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    
    return $conn;
}

// Set timezone
date_default_timezone_set('Africa/Accra');

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

