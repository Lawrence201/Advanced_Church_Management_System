<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$conn = getDBConnection();
$response = ['success' => false, 'message' => ''];

try {
    // Get common fields
    $date = $conn->real_escape_string($input['date']);
    $paymentMethod = $conn->real_escape_string($input['paymentMethod']);
    $type = $conn->real_escape_string($input['type']);
    
    // Generate transaction ID
    $transactionId = generateTransactionId($type);
    
    // Process based on type
    switch ($type) {
        case 'offering':
            $result = saveOffering($conn, $transactionId, $date, $paymentMethod, $input);
            break;
            
        case 'projectoffering':
            $result = saveProjectOffering($conn, $transactionId, $date, $paymentMethod, $input);
            break;
            
        case 'tithe':
            $result = saveTithe($conn, $transactionId, $date, $paymentMethod, $input);
            break;
            
        case 'welfare':
            $result = saveWelfare($conn, $transactionId, $date, $paymentMethod, $input);
            break;
            
        case 'expense':
            $result = saveExpense($conn, $transactionId, $date, $paymentMethod, $input);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Invalid donation type'];
    }
    
    $response = $result;
    
    // Log activity to dashboard if successful
    if ($result['success']) {
        try {
            logDonationActivity($conn, $type, $input, $result);
        } catch (Exception $e) {
            error_log('Failed to log donation activity: ' . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

$conn->close();
echo json_encode($response);

// ============================================
// HELPER FUNCTIONS
// ============================================

function generateTransactionId($type) {
    $prefix = '';
    switch ($type) {
        case 'offering': $prefix = 'OFF'; break;
        case 'projectoffering': $prefix = 'POFF'; break;
        case 'tithe': $prefix = 'TXN'; break;
        case 'welfare': $prefix = 'WEL'; break;
        case 'expense': $prefix = 'EXP'; break;
    }
    return $prefix . date('Ymd') . rand(1000, 9999);
}

function saveOffering($conn, $transactionId, $date, $paymentMethod, $data) {
    $serviceType = $conn->real_escape_string($data['serviceType']);
    $serviceTime = $conn->real_escape_string($data['serviceTime']);
    $amount = floatval($data['amount']);
    $countedBy = $conn->real_escape_string($data['countedBy']);
    $notes = $conn->real_escape_string($data['notes'] ?? '');
    
    $sql = "INSERT INTO offerings (transaction_id, date, service_type, service_time, amount_collected, 
            collection_method, counted_by, notes, status) 
            VALUES ('$transactionId', '$date', '$serviceType', '$serviceTime', $amount, 
            '$paymentMethod', '$countedBy', '$notes', 'Verified')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true, 
            'message' => 'Offering recorded successfully!',
            'transaction_id' => $transactionId
        ];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
}

function saveProjectOffering($conn, $transactionId, $date, $paymentMethod, $data) {
    $serviceType = $conn->real_escape_string($data['serviceType']);
    $serviceTime = $conn->real_escape_string($data['serviceTime']);
    $projectName = $conn->real_escape_string($data['projectName']);
    $amount = floatval($data['amount']);
    $countedBy = $conn->real_escape_string($data['countedBy']);
    $notes = $conn->real_escape_string($data['notes'] ?? '');
    
    $sql = "INSERT INTO project_offerings (transaction_id, date, service_type, service_time, project_name,
            amount_collected, collection_method, counted_by, notes, status) 
            VALUES ('$transactionId', '$date', '$serviceType', '$serviceTime', '$projectName', $amount,
            '$paymentMethod', '$countedBy', '$notes', 'Verified')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true, 
            'message' => 'Project offering recorded successfully!',
            'transaction_id' => $transactionId
        ];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
}

function saveTithe($conn, $transactionId, $date, $paymentMethod, $data) {
    $memberName = $conn->real_escape_string($data['memberName']);
    $memberEmail = $conn->real_escape_string($data['memberEmail'] ?? '');
    $amount = floatval($data['amount']);
    $receiptNumber = $conn->real_escape_string($data['receiptNumber'] ?? $transactionId);
    $notes = $conn->real_escape_string($data['notes'] ?? '');
    
    // Try to find member_id by name or email
    $memberId = null;
    if (!empty($memberEmail)) {
        $result = $conn->query("SELECT member_id FROM members WHERE email = '$memberEmail' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $memberId = $row['member_id'];
        }
    }
    
    // If no email match, try by name
    if (!$memberId) {
        $nameParts = explode(' ', $memberName);
        if (count($nameParts) >= 2) {
            $firstName = $conn->real_escape_string($nameParts[0]);
            $lastName = $conn->real_escape_string($nameParts[count($nameParts) - 1]);
            $result = $conn->query("SELECT member_id FROM members WHERE first_name = '$firstName' AND last_name = '$lastName' LIMIT 1");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $memberId = $row['member_id'];
            }
        }
    }
    
    $memberIdSql = $memberId ? $memberId : 'NULL';
    
    $sql = "INSERT INTO tithes (transaction_id, member_id, date, amount, payment_method, 
            receipt_number, notes, status) 
            VALUES ('$transactionId', $memberIdSql, '$date', $amount, '$paymentMethod', 
            '$receiptNumber', '$notes', 'Paid')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true, 
            'message' => 'Tithe recorded successfully!',
            'transaction_id' => $transactionId,
            'receipt_number' => $receiptNumber
        ];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
}

