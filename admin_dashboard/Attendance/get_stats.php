<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$service_id = $_GET['service_id'] ?? '';
$check_in_date = $_GET['check_in_date'] ?? date('Y-m-d');

if (empty($check_in_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit();
}

try {
    $stats = [];
    
    // Get total registered members (all members, regardless of status)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM members");
    $stmt->execute();
    $stats['total_members'] = $stmt->fetch()['total'];
    
    // Query based on whether we have a service_id or querying historical data
    $useServiceId = !empty($service_id) && $service_id !== '0';
    
    // Get members present count
    if ($useServiceId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM attendance a
            WHERE a.member_id IS NOT NULL 
            AND a.service_id = ? 
            AND a.check_in_date = ? 
            AND a.status = 'present'
        ");
        $stmt->execute([$service_id, $check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM attendance a
            WHERE a.member_id IS NOT NULL 
            AND a.check_in_date = ? 
            AND a.status = 'present'
        ");
        $stmt->execute([$check_in_date]);
    }
    $stats['members_present'] = $stmt->fetch()['count'];
    
    // Get visitors count
    if ($useServiceId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM attendance a
            WHERE a.visitor_id IS NOT NULL 
            AND a.service_id = ? 
            AND a.check_in_date = ? 
            AND a.status = 'visitor'
        ");
        $stmt->execute([$service_id, $check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM attendance a
            WHERE a.visitor_id IS NOT NULL 
            AND a.check_in_date = ? 
            AND a.status = 'visitor'
        ");
        $stmt->execute([$check_in_date]);
    }
    $stats['visitors_count'] = $stmt->fetch()['count'];
    
    // Total present (members + visitors)
    $stats['total_present'] = $stats['members_present'] + $stats['visitors_count'];
    
    // Absent count
    $stats['absent_count'] = $stats['total_members'] - $stats['members_present'];
    
    // Get gender breakdown (members only)
    if ($useServiceId) {
        $stmt = $pdo->prepare("
            SELECT m.gender, COUNT(*) as count
            FROM attendance a
            JOIN members m ON a.member_id = m.member_id
            WHERE a.service_id = ? 
            AND a.check_in_date = ? 
            AND a.status = 'present'
            GROUP BY m.gender
        ");
        $stmt->execute([$service_id, $check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT m.gender, COUNT(*) as count
            FROM attendance a
            JOIN members m ON a.member_id = m.member_id
            WHERE a.check_in_date = ? 
            AND a.status = 'present'
            GROUP BY m.gender
        ");
        $stmt->execute([$check_in_date]);
    }
    $genderData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats['males_count'] = 0;
    $stats['females_count'] = 0;
    
    foreach ($genderData as $row) {
        if (strtolower($row['gender']) === 'male') {
            $stats['males_count'] = $row['count'];
        } elseif (strtolower($row['gender']) === 'female') {
            $stats['females_count'] = $row['count'];
        }
    }
    
    // Children count (assuming members under 18 or specific marital_status)
    if ($useServiceId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM attendance a
            JOIN members m ON a.member_id = m.member_id
            WHERE a.service_id = ? 
            AND a.check_in_date = ? 
            AND a.status = 'present'
            AND (YEAR(CURDATE()) - YEAR(m.date_of_birth)) < 18
        ");
        $stmt->execute([$service_id, $check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM attendance a
            JOIN members m ON a.member_id = m.member_id
            WHERE a.check_in_date = ? 
            AND a.status = 'present'
            AND (YEAR(CURDATE()) - YEAR(m.date_of_birth)) < 18
        ");
        $stmt->execute([$check_in_date]);
    }
    $stats['children_count'] = $stmt->fetch()['count'];
    
    // Calculate average arrival time
    if ($useServiceId) {
        $stmt = $pdo->prepare("
            SELECT AVG(TIME_TO_SEC(check_in_time)) as avg_seconds
            FROM attendance
            WHERE service_id = ? 
            AND check_in_date = ? 
            AND check_in_time IS NOT NULL
            AND status IN ('present', 'visitor')
        ");
        $stmt->execute([$service_id, $check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT AVG(TIME_TO_SEC(check_in_time)) as avg_seconds
            FROM attendance
            WHERE check_in_date = ? 
            AND check_in_time IS NOT NULL
            AND status IN ('present', 'visitor')
        ");
        $stmt->execute([$check_in_date]);
    }
    $result = $stmt->fetch();
    
    if ($result['avg_seconds']) {
        $avgSeconds = round($result['avg_seconds']);
        $hours = floor($avgSeconds / 3600);
        $minutes = floor(($avgSeconds % 3600) / 60);
        $stats['avg_arrival'] = sprintf('%02d:%02d', $hours, $minutes);
    } else {
        $stats['avg_arrival'] = 'N/A';
    }
    
    echo json_encode(['success' => true, 'data' => $stats]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>