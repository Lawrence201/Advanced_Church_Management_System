<?php
/**
 * Database Configuration File
 * Church Management System - Events Module
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'church_management_system');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }

    // Set charset to utf8mb4 for better unicode support
    $conn->set_charset("utf8mb4");

    return $conn;
}

// Helper function to send JSON response
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Helper function to sanitize input
function sanitizeInput($data) {
    if ($data === null) {
        return null;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to format date for display
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Helper function to format time for display
function formatTime($time) {
    return date('g:i A', strtotime($time));
}
?>