function saveWelfare($conn, $transactionId, $date, $paymentMethod, $data) {
    $memberName = $conn->real_escape_string($data['memberName']);
    $amount = floatval($data['amount']);
    $paymentPeriod = $conn->real_escape_string($data['paymentPeriod']);
    $status = $conn->real_escape_string($data['status']);
    $notes = $conn->real_escape_string($data['notes'] ?? '');
    
    // Try to find member_id by name
    $memberId = null;
    $nameParts = explode(' ', $memberName);
    if (count($nameParts) >= 2) {
        $firstName = $conn->real_escape_string($nameParts[0]);
        $lastName = $conn->real_escape_string($nameParts[count($nameParts) - 1]);
        $result = $conn->query("SELECT member_id FROM members WHERE first_name = '$firstName' AND last_name = '$lastName' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $memberId = $row['member_id'];
        }
    }
    
    $memberIdSql = $memberId ? $memberId : 'NULL';
    
    $sql = "INSERT INTO welfare_contributions (transaction_id, member_id, date, amount, 
            payment_method, payment_period, notes, status) 
            VALUES ('$transactionId', $memberIdSql, '$date', $amount, '$paymentMethod', 
            '$paymentPeriod', '$notes', '$status')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true, 
            'message' => 'Welfare contribution recorded successfully!',
            'transaction_id' => $transactionId
        ];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
}

function saveExpense($conn, $transactionId, $date, $paymentMethod, $data) {
    $category = $conn->real_escape_string($data['category']);
    $customCategory = isset($data['customCategory']) ? $conn->real_escape_string($data['customCategory']) : null;
    $description = $conn->real_escape_string($data['description']);
    $vendor = $conn->real_escape_string($data['vendor']);
    $amount = floatval($data['amount']);
    $status = $conn->real_escape_string($data['status']);
    $notes = $conn->real_escape_string($data['notes'] ?? '');
    
    $customCategorySql = $customCategory ? "'$customCategory'" : 'NULL';
    
    $sql = "INSERT INTO expenses (transaction_id, date, category, custom_category, description, 
            vendor_payee, amount, payment_method, notes, status) 
            VALUES ('$transactionId', '$date', '$category', $customCategorySql, '$description', 
            '$vendor', $amount, '$paymentMethod', '$notes', '$status')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true, 
            'message' => 'Expense recorded successfully!',
            'transaction_id' => $transactionId
        ];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
}

function logDonationActivity($conn, $type, $data, $result) {
    $amount = number_format(floatval($data['amount']), 2);
    $donor = 'anonymous donor';
    
    // Try to get donor name based on type
    if ($type == 'tithe' && !empty($data['memberName'])) {
        $donor = $conn->real_escape_string($data['memberName']);
    } elseif ($type == 'welfare' && !empty($data['memberName'])) {
        $donor = $conn->real_escape_string($data['memberName']);
    }
    
    // Create description based on type
    $typeLabel = '';
    switch ($type) {
        case 'offering': $typeLabel = 'offering'; break;
        case 'projectoffering': $typeLabel = 'project offering'; break;
        case 'tithe': $typeLabel = 'tithe'; break;
        case 'welfare': $typeLabel = 'welfare contribution'; break;
        case 'expense': return; // Don't log expenses as donations
    }
    
    $activityType = 'donation_recorded';
    $activityTitle = 'Donation recorded';
    $activityDescription = "$$amount $typeLabel from $donor";
    $iconType = 'donation';
    $transactionId = $result['transaction_id'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO activity_log (activity_type, title, description, icon_type, related_id) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $relatedId = $transactionId ? $transactionId : null;
        $stmt->bind_param('sssss', $activityType, $activityTitle, $activityDescription, $iconType, $relatedId);
        $stmt->execute();
        $stmt->close();
        
        // Keep only the 4 most recent activities
        $conn->query("DELETE FROM activity_log WHERE activity_id NOT IN (SELECT activity_id FROM (SELECT activity_id FROM activity_log ORDER BY created_at DESC LIMIT 4) AS recent)");
    }
}
?>
