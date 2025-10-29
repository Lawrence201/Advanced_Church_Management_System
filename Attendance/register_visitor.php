<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$source = trim($input['source'] ?? 'other');
$visitors_purpose = trim($input['visitors_purpose'] ?? '');
$service_id = trim($input['service_id'] ?? '');
$check_in_date = trim($input['check_in_date'] ?? date('Y-m-d'));

if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and phone number are required']);
    exit();
}

if (empty($service_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Service ID is required']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check if visitor already exists
    $checkVisitorSql = "SELECT visitor_id FROM visitors WHERE phone = ?";
    $checkVisitorStmt = $pdo->prepare($checkVisitorSql);
    $checkVisitorStmt->execute([$phone]);
    $existingVisitor = $checkVisitorStmt->fetch();
    
    $visitor_id = null;
    
    if ($existingVisitor) {
        // Update existing visitor info
        $visitor_id = $existingVisitor['visitor_id'];
        $updateSql = "UPDATE visitors SET name = ?, email = ?, source = ?, visitors_purpose = ?, updated_at = NOW() WHERE visitor_id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$name, $email, $source, $visitors_purpose, $visitor_id]);
    } else {
        // Insert new visitor
        $insertVisitorSql = "INSERT INTO visitors (name, phone, email, source, visitors_purpose, created_at, updated_at) 
                             VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $insertVisitorStmt = $pdo->prepare($insertVisitorSql);
        $insertVisitorStmt->execute([$name, $phone, $email, $source, $visitors_purpose]);
        $visitor_id = $pdo->lastInsertId();
    }
    
    // Check if visitor already checked in for this service today
    $checkAttendanceSql = "SELECT attendance_id FROM attendance 
                           WHERE visitor_id = ? AND service_id = ? AND check_in_date = ?";
    $checkAttendanceStmt = $pdo->prepare($checkAttendanceSql);
    $checkAttendanceStmt->execute([$visitor_id, $service_id, $check_in_date]);
    
    if (!$checkAttendanceStmt->fetch()) {
        // Check in the visitor
        $insertAttendanceSql = "INSERT INTO attendance (visitor_id, service_id, check_in_date, check_in_time, status, created_at, updated_at) 
                                VALUES (?, ?, ?, TIME(NOW()), 'visitor', NOW(), NOW())";
        $insertAttendanceStmt = $pdo->prepare($insertAttendanceSql);
        $insertAttendanceStmt->execute([$visitor_id, $service_id, $check_in_date]);
    }
    
    $pdo->commit();
    
    $message = $existingVisitor ? 
        "Welcome back, {$name}! You are checked in as a returning visitor." : 
        "Welcome to our church, {$name}! You have been successfully registered and checked in.";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'visitor' => [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'check_in_time' => date('H:i:s')
        ]
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
