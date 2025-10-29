<?php
$host = 'localhost';
$dbname = 'church_management_system';
$username = 'root'; // Default XAMPP MySQL username (adjust if changed)
$password = '';     // Default XAMPP MySQL password (adjust if changed)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
