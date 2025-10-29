<?php
/**
 * Process Scheduled Messages
 * Run this file via cron job to send scheduled messages
 * 
 * Linux Cron: Run every 5 minutes - see documentation for cron syntax
 * Windows Task Scheduler: Run every 5 minutes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/scheduled_messages.log');

require_once 'db_connect.php';
require_once 'email_handler.php';
require_once 'sms_handler.php';

$logFile = __DIR__ . '/scheduled_messages.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

logMessage("=== Starting scheduled message processing ===");

try {
    // Get messages that are due to be sent
    $sql = "SELECT sm.*, m.* 
            FROM scheduled_messages sm
            INNER JOIN messages m ON sm.message_id = m.message_id
            WHERE sm.status = 'pending' 
            AND sm.next_run <= NOW()
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        logMessage("No scheduled messages to process");
        exit;
    }
    
    logMessage("Found " . $result->num_rows . " scheduled messages to process");
    
    while ($schedule = $result->fetch_assoc()) {
        $messageId = $schedule['message_id'];
        $scheduleId = $schedule['schedule_id'];
        
        logMessage("Processing message ID: $messageId, Schedule ID: $scheduleId");
        
        // Update schedule status to processing
        $conn->query("UPDATE scheduled_messages SET status = 'processing' WHERE schedule_id = $scheduleId");
        
        // Update message status
        $conn->query("UPDATE messages SET status = 'sending' WHERE message_id = $messageId");
        
        // Get pending recipients
        $recipientsSql = "SELECT * FROM message_recipients 
                         WHERE message_id = $messageId 
                         AND delivery_status = 'pending'";
        $recipients = $conn->query($recipientsSql);
        
        $sent = 0;
        $failed = 0;
        
        // Process each recipient
        while ($recipient = $recipients->fetch_assoc()) {
            $success = false;
            
            if ($recipient['delivery_channel'] === 'email') {
                logMessage("Sending email to: " . $recipient['recipient_email']);
                $success = sendEmail(
                    $recipient['recipient_email'],
                    $schedule['title'],
                    $schedule['content'],
                    $messageId
                );
            } elseif ($recipient['delivery_channel'] === 'sms') {
                logMessage("Sending SMS to: " . $recipient['recipient_phone']);
                $success = sendSMS(
                    $recipient['recipient_phone'],
                    $schedule['content'],
                    $messageId
                );
            }
            
            if ($success) {
                $sent++;
                $status = 'sent';
                $sentAt = date('Y-m-d H:i:s');
                
                $updateSql = "UPDATE message_recipients 
                             SET delivery_status = ?, sent_at = ? 
                             WHERE recipient_id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param('ssi', $status, $sentAt, $recipient['recipient_id']);
                $stmt->execute();
                $stmt->close();
            } else {
                $failed++;
                $status = 'failed';
                
                $updateSql = "UPDATE message_recipients 
                             SET delivery_status = ? 
                             WHERE recipient_id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param('si', $status, $recipient['recipient_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Prevent overwhelming the server
            usleep(100000); // 0.1 second delay
        }
        
        logMessage("Sent: $sent, Failed: $failed");
        
        // Update message with results
        $finalStatus = 'sent';
        $sentAt = date('Y-m-d H:i:s');
        
        $updateSql = "UPDATE messages 
                     SET status = ?, sent_at = ?, total_sent = ?, total_failed = ? 
                     WHERE message_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param('ssiii', $finalStatus, $sentAt, $sent, $failed, $messageId);
        $stmt->execute();
        $stmt->close();
        
        // Handle recurring messages
        if ($schedule['recurrence'] !== 'none') {
            $nextRun = calculateNextRun($schedule['next_run'], $schedule['recurrence']);
            
            if ($schedule['recurrence_end'] === null || $nextRun <= $schedule['recurrence_end']) {
                // Schedule next occurrence
                $conn->query("UPDATE scheduled_messages 
                             SET status = 'pending', 
                                 last_run = NOW(), 
                                 next_run = '$nextRun' 
                             WHERE schedule_id = $scheduleId");
                logMessage("Scheduled next occurrence for: $nextRun");
            } else {
                // Recurrence ended
                $conn->query("UPDATE scheduled_messages SET status = 'completed' WHERE schedule_id = $scheduleId");
                logMessage("Recurrence ended for schedule ID: $scheduleId");
            }
        } else {
            // One-time message, mark as completed
            $conn->query("UPDATE scheduled_messages 
                         SET status = 'completed', last_run = NOW() 
                         WHERE schedule_id = $scheduleId");
            logMessage("One-time message completed");
        }
    }
    
    logMessage("=== Scheduled message processing completed ===");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    error_log("Scheduled message error: " . $e->getMessage());
}

$conn->close();

/**
 * Calculate next run time for recurring messages
 */
function calculateNextRun($currentRun, $recurrence) {
    $timestamp = strtotime($currentRun);
    
    switch ($recurrence) {
        case 'daily':
            return date('Y-m-d H:i:s', strtotime('+1 day', $timestamp));
        case 'weekly':
            return date('Y-m-d H:i:s', strtotime('+1 week', $timestamp));
        case 'monthly':
            return date('Y-m-d H:i:s', strtotime('+1 month', $timestamp));
        default:
            return $currentRun;
    }
}
?>
