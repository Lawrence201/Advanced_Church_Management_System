<?php
require_once '../Attendance/config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['visitor_id'])) {
        throw new Exception('Visitor ID is required');
    }
    
    // Delete related attendance records first (optional - you might want to keep them)
    // $stmt = $pdo->prepare("DELETE FROM attendance WHERE visitor_id = ?");
    // $stmt->execute([$data['visitor_id']]);
    
    // Delete the visitor
    $stmt = $pdo->prepare("DELETE FROM visitors WHERE visitor_id = ?");
    $stmt->execute([$data['visitor_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Visitor deleted successfully'
        ]);
    } else {
        throw new Exception('Visitor not found');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
