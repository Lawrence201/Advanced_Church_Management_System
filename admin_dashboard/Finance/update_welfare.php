<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'message' => ''];

try {
    $conn = getDBConnection();

    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['transaction_id'])) {
        throw new Exception('Transaction ID is required');
    }

    $transactionId = $conn->real_escape_string($data['transaction_id']);
    $date = $conn->real_escape_string($data['date']);
    $amount = floatval($data['amount']);
    $paymentMethod = $conn->real_escape_string($data['payment_method']);
    $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : '';

    // Update the welfare record
    $sql = "UPDATE welfare_contributions SET
            date = '$date',
            amount = $amount,
            payment_method = '$paymentMethod',
            notes = '$notes',
            updated_at = NOW()
            WHERE transaction_id = '$transactionId'";

    if ($conn->query($sql)) {
        $response['success'] = true;
        $response['message'] = 'Welfare contribution updated successfully';
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }

    $conn->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
