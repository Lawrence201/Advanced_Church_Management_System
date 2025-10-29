<?php
header('Content-Type: application/json');
require_once 'config.php';

$conn = getDBConnection();
$response = ['success' => false, 'data' => []];

try {
    $action = isset($_GET['action']) ? $_GET['action'] : 'get';
    
    switch ($action) {
        case 'get':
            // Get all budget settings
            $sql = "SELECT * FROM budget_settings ORDER BY setting_name";
            $result = $conn->query($sql);
            
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_name']] = [
                    'value' => floatval($row['setting_value']),
                    'description' => $row['description'],
                    'updated_at' => $row['updated_at']
                ];
            }
            
            $response['data'] = $settings;
            $response['success'] = true;
            break;
            
        case 'get_monthly':
            // Get only monthly budget
            $sql = "SELECT setting_value FROM budget_settings WHERE setting_name = 'monthly_budget'";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $response['data'] = ['monthly_budget' => floatval($row['setting_value'])];
            } else {
                // Return default if not set
                $response['data'] = ['monthly_budget' => 50000.00];
            }
            $response['success'] = true;
            break;
            
        case 'update':
            // Update budget setting
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['setting_name']) || !isset($input['setting_value'])) {
                throw new Exception('Missing required fields: setting_name and setting_value');
            }
            
            $settingName = $conn->real_escape_string($input['setting_name']);
            $settingValue = floatval($input['setting_value']);
            $description = isset($input['description']) ? $conn->real_escape_string($input['description']) : '';
            
            // Validate budget value
            if ($settingValue < 0) {
                throw new Exception('Budget value cannot be negative');
            }
            
            // Update or insert
            $sql = "INSERT INTO budget_settings (setting_name, setting_value, description) 
                    VALUES ('$settingName', $settingValue, '$description')
                    ON DUPLICATE KEY UPDATE 
                    setting_value = $settingValue,
                    description = IF('$description' != '', '$description', description)";
            
            if ($conn->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'Budget setting updated successfully';
                $response['data'] = [
                    'setting_name' => $settingName,
                    'setting_value' => $settingValue
                ];
            } else {
                throw new Exception('Failed to update budget setting: ' . $conn->error);
            }
            break;
            
        case 'update_monthly':
            // Quick update for monthly budget only
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['amount'])) {
                throw new Exception('Missing required field: amount');
            }
            
            $amount = floatval($input['amount']);
            
            if ($amount < 0) {
                throw new Exception('Budget amount cannot be negative');
            }
            
            $sql = "INSERT INTO budget_settings (setting_name, setting_value, description) 
                    VALUES ('monthly_budget', $amount, 'Total monthly budget for church expenses')
                    ON DUPLICATE KEY UPDATE setting_value = $amount";
            
            if ($conn->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'Monthly budget updated successfully';
                $response['data'] = ['monthly_budget' => $amount];
            } else {
                throw new Exception('Failed to update monthly budget: ' . $conn->error);
            }
            break;
            
        default:
            throw new Exception('Invalid action parameter');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
