<?php
require_once 'config.php';

// Secret key for encoding - change this to something unique
define('SECRET_KEY', 'your-secret-key-change-this-to-something-random-12345');

/**
 * Generate a secure token for a service
 */
function generateSecureToken($service_id, $date, $service_name) {
    $data = [
        'service_id' => $service_id,
        'date' => $date,
        'service' => $service_name,
        'timestamp' => time()
    ];
    
    $json = json_encode($data);
    $encoded = base64_encode($json);
    $hash = hash_hmac('sha256', $encoded, SECRET_KEY);
    
    // Combine encoded data with hash
    return base64_encode($encoded . '::' . $hash);
}

/**
 * Validate and decode a secure token
 */
function validateSecureToken($token) {
    global $pdo;
    
    try {
        // Decode the token
        $decoded = base64_decode($token);
        if (!$decoded) {
            return ['valid' => false, 'message' => 'Invalid token format'];
        }
        
        $parts = explode('::', $decoded);
        if (count($parts) !== 2) {
            return ['valid' => false, 'message' => 'Invalid token structure'];
        }
        
        list($encoded, $hash) = $parts;
        
        // Verify hash
        $expectedHash = hash_hmac('sha256', $encoded, SECRET_KEY);
        if (!hash_equals($expectedHash, $hash)) {
            return ['valid' => false, 'message' => 'Token has been tampered with'];
        }
        
        // Decode data
        $json = base64_decode($encoded);
        $data = json_decode($json, true);
        
        if (!$data) {
            return ['valid' => false, 'message' => 'Invalid token data'];
        }
        
        // Check if token is too old (expires after 24 hours)
        $tokenAge = time() - $data['timestamp'];
        if ($tokenAge > 86400) { // 24 hours
            return ['valid' => false, 'message' => 'This QR code has expired. Please get a new one.'];
        }
        
        // Check if date matches today (optional - remove if you want multi-day codes)
        $today = date('Y-m-d');
        if ($data['date'] !== $today) {
            return ['valid' => false, 'message' => 'This QR code is for ' . $data['date'] . '. Today is ' . $today . '.'];
        }
        
        return [
            'valid' => true,
            'service_id' => $data['service_id'],
            'date' => $data['date'],
            'service' => $data['service'],
            'timestamp' => $data['timestamp']
        ];
        
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Error validating token: ' . $e->getMessage()];
    }
}

// If called directly as API
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Check if this is a validation request
        if (isset($input['token'])) {
            // Validate token
            $token = $input['token'];
            $result = validateSecureToken($token);
            
            if ($result['valid']) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'service_id' => $result['service_id'],
                        'date' => $result['date'],
                        'service_name' => $result['service']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } else {
            // Generate token
            $service_id = $input['service_id'] ?? '';
            $date = $input['date'] ?? date('Y-m-d');
            $service = $input['service'] ?? 'Service';
            
            $token = generateSecureToken($service_id, $date, $service);
            echo json_encode(['success' => true, 'token' => $token]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Validate token via GET
        $token = $_GET['token'] ?? '';
        $result = validateSecureToken($token);
        echo json_encode($result);
    }
}
?>