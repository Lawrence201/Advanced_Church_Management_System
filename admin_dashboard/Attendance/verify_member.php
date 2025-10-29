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

$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$service_id = trim($input['service_id'] ?? '');
$check_in_date = trim($input['check_in_date'] ?? date('Y-m-d'));

if (empty($phone) && empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide either phone number or email']);
    exit();
}

if (empty($service_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Service ID is required']);
    exit();
}

try {
    // First, check if email exists
    $emailCheckSql = "SELECT member_id, phone FROM members WHERE email = ?";
    $emailStmt = $pdo->prepare($emailCheckSql);
    $emailStmt->execute([$email]);
    $emailMatch = $emailStmt->fetch();

    // Then, check if phone exists
    $phoneCheckSql = "SELECT member_id, email FROM members WHERE phone = ?";
    $phoneStmt = $pdo->prepare($phoneCheckSql);
    $phoneStmt->execute([$phone]);
    $phoneMatch = $phoneStmt->fetch();

    // Validate: BOTH email AND phone must match the SAME member
    if (!$emailMatch && !$phoneMatch) {
        echo json_encode(['success' => false, 'message' => 'Incorrect email and phone number. Please check your details and try again.']);
        exit();
    }

    if ($emailMatch && !$phoneMatch) {
        echo json_encode(['success' => false, 'message' => 'Incorrect phone number. Please enter the correct phone number registered with your account.']);
        exit();
    }

    if (!$emailMatch && $phoneMatch) {
        echo json_encode(['success' => false, 'message' => 'Incorrect email address. Please enter the correct email registered with your account.']);
        exit();
    }

    // Both exist, but check if they belong to the SAME member
    if ($emailMatch['member_id'] !== $phoneMatch['member_id']) {
        echo json_encode(['success' => false, 'message' => 'Email and phone number do not match the same member account. Please check your details.']);
        exit();
    }

    // Now fetch the full member details (both email and phone are correct and match)
    $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) AS name, phone, email, church_group, leadership_role, photo_path
            FROM members
            WHERE member_id = ? AND status = 'Active'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$emailMatch['member_id']]);
    $member = $stmt->fetch();

    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Member account is inactive. Please contact the church office.']);
        exit();
    }
    
    // Check if member already checked in for this service today
    $checkSql = "SELECT attendance_id, check_in_time FROM attendance 
                 WHERE member_id = ? AND service_id = ? AND check_in_date = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$member['member_id'], $service_id, $check_in_date]);
    $existingAttendance = $checkStmt->fetch();
    
    if ($existingAttendance) {
        echo json_encode([
            'success' => true,
            'already_checked_in' => true,
            'message' => 'You have already checked in at ' . $existingAttendance['check_in_time'] . '. See you at the service!',
            'member' => [
                'name' => $member['name'],
                'phone' => $member['phone'],
                'email' => $member['email'],
                'ministry' => $member['church_group'],
                'position' => $member['leadership_role'],
                'photo' => $member['photo_path'],
                'check_in_time' => $existingAttendance['check_in_time']
            ]
        ]);
        exit();
    }
    
    // Check in the member
    $insertSql = "INSERT INTO attendance (member_id, service_id, check_in_date, check_in_time, status, created_at, updated_at) 
                  VALUES (?, ?, ?, TIME(NOW()), 'present', NOW(), NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$member['member_id'], $service_id, $check_in_date]);
    
    $currentTime = date('H:i:s');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Welcome back, ' . $member['name'] . '! Check-in successful.',
        'member' => [
            'name' => $member['name'],
            'phone' => $member['phone'],
            'email' => $member['email'],
            'ministry' => $member['church_group'],
            'position' => $member['leadership_role'],
            'photo' => $member['photo_path'],
            'check_in_time' => $currentTime
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
