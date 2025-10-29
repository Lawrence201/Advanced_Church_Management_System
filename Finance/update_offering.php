<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['transaction_id', 'date', 'service_type', 'amount_collected', 'collection_method', 'status'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $conn = getDBConnection();
    
    // Escape values
    $transaction_id = $conn->real_escape_string($data['transaction_id']);
    $date = $conn->real_escape_string($data['date']);
    $service_type = $conn->real_escape_string($data['service_type']);
    $service_time = isset($data['service_time']) ? $conn->real_escape_string($data['service_time']) : null;
    $amount_collected = floatval($data['amount_collected']);
    $collection_method = $conn->real_escape_string($data['collection_method']);
    $counted_by = isset($data['counted_by']) ? $conn->real_escape_string($data['counted_by']) : null;
    $status = $conn->real_escape_string($data['status']);
    $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : null;
    
    // Validate amount
    if ($amount_collected < 0) {
        throw new Exception('Amount cannot be negative');
    }
    
    // Update query
    $sql = "UPDATE offerings SET 
            date = '$date',
            service_type = '$service_type',
            service_time = " . ($service_time ? "'$service_time'" : "NULL") . ",
            amount_collected = $amount_collected,
            collection_method = '$collection_method',
            counted_by = " . ($counted_by ? "'$counted_by'" : "NULL") . ",
            status = '$status',
            notes = " . ($notes ? "'$notes'" : "NULL") . ",
            updated_at = NOW()
            WHERE transaction_id = '$transaction_id'";
    
    if ($conn->query($sql)) {
        if ($conn->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Offering updated successfully';
        } else {
            // Check if record exists
            $checkSql = "SELECT offering_id FROM offerings WHERE transaction_id = '$transaction_id'";
            $result = $conn->query($checkSql);
            if ($result->num_rows === 0) {
                throw new Exception('Offering not found');
            } else {
                // No changes made (data was the same)
                $response['success'] = true;
                $response['message'] = 'No changes detected';
            }
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
