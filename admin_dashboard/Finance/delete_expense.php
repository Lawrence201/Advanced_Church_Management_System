<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get the transaction ID from query string
    if (!isset($_GET['id'])) {
        throw new Exception('Transaction ID is required');
    }

    $transactionId = $_GET['id'];
    $conn = getDBConnection();

    // Escape the transaction ID to prevent SQL injection
    $transactionId = $conn->real_escape_string($transactionId);

    // Delete the expense record
    $sql = "DELETE FROM expenses WHERE transaction_id = '$transactionId'";

    if ($conn->query($sql)) {
        if ($conn->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Expense deleted successfully';
        } else {
            throw new Exception('Expense not found');
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
