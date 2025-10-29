<?php
/**
 * Test Email Configuration
 * This script tests if emails can be sent with current settings
 */

require_once 'db_connect.php';

// Check if communication_settings table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'communication_settings'");
if ($tableCheck->num_rows === 0) {
    echo "<h2 style='color: red;'>❌ Communication Settings Table Does Not Exist</h2>";
    echo "<p>Creating table now...</p>";
    
    $createTable = "CREATE TABLE IF NOT EXISTS communication_settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        setting_type VARCHAR(50) NOT NULL,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_setting (setting_type, setting_key)
    )";
    
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>✅ Table created successfully!</p>";
        
        // Insert default email settings
        $settings = [
            ['email', 'smtp_host', 'smtp.gmail.com'],
            ['email', 'smtp_port', '587'],
            ['email', 'smtp_username', 'lawrenceantwi63@gmail.com'],
            ['email', 'smtp_password', 'gotjwynclayogibo'],
            ['email', 'email_from_name', 'Church Management System'],
            ['email', 'email_from_address', 'lawrenceantwi63@gmail.com'],
            ['email', 'smtp_encryption', 'tls']
        ];
        
        foreach ($settings as $setting) {
            $sql = "INSERT INTO communication_settings (setting_type, setting_key, setting_value) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $setting[0], $setting[1], $setting[2]);
            $stmt->execute();
            $stmt->close();
        }
        
        echo "<p style='color: green;'>✅ Default settings inserted!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

// Load email settings from database
echo "<h2>📧 Current Email Settings</h2>";
$sql = "SELECT setting_key, setting_value FROM communication_settings WHERE setting_type = 'email'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $displayValue = $row['setting_key'] === 'smtp_password' 
            ? str_repeat('*', strlen($row['setting_value'])) 
            : $row['setting_value'];
        echo "<tr><td><strong>{$row['setting_key']}</strong></td><td>{$displayValue}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No email settings found in database!</p>";
}

// Check PHPMailer availability
echo "<h2>📦 PHPMailer Status</h2>";

// Try to load autoloader
$autoloaderPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
    echo "<p style='color: green;'>✅ Vendor autoloader found at: <code>$autoloaderPath</code></p>";
} else {
    echo "<p style='color: red;'>❌ Vendor autoloader NOT found at: <code>$autoloaderPath</code></p>";
}

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<p style='color: green;'>✅ PHPMailer is installed and loaded!</p>";
    
    // Check OpenSSL
    echo "<h3>🔐 PHP Configuration</h3>";
    if (extension_loaded('openssl')) {
        echo "<p style='color: green;'>✅ OpenSSL extension is loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ OpenSSL extension is NOT loaded</p>";
        echo "<p><strong>To enable OpenSSL:</strong></p>";
        echo "<ol>";
        echo "<li>Open: <code>C:\\xampp\\php\\php.ini</code></li>";
        echo "<li>Find: <code>;extension=openssl</code></li>";
        echo "<li>Remove the semicolon: <code>extension=openssl</code></li>";
        echo "<li>Restart Apache</li>";
        echo "</ol>";
    }
    
    if (extension_loaded('sockets')) {
        echo "<p style='color: green;'>✅ Sockets extension is loaded</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Sockets extension is NOT loaded (optional but recommended)</p>";
    }
    
    // Test PHPMailer with SMTP
    echo "<h3>Testing PHPMailer with Gmail SMTP...</h3>";
    echo "<form method='POST'>";
    echo "<p>Configuration to test:</p>";
    echo "<label><input type='radio' name='smtp_config' value='tls587' checked> Port 587 with TLS (Default)</label><br>";
    echo "<label><input type='radio' name='smtp_config' value='ssl465'> Port 465 with SSL</label><br>";
    echo "<label><input type='radio' name='smtp_config' value='tls587_debug'> Port 587 with TLS + Debug</label><br><br>";
    echo "<p>Send test email to: <input type='email' name='test_email_phpmailer' value='lawrenceantwi63@gmail.com' style='padding: 5px; width: 250px;'></p>";
    echo "<button type='submit' name='send_test_phpmailer' style='padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer;'>Send Test Email</button>";
    echo "</form>";
    
    if (isset($_POST['send_test_phpmailer'])) {
        $testEmail = $_POST['test_email_phpmailer'];
        $config = $_POST['smtp_config'] ?? 'tls587';
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP Configuration
            $sql = "SELECT setting_key, setting_value FROM communication_settings WHERE setting_type = 'email'";
            $result = $conn->query($sql);
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // Apply configuration based on selection
            switch ($config) {
                case 'ssl465':
                    $port = 465;
                    $encryption = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $debug = 0;
                    echo "<p><strong>Testing with Port 465 (SSL)...</strong></p>";
                    break;
                case 'tls587_debug':
                    $port = 587;
                    $encryption = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $debug = 2;
                    echo "<p><strong>Testing with Port 587 (TLS) + Debug...</strong></p>";
                    break;
                default: // tls587
                    $port = 587;
                    $encryption = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $debug = 0;
                    echo "<p><strong>Testing with Port 587 (TLS)...</strong></p>";
                    break;
            }
            
            $mail->SMTPDebug = $debug;
            $mail->Debugoutput = 'html';
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_username'];
            $mail->Password = $settings['smtp_password'];
            $mail->SMTPSecure = $encryption;
            $mail->Port = $port;
            $mail->Timeout = 30;
            
            // Disable SSL verification for testing
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Email content
            $mail->setFrom($settings['email_from_address'], $settings['email_from_name']);
            $mail->addAddress($testEmail);
            $mail->Subject = 'Test Email from Church Management System (PHPMailer)';
            $mail->Body = 'Success! PHPMailer is working correctly with Gmail SMTP.<br><br>Your email system is now fully functional!';
            $mail->AltBody = 'Success! PHPMailer is working correctly with Gmail SMTP. Your email system is now fully functional!';
            $mail->isHTML(true);
            
            // Send
            echo "<div style='background: #f3f4f6; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            $mail->send();
            echo "</div>";
            
            echo "<div style='color: green; padding: 15px; background: #d1fae5; border-radius: 5px; margin-top: 10px;'>";
            echo "✅ <strong>SUCCESS!</strong> Email sent via PHPMailer to {$testEmail}!<br>";
            echo "Check your inbox (and spam folder).<br><br>";
            echo "<strong>Working Configuration:</strong> Port {$port} with " . ($encryption == 'ssl' ? 'SSL' : 'TLS');
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 15px; background: #fee2e2; border-radius: 5px; margin-top: 10px;'>";
            echo "❌ <strong>ERROR:</strong> " . $mail->ErrorInfo . "<br><br>";
            echo "<strong>Details:</strong> " . $e->getMessage() . "<br><br>";
            echo "<strong>Possible Solutions:</strong><br>";
            echo "<ul>";
            echo "<li>Check if OpenSSL is enabled in PHP (see above)</li>";
            echo "<li>Try Port 465 with SSL instead of Port 587</li>";
            echo "<li>Check your firewall is not blocking outbound connections</li>";
            echo "<li>Verify your Gmail App Password is correct</li>";
            echo "<li>Temporarily disable antivirus/firewall to test</li>";
            echo "</ul>";
            echo "</div>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ PHPMailer is NOT installed</p>";
    echo "<div style='background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b;'>";
    echo "<h3>⚡ Quick Install Instructions</h3>";
    echo "<p><strong>Option 1: Double-click the install script</strong></p>";
    echo "<ol>";
    echo "<li>Navigate to: <code>C:\\xampp\\htdocs\\Church_Management_System\\admin_dashboard\\</code></li>";
    echo "<li>Double-click: <code><strong>install_phpmailer.bat</strong></code></li>";
    echo "<li>Wait for installation to complete</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
    echo "<p><strong>Option 2: Command line</strong></p>";
    echo "<ol>";
    echo "<li>Open Command Prompt (cmd)</li>";
    echo "<li>Run: <code>cd C:\\xampp\\htdocs\\Church_Management_System\\admin_dashboard</code></li>";
    echo "<li>Run: <code>composer install</code></li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
    echo "<p style='margin-top: 15px;'><strong>Don't have Composer?</strong> Download from: <a href='https://getcomposer.org/download/' target='_blank' style='color: #2563eb;'>getcomposer.org</a></p>";
    echo "</div>";
}

