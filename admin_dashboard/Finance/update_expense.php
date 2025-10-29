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
    $description = $conn->real_escape_string($data['description']);
    $category = $conn->real_escape_string($data['category']);
    $vendorPayee = $conn->real_escape_string($data['vendor_payee']);
    $amount = floatval($data['amount']);
    $paymentMethod = $conn->real_escape_string($data['payment_method']);
    $status = $conn->real_escape_string($data['status']);
    $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : '';

    // Update the expense record
    $sql = "UPDATE expenses SET
            date = '$date',
            description = '$description',
            category = '$category',
            vendor_payee = '$vendorPayee',
            amount = $amount,
            payment_method = '$paymentMethod',
            status = '$status',
            notes = '$notes',
            updated_at = NOW()
            WHERE transaction_id = '$transactionId'";

    if ($conn->query($sql)) {
        $response['success'] = true;
        $response['message'] = 'Expense updated successfully';
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }

    $conn->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
