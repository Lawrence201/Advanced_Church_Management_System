<?php
require_once '../Attendance/config.php';

header('Content-Type: application/json');

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

$visitor_id = $input['visitor_id'] ?? null;
$follow_up_status = $input['follow_up_status'] ?? 'pending';
$follow_up_date = $input['follow_up_date'] ?? null;
$follow_up_notes = $input['follow_up_notes'] ?? '';
$assigned_to = $input['assigned_to'] ?? null;
$contact_method = $input['contact_method'] ?? 'phone';
$outcome = $input['outcome'] ?? 'contacted';
$next_followup_date = $input['next_followup_date'] ?? null;
$created_by = $input['created_by'] ?? 'System';

if (!$visitor_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Visitor ID is required']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update visitor follow-up status
    $updateVisitorSql = "UPDATE visitors SET 
        follow_up_status = ?,
        follow_up_date = ?,
        follow_up_notes = ?,
        assigned_to = ?,
        updated_at = NOW()
    WHERE visitor_id = ?";
    
    $stmt = $pdo->prepare($updateVisitorSql);
    $stmt->execute([
        $follow_up_status,
        $follow_up_date,
        $follow_up_notes,
        $assigned_to,
        $visitor_id
    ]);
    
    // Log follow-up interaction
    $insertFollowupSql = "INSERT INTO visitor_followups 
        (visitor_id, followup_date, contact_method, notes, outcome, next_followup_date, created_by, created_at)
    VALUES (?, CURDATE(), ?, ?, ?, ?, ?, NOW())";
    
    $stmt2 = $pdo->prepare($insertFollowupSql);
    $stmt2->execute([
        $visitor_id,
        $contact_method,
        $follow_up_notes,
        $outcome,
        $next_followup_date,
        $created_by
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Follow-up updated successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
