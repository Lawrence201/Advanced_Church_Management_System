<?php
require_once '../Attendance/config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['visitor_id']) || !isset($data['name']) || !isset($data['phone'])) {
        throw new Exception('Missing required fields');
    }
    
    $query = "UPDATE visitors SET 
        name = ?,
        phone = ?,
        email = ?,
        source = ?,
        follow_up_status = ?,
        follow_up_date = ?,
        follow_up_notes = ?,
        updated_at = NOW()
        WHERE visitor_id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $data['name'],
        $data['phone'],
        $data['email'] ?: null,
        $data['source'] ?: null,
        $data['follow_up_status'] ?: 'pending',
        $data['follow_up_date'] ?: null,
        $data['follow_up_notes'] ?: null,
        $data['visitor_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Visitor updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
