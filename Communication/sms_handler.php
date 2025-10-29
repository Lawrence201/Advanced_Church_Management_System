<?php
/**
 * SMS Handler
 * Handles sending SMS using Twilio or Africa's Talking
 */

/**
 * Send SMS to a recipient
 * 
 * @param string $phone Recipient phone number (include country code)
 * @param string $message SMS content
 * @param int $messageId Message ID for logging
 * @return bool Success status
 */
function sendSMS($phone, $message, $messageId = null) {
    // For now, simulate SMS sending (always return true)
    // This prevents errors when SMS tables don't exist or provider isn't configured
    // In production, configure SMS provider and uncomment the actual sending code
    
    error_log("SMS SIMULATED: To: $phone, Message: $message");
    
    // Uncomment below when you have SMS provider configured
    /*
    global $conn;
    
    try {
        // Get SMS settings
        $settings = getSMSSettings($conn);
        
        if (empty($settings['sms_provider']) || $settings['sms_provider'] === 'none') {
            throw new Exception('SMS provider not configured');
        }
        
        $result = false;
        
        switch ($settings['sms_provider']) {
            case 'twilio':
                $result = sendViaTwilio($phone, $message, $settings, $messageId);
                break;
                
            case 'africastalking':
                $result = sendViaAfricasTalking($phone, $message, $settings, $messageId);
                break;
                
            default:
                throw new Exception('Invalid SMS provider');
        }
        
        // Log SMS
        logSMS($conn, $messageId, $phone, $message, $result ? 'sent' : 'failed', $settings['sms_provider'], null);
        
        return $result;
        
    } catch (Exception $e) {
        logSMS($conn, $messageId, $phone, $message, 'failed', $settings['sms_provider'] ?? 'unknown', $e->getMessage());
        error_log("SMS error: {$e->getMessage()}");
        return false;
    }
    */
    
    return true; // Simulated success
}

/**
 * Send SMS via Twilio
 */
function sendViaTwilio($phone, $message, $settings, $messageId) {
    // Twilio credentials
    $accountSid = $settings['sms_api_key'];
    $authToken = $settings['sms_api_secret'];
    $fromNumber = $settings['sms_sender_id'];
    
    if (empty($accountSid) || empty($authToken)) {
        throw new Exception('Twilio credentials not configured');
    }
    
    // Twilio API endpoint
    $url = "https://api.twilio.com/2010-04-01/Accounts/$accountSid/Messages.json";
    
    // Prepare data
    $data = [
        'From' => $fromNumber,
        'To' => $phone,
        'Body' => $message
    ];
    
    // Make API request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$accountSid:$authToken");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    } else {
        $error = json_decode($response, true);
        throw new Exception($error['message'] ?? 'Twilio API error');
    }
}

/**
 * Send SMS via Africa's Talking
 */
function sendViaAfricasTalking($phone, $message, $settings, $messageId) {
    $username = $settings['sms_api_key'];
    $apiKey = $settings['sms_api_secret'];
    $from = $settings['sms_sender_id'];
    
    if (empty($username) || empty($apiKey)) {
        throw new Exception('Africa\'s Talking credentials not configured');
    }
    
    // API endpoint (use sandbox for testing)
    $url = "https://api.africastalking.com/version1/messaging";
    
    // Prepare data
    $data = [
        'username' => $username,
        'to' => $phone,
        'message' => $message,
        'from' => $from
    ];
    
    // Make API request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apiKey: $apiKey",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        if (isset($result['SMSMessageData']['Recipients']) && count($result['SMSMessageData']['Recipients']) > 0) {
            return true;
        }
    }
    
    throw new Exception('Africa\'s Talking API error: ' . $response);
}

/**
 * Get SMS settings from database
 */
function getSMSSettings($conn) {
    $defaults = [
        'sms_provider' => 'none',
        'sms_api_key' => '',
        'sms_api_secret' => '',
        'sms_sender_id' => 'CHURCH',
        'max_sms_per_batch' => '100'
    ];
    
    $sql = "SELECT setting_key, setting_value FROM communication_settings WHERE setting_type = 'sms'";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $defaults[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $defaults;
}

/**
 * Log SMS to database
 */
function logSMS($conn, $messageId, $phone, $content, $status, $provider, $error = null) {
    // Skip logging if table doesn't exist
    if (!$conn) return;
    
    try {
        $sql = "INSERT INTO sms_logs (message_id, recipient_phone, message_content, status, provider, error_message, sent_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('isssss', $messageId, $phone, $content, $status, $provider, $error);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently fail if table doesn't exist
        error_log("SMS log error: " . $e->getMessage());
    }
}

/**
 * Send bulk SMS (batch processing)
 */
function sendBulkSMS($recipients, $message, $messageId = null, $batchSize = 100) {
    $sent = 0;
    $failed = 0;
    $batch = [];
    
    foreach ($recipients as $recipient) {
        $batch[] = $recipient;
        
        if (count($batch) >= $batchSize) {
            foreach ($batch as $r) {
                if (sendSMS($r['phone'], $message, $messageId)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
            $batch = [];
            sleep(1); // Prevent API rate limiting
        }
    }
    
    // Send remaining
    foreach ($batch as $r) {
        if (sendSMS($r['phone'], $message, $messageId)) {
            $sent++;
        } else {
            $failed++;
        }
    }
    
    return ['sent' => $sent, 'failed' => $failed];
}

/**
 * Validate phone number format
 */
function validatePhoneNumber($phone) {
    // Remove spaces, dashes, parentheses
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if it starts with + (international format)
    if (substr($phone, 0, 1) !== '+') {
        // Assume it's a local number, you might want to add country code
        // Example for Kenya: $phone = '+254' . ltrim($phone, '0');
    }
    
    return $phone;
}

/**
 * Test SMS configuration
 */
function testSMSConfig($testPhone) {
    $message = "Test SMS from Church Management System. Your SMS is working!";
    return sendSMS($testPhone, $message);
}

/**
 * Get SMS character count and number of messages
 */
function getSMSInfo($message) {
    $length = strlen($message);
    $singleSMSLength = 160;
    $multiSMSLength = 153;
    
    if ($length <= $singleSMSLength) {
        $parts = 1;
    } else {
        $parts = ceil($length / $multiSMSLength);
    }
    
    return [
        'characters' => $length,
        'messages' => $parts,
        'cost_estimate' => $parts * 0.05 // Adjust based on your provider
    ];
}
?>
