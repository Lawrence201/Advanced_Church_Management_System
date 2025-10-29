<?php
/**
 * Delete Member API
 * Handles member deletion with cascade to related tables
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_config.php';

// Get member ID from query parameter or JSON input
$member_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : null;

    if (!$member_id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $member_id = isset($input['member_id']) ? intval($input['member_id']) : (isset($input['memberId']) ? intval($input['memberId']) : null);
    }
}

if (!$member_id) {
    sendResponse(false, 'Member ID is required');
}

$conn = getDBConnection();

try {
    // Check if member exists
    $check_stmt = $conn->prepare("SELECT first_name, last_name FROM members WHERE member_id = ?");
    $check_stmt->bind_param("i", $member_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse(false, 'Member not found');
    }

    $member = $result->fetch_assoc();
    $member_name = $member['first_name'] . ' ' . $member['last_name'];
    $check_stmt->close();

    // Delete member (cascade delete is handled by database foreign keys)
    // But we'll be explicit for clarity
    $delete_stmt = $conn->prepare("DELETE FROM members WHERE member_id = ?");
    $delete_stmt->bind_param("i", $member_id);
    $delete_stmt->execute();

    if ($delete_stmt->affected_rows > 0) {
        $delete_stmt->close();
        $conn->close();
        sendResponse(true, "Member '$member_name' deleted successfully", ['member_id' => $member_id]);
    } else {
        $delete_stmt->close();
        $conn->close();
        sendResponse(false, 'Failed to delete member');
    }

} catch (Exception $e) {
    $conn->close();
    sendResponse(false, 'Error: ' . $e->getMessage());
}
?>