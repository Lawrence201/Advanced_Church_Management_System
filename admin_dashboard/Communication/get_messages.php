<?php
/**
 * Get Messages API
 * Retrieve sent, scheduled, and draft messages
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

$action = $_GET['action'] ?? 'sent';

switch ($action) {
    case 'sent':
        getSentMessages($conn);
        break;
    case 'scheduled':
        getScheduledMessages($conn);
        break;
    case 'drafts':
        getDraftMessages($conn);
        break;
    case 'stats':
        getMessageStats($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();

/**
 * Get all sent messages
 */
function getSentMessages($conn) {
    $sql = "SELECT 
            m.message_id,
            m.message_type,
            m.title,
            m.content,
            m.delivery_channels,
            m.status,
            m.total_recipients,
            m.total_sent,
            m.total_failed,
            m.sent_at,
            m.created_at,
            COUNT(DISTINCT mr.recipient_id) as recipient_count,
            SUM(CASE WHEN mr.opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened_count,
            GROUP_CONCAT(DISTINCT 
                CASE 
                    WHEN mr.recipient_type = 'member' THEN mr.recipient_name
                    ELSE NULL
                END
            ) as recipient_names
            FROM messages m
            LEFT JOIN message_recipients mr ON m.message_id = mr.message_id
            WHERE m.status = 'sent'
            GROUP BY m.message_id
            ORDER BY m.sent_at DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    $messages = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Calculate open rate
            $openRate = 0;
            if ($row['recipient_count'] > 0) {
                $openRate = round(($row['opened_count'] / $row['recipient_count']) * 100);
            }
            
            // Decode delivery channels
            $row['delivery_channels'] = json_decode($row['delivery_channels'], true);
            
            // Get audience info
            $audienceInfo = getAudienceInfo($conn, $row['message_id']);
            $row['audience_text'] = $audienceInfo;
            
            // Add open rate
            $row['open_rate'] = $openRate;
            
            // Format date
            $row['sent_date'] = date('M j, Y', strtotime($row['sent_at']));
            $row['sent_time'] = date('g:i A', strtotime($row['sent_at']));
            
            $messages[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

/**
 * Get scheduled messages
 */
function getScheduledMessages($conn) {
    $sql = "SELECT 
            m.message_id,
            m.message_type,
            m.title,
            m.content,
            m.delivery_channels,
            m.status,
            m.total_recipients,
            m.scheduled_at,
            m.created_at,
            sm.status as schedule_status,
            sm.next_run
            FROM messages m
            INNER JOIN scheduled_messages sm ON m.message_id = sm.message_id
            WHERE m.status = 'scheduled' AND sm.status = 'pending'
            ORDER BY m.scheduled_at ASC
            LIMIT 50";
    
    $result = $conn->query($sql);
    $messages = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['delivery_channels'] = json_decode($row['delivery_channels'], true);
            
            // Get audience info
            $audienceInfo = getAudienceInfo($conn, $row['message_id']);
            $row['audience_text'] = $audienceInfo;
            
            // Format date
            $row['scheduled_date'] = date('M j, Y', strtotime($row['scheduled_at']));
            $row['scheduled_time'] = date('g:i A', strtotime($row['scheduled_at']));
            
            $messages[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

/**
 * Get draft messages
 */
function getDraftMessages($conn) {
    $sql = "SELECT 
            m.message_id,
            m.message_type,
            m.title,
            m.content,
            m.delivery_channels,
            m.status,
            m.created_at,
            m.updated_at
            FROM messages m
            WHERE m.status = 'draft'
            ORDER BY m.updated_at DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    $messages = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['delivery_channels'] = json_decode($row['delivery_channels'], true);
            $row['created_date'] = date('M j, Y', strtotime($row['created_at']));
            $messages[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

/**
 * Get message statistics
 */
function getMessageStats($conn) {
    $stats = [];
    
    // Total sent messages
    $sql = "SELECT COUNT(*) as count FROM messages WHERE status = 'sent'";
    $result = $conn->query($sql);
    $stats['total_sent'] = $result->fetch_assoc()['count'];
    
    // Total scheduled messages
    $sql = "SELECT COUNT(*) as count FROM messages WHERE status = 'scheduled'";
    $result = $conn->query($sql);
    $stats['total_scheduled'] = $result->fetch_assoc()['count'];
    
    // Total recipients reached
    $sql = "SELECT SUM(total_sent) as count FROM messages WHERE status = 'sent'";
    $result = $conn->query($sql);
    $stats['total_recipients'] = $result->fetch_assoc()['count'] ?? 0;
    
    // Average open rate
    $sql = "SELECT 
            COUNT(DISTINCT mr.recipient_id) as total_recipients,
            SUM(CASE WHEN mr.opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened_count
            FROM messages m
            INNER JOIN message_recipients mr ON m.message_id = mr.message_id
            WHERE m.status = 'sent'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    if ($row['total_recipients'] > 0) {
        $stats['avg_open_rate'] = round(($row['opened_count'] / $row['total_recipients']) * 100);
    } else {
        $stats['avg_open_rate'] = 0;
    }
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

/**
 * Get audience information for a message
 */
function getAudienceInfo($conn, $messageId) {
    $sql = "SELECT COUNT(DISTINCT member_id) as count FROM message_recipients WHERE message_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'];
    $stmt->close();
    
    if ($count == 1) {
        // Get the member name
        $sql = "SELECT recipient_name FROM message_recipients WHERE message_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $messageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['recipient_name'];
    } else if ($count > 1) {
        return "$count Members";
    } else {
        return "All Members";
    }
}
?>