// Check email_logs table
echo "<h2>📊 Email Logs Table</h2>";
$tableCheck = $conn->query("SHOW TABLES LIKE 'email_logs'");
if ($tableCheck->num_rows === 0) {
    echo "<p style='color: orange;'>⚠️ Email logs table does not exist. Creating it...</p>";
    
    $createLogs = "CREATE TABLE IF NOT EXISTS email_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT,
        recipient_email VARCHAR(255),
        subject VARCHAR(255),
        message_content TEXT,
        status VARCHAR(50),
        error_message TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($createLogs)) {
        echo "<p style='color: green;'>✅ Email logs table created!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Email logs table exists</p>";
}

// Test sending a simple email using PHP mail()
echo "<h2>✉️ Test Email (Using PHP mail() function)</h2>";
echo "<form method='POST'>";
echo "<p>Send test email to: <input type='email' name='test_email' value='lawrenceantwi63@gmail.com' style='padding: 5px; width: 250px;'></p>";
echo "<button type='submit' name='send_test' style='padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;'>Send Test Email</button>";
echo "</form>";

if (isset($_POST['send_test'])) {
    $testEmail = $_POST['test_email'];
    $subject = "Test Email from Church Management System";
    $message = "Hello! This is a test email to verify your email configuration is working.\n\nIf you receive this, your system can send emails!";
    
    $headers = "From: Church Management System <lawrenceantwi63@gmail.com>\r\n";
    $headers .= "Reply-To: lawrenceantwi63@gmail.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    if (mail($testEmail, $subject, $message, $headers)) {
        echo "<p style='color: green; padding: 15px; background: #d1fae5; border-radius: 5px;'>✅ Test email sent successfully to {$testEmail}!</p>";
        echo "<p><strong>Note:</strong> Check your inbox (and spam folder) for the test email.</p>";
    } else {
        echo "<p style='color: red; padding: 15px; background: #fee2e2; border-radius: 5px;'>❌ Failed to send test email</p>";
        echo "<p><strong>Possible issues:</strong></p>";
        echo "<ul>";
        echo "<li>PHP mail() function may not be configured on your server</li>";
        echo "<li>XAMPP needs sendmail or SMTP configured in php.ini</li>";
        echo "<li>Install PHPMailer for better email support</li>";
        echo "</ul>";
    }
}

echo "<hr>";
echo "<h2>🔧 Gmail SMTP Configuration</h2>";
echo "<p><strong>Important:</strong> Gmail requires an App Password (not your regular password)</p>";
echo "<ol>";
echo "<li>Go to your Google Account settings</li>";
echo "<li>Enable 2-Step Verification</li>";
echo "<li>Generate an App Password for 'Mail'</li>";
echo "<li>Use that 16-character password in your settings</li>";
echo "</ol>";
echo "<p>Current password in database: <code>gotjwynclayogibo</code> - Is this an App Password?</p>";

$conn->close();
?>
