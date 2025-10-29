<?php
require_once '../Attendance/config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['visitor_id']) || !isset($data['assigned_to'])) {
        throw new Exception('Visitor ID and assigned member are required');
    }
    
    $query = "UPDATE visitors SET 
        assigned_to = ?,
        follow_up_notes = CONCAT(COALESCE(follow_up_notes, ''), '\n[', NOW(), '] Assigned to: ', ?, IF(? != '', CONCAT(' - ', ?), '')),
        updated_at = NOW()
        WHERE visitor_id = ?";
    
    $notes = $data['notes'] ?? '';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $data['assigned_to'],
        $data['assigned_to'],
        $notes,
        $notes,
        $data['visitor_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Visitor assigned successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
