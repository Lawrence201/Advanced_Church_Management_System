<?php
/**
 * Email Handler
 * Handles sending emails using PHPMailer
 * Install: composer require phpmailer/phpmailer
 */

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email to a recipient
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email content (can be HTML)
 * @param int $messageId Message ID for logging
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $messageId = null) {
    global $conn;
    
    try {
        // Get email settings from database
        $settings = getEmailSettings($conn);
        
        // Debug: Log settings
        error_log("Email Settings: " . json_encode([
            'smtp_host' => $settings['smtp_host'],
            'smtp_port' => $settings['smtp_port'],
            'smtp_username' => $settings['smtp_username'],
            'smtp_encryption' => $settings['smtp_encryption'],
            'has_password' => !empty($settings['smtp_password'])
        ]));
        
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer class not found, using fallback");
            throw new Exception("PHPMailer not available");
        }
        
        $mail = new PHPMailer(true);
        
        // Enable debug output
        $mail->SMTPDebug = 0; // 0 = off, 2 = detailed
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: $str");
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_username'];
        $mail->Password = $settings['smtp_password'];
        
        // Set encryption type
        if ($settings['smtp_encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        }
        
        $mail->Port = (int)$settings['smtp_port'];
        $mail->Timeout = 30;
        
        // Disable SSL verification (for development only)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom($settings['email_from_address'], $settings['email_from_name']);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($body); // Convert line breaks to <br>
        $mail->AltBody = strip_tags($body); // Plain text version
        
        // Send
        $result = $mail->send();
        
        // Log to database
        logEmail($conn, $messageId, $to, $subject, $body, 'sent', null);
        error_log("Email sent successfully to $to");
        
        return true;
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("PHPMailer Error: $errorMsg");
        
        // Log error
        logEmail($conn, $messageId, $to, $subject, $body, 'failed', $errorMsg);
        
        // DON'T fallback - throw the error so we can see it
        throw new Exception("Email sending failed: $errorMsg");
    }
}

/**
 * Send email using basic PHP mail() function (fallback)
 */
function sendEmailBasic($to, $subject, $body, $messageId = null) {
    global $conn;
    
    $settings = getEmailSettings($conn);
    
    $headers = "From: {$settings['email_from_name']} <{$settings['email_from_address']}>\r\n";
    $headers .= "Reply-To: {$settings['email_from_address']}\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $htmlBody = nl2br($body);
    
    $result = mail($to, $subject, $htmlBody, $headers);
    
    // Log to database
    logEmail($conn, $messageId, $to, $subject, $body, $result ? 'sent' : 'failed', 
             $result ? null : 'PHP mail() function failed');
    
    return $result;
}

/**
 * Get email settings from database
 */
function getEmailSettings($conn) {
    $defaults = [
        'email_from_name' => 'Church Management System',
        'email_from_address' => 'noreply@church.com',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls'
    ];
    
    try {
        $sql = "SELECT setting_key, setting_value FROM communication_settings WHERE setting_type = 'email'";
        $result = $conn->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $defaults[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        error_log("Error loading email settings: " . $e->getMessage());
    }
    
    return $defaults;
}

/**
 * Log email to database
 */
function logEmail($conn, $messageId, $email, $subject, $content, $status, $error = null) {
    // Skip logging if table doesn't exist
    if (!$conn) return;
    
    try {
        $sql = "INSERT INTO email_logs (message_id, recipient_email, subject, message_content, status, error_message, sent_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('isssss', $messageId, $email, $subject, $content, $status, $error);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently fail if table doesn't exist
        error_log("Email log error: " . $e->getMessage());
    }
}

/**
 * Send bulk emails (batch processing)
 */
function sendBulkEmails($recipients, $subject, $body, $messageId = null, $batchSize = 50) {
    $sent = 0;
    $failed = 0;
    $batch = [];
    
    foreach ($recipients as $recipient) {
        $batch[] = $recipient;
        
        if (count($batch) >= $batchSize) {
            foreach ($batch as $r) {
                if (sendEmail($r['email'], $subject, $body, $messageId)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
            $batch = [];
            sleep(1); // Prevent overwhelming SMTP server
        }
    }
    
    // Send remaining
    foreach ($batch as $r) {
        if (sendEmail($r['email'], $subject, $body, $messageId)) {
            $sent++;
        } else {
            $failed++;
        }
    }
    
    return ['sent' => $sent, 'failed' => $failed];
}

/**
 * Personalize email content with recipient data
 */
function personalizeEmail($template, $recipientData) {
    $content = $template;
    
    foreach ($recipientData as $key => $value) {
        $content = str_replace('{' . $key . '}', $value, $content);
    }
    
    return $content;
}

/**
 * Test email configuration
 */
function testEmailConfig($testEmail) {
    global $conn;
    
    $subject = "Test Email - Church Management System";
    $body = "This is a test email. If you receive this, your email configuration is working correctly!";
    
    return sendEmail($testEmail, $subject, $body);
}
?>
