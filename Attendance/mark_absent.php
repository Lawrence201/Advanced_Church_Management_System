<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

try {
    // Get service_id and check_in_date from request
    $data = json_decode(file_get_contents('php://input'), true);
    $service_id = $data['service_id'] ?? '';
    $check_in_date = $data['check_in_date'] ?? date('Y-m-d');

    if (!$service_id || !$check_in_date) {
        echo json_encode(['success' => false, 'message' => 'Service ID and date are required']);
        exit;
    }

    // Fetch all members
    $stmt = $pdo->query("SELECT member_id FROM members WHERE status = 'Active'");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Begin transaction
    $pdo->beginTransaction();

    // Insert absent records for all members
    $stmt = $pdo->prepare("
        INSERT INTO attendance (member_id, service_id, check_in_date, status, created_at, updated_at)
        VALUES (:member_id, :service_id, :check_in_date, 'absent', NOW(), NOW())
        ON DUPLICATE KEY UPDATE status = 'absent', updated_at = NOW()
    ");

    foreach ($members as $member) {
        $stmt->execute([
            'member_id' => $member['member_id'],
            'service_id' => $service_id,
            'check_in_date' => $check_in_date
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'All members marked as absent']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error marking members as absent: ' . $e->getMessage()]);
}
