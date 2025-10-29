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
    // Get visitors with their check-in information
    // Query by date only if service_id is '0' or empty
    if (empty($service_id) || $service_id === '0') {
        $stmt = $pdo->prepare("
            SELECT 
                v.visitor_id as id,
                v.name,
                v.phone,
                v.email,
                v.source,
                v.visitors_purpose,
                a.check_in_time,
                a.check_in_date
            FROM visitors v
            JOIN attendance a ON v.visitor_id = a.visitor_id
            WHERE a.check_in_date = ?
            AND a.status = 'visitor'
            ORDER BY a.check_in_time DESC
        ");
        $stmt->execute([$check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                v.visitor_id as id,
                v.name,
                v.phone,
                v.email,
                v.source,
                v.visitors_purpose,
                a.check_in_time,
                a.check_in_date
            FROM visitors v
            JOIN attendance a ON v.visitor_id = a.visitor_id
            WHERE a.service_id = ?
            AND a.check_in_date = ?
            AND a.status = 'visitor'
            ORDER BY a.check_in_time DESC
        ");
        $stmt->execute([$service_id, $check_in_date]);
    }
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $visitors]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
