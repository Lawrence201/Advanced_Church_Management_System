<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'message' => ''];

try {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Project Offering ID is required');
    }
    
    $transaction_id = $_GET['id'];
    $conn = getDBConnection();
    
    // Escape the transaction ID
    $transaction_id = $conn->real_escape_string($transaction_id);
    
    // First check if project offering exists
    $checkSql = "SELECT project_offering_id FROM project_offerings WHERE transaction_id = '$transaction_id'";
    $result = $conn->query($checkSql);
    
    if ($result->num_rows === 0) {
        throw new Exception('Project offering not found');
    }
    
    // Delete the project offering
    $sql = "DELETE FROM project_offerings WHERE transaction_id = '$transaction_id'";
    
    if ($conn->query($sql)) {
        if ($conn->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Project offering deleted successfully';
        } else {
            throw new Exception('Failed to delete project offering');
        }
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
