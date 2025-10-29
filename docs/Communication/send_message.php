<?php
/**
 * Send Message API
 * Handles sending messages via Email and SMS
 */

// Prevent any output before JSON
ob_start();

// Set headers
header('Content-Type: application/json');

// Error handling - always return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    require_once 'db_connect.php';
    require_once 'email_handler.php';
    require_once 'sms_handler.php';
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['title']) || !isset($data['content']) || !isset($data['delivery_channels'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: title, content, delivery_channels'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // 1. Create the message record
    $messageType = $data['message_type'] ?? 'general';
    $title = $conn->real_escape_string($data['title']);
    $content = $conn->real_escape_string($data['content']);
    $deliveryChannels = json_encode($data['delivery_channels']);
    $scheduledAt = isset($data['scheduled_at']) ? $data['scheduled_at'] : null;
    $status = isset($data['action']) && $data['action'] === 'schedule' ? 'scheduled' : 'sending';
    
    $sql = "INSERT INTO messages (message_type, title, content, delivery_channels, status, scheduled_at, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss', $messageType, $title, $content, $deliveryChannels, $status, $scheduledAt);
    $stmt->execute();
    $messageId = $conn->insert_id;
    $stmt->close();
    
    // 2. Get recipients based on audience selection
    $recipients = getRecipients($conn, $data);
    
    if (empty($recipients)) {
        throw new Exception('No recipients found for the selected audience');
    }
    
    // 3. Insert recipients into message_recipients table
    $totalRecipients = 0;
    foreach ($recipients as $recipient) {
        foreach ($data['delivery_channels'] as $channel) {
            // Skip if channel data is missing
            if ($channel === 'email' && empty($recipient['email'])) continue;
            if ($channel === 'sms' && empty($recipient['phone'])) continue;
            
            $sql = "INSERT INTO message_recipients 
                    (message_id, member_id, recipient_type, recipient_name, recipient_email, recipient_phone, delivery_channel, delivery_status) 
                    VALUES (?, ?, 'member', ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'iissss',
                $messageId,
                $recipient['member_id'],
                $recipient['name'],
                $recipient['email'],
                $recipient['phone'],
                $channel
            );
            $stmt->execute();
            $stmt->close();
            $totalRecipients++;
        }
    }
    
    // 4. Update message with recipient count
    $sql = "UPDATE messages SET total_recipients = ? WHERE message_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $totalRecipients, $messageId);
    $stmt->execute();
    $stmt->close();
    
    // 5. If sending now (not scheduled), process delivery
    if ($status === 'sending') {
        $result = processMessageDelivery($conn, $messageId, $data);
        
        // Update message status
        $finalStatus = 'sent';
        $sentAt = date('Y-m-d H:i:s');
        $sql = "UPDATE messages SET status = ?, sent_at = ?, total_sent = ?, total_failed = ? WHERE message_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiii', $finalStatus, $sentAt, $result['sent'], $result['failed'], $messageId);
        $stmt->execute();
        $stmt->close();
    } else {
        // Create scheduled message entry
        $sql = "INSERT INTO scheduled_messages (message_id, scheduled_time, status, next_run) 
                VALUES (?, ?, 'pending', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iss', $messageId, $scheduledAt, $scheduledAt);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->commit();
    
    // Log activity to dashboard if message was sent (not scheduled)
    if ($status === 'sending' || $status === 'sent') {
        try {
            $activityType = 'message_sent';
            $activityTitle = 'Message sent';
            $activityDescription = $title . ' sent to ' . $totalRecipients . ' members';
            $iconType = 'message';
            
            $stmt = $conn->prepare("INSERT INTO activity_log (activity_type, title, description, icon_type, related_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssssi', $activityType, $activityTitle, $activityDescription, $iconType, $messageId);
                $stmt->execute();
                $stmt->close();
                
                // Keep only the 4 most recent activities
                $conn->query("DELETE FROM activity_log WHERE activity_id NOT IN (SELECT activity_id FROM (SELECT activity_id FROM activity_log ORDER BY created_at DESC LIMIT 4) AS recent)");
            }
        } catch (Exception $e) {
            error_log('Failed to log message activity: ' . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'total_recipients' => $totalRecipients,
        'status' => $status,
        'delivery_stats' => $result ?? ['message' => 'Scheduled for later']
    ]);
    
} catch (Exception $e) {
    if ($conn) {
        $conn->rollback();
    }
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

if ($conn) {
    $conn->close();
}

/**
 * Get recipients based on audience selection
 */
function getRecipients($conn, $data) {
    $recipients = [];
    $audience = $data['audience'] ?? 'All Members';
    
    // Check if it's a specific group
    if (isset($data['audience_type'])) {
        switch ($data['audience_type']) {
            case 'all':
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE LOWER(status) = 'active'";
                break;
                
            case 'group':
            case 'department':
            case 'church_group':
                // Get members from specific church group
                $group = $conn->real_escape_string($data['audience_value']);
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE church_group = '$group' AND LOWER(status) = 'active'";
                break;
                
            case 'ministry':
                // Get members from specific ministry/department
                $ministry = $conn->real_escape_string($data['audience_value']);
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE leadership_role = '$ministry' AND LOWER(status) = 'active'";
                break;
                
            case 'custom_group':
                // Get members from message group
                $groupId = intval($data['group_id']);
                $sql = "SELECT m.member_id, CONCAT(m.first_name, ' ', m.last_name) as name, m.email, m.phone 
                        FROM members m 
                        INNER JOIN message_group_members mgm ON m.member_id = mgm.member_id 
                        WHERE mgm.group_id = $groupId AND LOWER(m.status) = 'active'";
                break;
                
            case 'individual':
            case 'others':
                // Specific member(s)
                $memberIds = implode(',', array_map('intval', $data['member_ids']));
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE member_id IN ($memberIds)";
                break;
                
            default:
                // Default to all members
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE LOWER(status) = 'active'";
        }
    } else {
        // Legacy support for old audience format
        switch ($audience) {
            case 'Youth Group':
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE church_group = 'Youth' AND status = 'active'";
                break;
            case 'Prayer Team':
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE church_group = 'Prayer Team' AND status = 'active'";
                break;
            case 'Volunteers':
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE leadership_role IS NOT NULL AND status = 'active'";
                break;
            default: // All Members
                $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                        FROM members WHERE LOWER(status) = 'active'";
        }
    }
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recipients[] = $row;
        }
    }
    
    return $recipients;
}

/**
 * Process message delivery (email/SMS)
 */
function processMessageDelivery($conn, $messageId, $data) {
    $sent = 0;
    $failed = 0;
    
    // Get all pending recipients
    $sql = "SELECT * FROM message_recipients WHERE message_id = ? AND delivery_status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($recipient = $result->fetch_assoc()) {
        $success = false;
        
        if ($recipient['delivery_channel'] === 'email') {
            $success = sendEmail(
                $recipient['recipient_email'],
                $data['title'],
                $data['content'],
                $messageId
            );
        } elseif ($recipient['delivery_channel'] === 'sms') {
            $success = sendSMS(
                $recipient['recipient_phone'],
                $data['content'],
                $messageId
            );
        }
        
        if ($success) {
            $sent++;
            $status = 'sent';
            $sentAt = date('Y-m-d H:i:s');
            
            $updateSql = "UPDATE message_recipients SET delivery_status = ?, sent_at = ? WHERE recipient_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('ssi', $status, $sentAt, $recipient['recipient_id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $failed++;
            $status = 'failed';
            
            $updateSql = "UPDATE message_recipients SET delivery_status = ? WHERE recipient_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('si', $status, $recipient['recipient_id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    $stmt->close();
    
    return ['sent' => $sent, 'failed' => $failed];
}

// Clean buffer and output
ob_end_flush();
?>
