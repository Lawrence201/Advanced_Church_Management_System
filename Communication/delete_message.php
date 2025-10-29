<?php
/**
 * Delete Message API
 * Handles deletion of sent messages and their recipients
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['message_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required field: message_id'
    ]);
    exit;
}

$messageId = intval($data['message_id']);

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Delete message recipients first (foreign key constraint)
    $sql = "DELETE FROM message_recipients WHERE message_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $recipientsDeleted = $stmt->affected_rows;
    $stmt->close();
    
    // Delete from scheduled messages if exists
    $sql = "DELETE FROM scheduled_messages WHERE message_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $stmt->close();
    
    // Delete the message
    $sql = "DELETE FROM messages WHERE message_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $messagesDeleted = $stmt->affected_rows;
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Message deleted successfully',
        'recipients_deleted' => $recipientsDeleted,
        'messages_deleted' => $messagesDeleted
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
