<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication System - Setup Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #2196F3;
            background: #f9f9f9;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .warning {
            color: #FF9800;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .info {
            background: #E3F2FD;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-ok {
            background: #4CAF50;
            color: white;
        }
        .status-error {
            background: #f44336;
            color: white;
        }
        .status-warning {
            background: #FF9800;
            color: white;
        }
        .btn {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #2196F3;
        }
        .btn-secondary:hover {
            background: #0b7dda;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Communication System Setup Test</h1>
        <p>This page will verify your communication system setup.</p>

        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        require_once 'db_connect.php';
        
        $allGood = true;
        
        // Test 1: Database Connection
        echo '<div class="test-section">';
        echo '<h2>✓ Test 1: Database Connection</h2>';
        if ($conn && $conn->ping()) {
            echo '<p class="success">✅ Database connection successful!</p>';
            echo '<p>Connected to: ' . htmlspecialchars($conn->server_info) . '</p>';
        } else {
            echo '<p class="error">❌ Database connection failed!</p>';
            $allGood = false;
        }
        echo '</div>';
        
        // Test 2: Check Tables
        echo '<div class="test-section">';
        echo '<h2>✓ Test 2: Database Tables</h2>';
        
        $requiredTables = [
            'messages', 'message_recipients', 'message_templates', 
            'message_groups', 'message_group_members', 'scheduled_messages',
            'sms_logs', 'email_logs', 'communication_settings'
        ];
        
        $existingTables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $existingTables[] = $row[0];
        }
        
        echo '<table>';
        echo '<tr><th>Table Name</th><th>Status</th></tr>';
        foreach ($requiredTables as $table) {
            $exists = in_array($table, $existingTables);
            $status = $exists ? 
                '<span class="status-badge status-ok">EXISTS</span>' : 
                '<span class="status-badge status-error">MISSING</span>';
            echo "<tr><td>$table</td><td>$status</td></tr>";
            if (!$exists) $allGood = false;
        }
        echo '</table>';
        
        if ($allGood) {
            echo '<p class="success">✅ All required tables exist!</p>';
        } else {
            echo '<p class="error">❌ Some tables are missing. Please run communication_system_setup.sql</p>';
        }
        echo '</div>';
        
        // Test 3: Email Configuration
        echo '<div class="test-section">';
        echo '<h2>✓ Test 3: Email Configuration</h2>';
        
        $emailSettings = [];
        $result = $conn->query("SELECT setting_key, setting_value FROM communication_settings WHERE setting_type = 'email'");
        while ($row = $result->fetch_assoc()) {
            $emailSettings[$row['setting_key']] = $row['setting_value'];
        }
        
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
        
        $emailConfigured = true;
        $criticalSettings = ['smtp_host', 'smtp_username', 'smtp_password', 'email_from_address'];
        
        foreach ($emailSettings as $key => $value) {
            $displayValue = (strpos($key, 'password') !== false) ? '********' : htmlspecialchars($value);
            $isEmpty = empty($value);
            $isCritical = in_array($key, $criticalSettings);
            
            if ($isEmpty && $isCritical) {
                $status = '<span class="status-badge status-error">NOT SET</span>';
                $emailConfigured = false;
            } elseif ($isEmpty) {
                $status = '<span class="status-badge status-warning">EMPTY</span>';
            } else {
                $status = '<span class="status-badge status-ok">SET</span>';
            }
            
            echo "<tr><td>$key</td><td>$displayValue</td><td>$status</td></tr>";
        }
        echo '</table>';
        
        if ($emailConfigured) {
            echo '<p class="success">✅ Email is configured!</p>';
            echo '<form method="POST" style="margin-top: 15px;">';
            echo '<input type="email" name="test_email" placeholder="Enter email to test" required style="padding: 8px; width: 250px; margin-right: 10px;">';
            echo '<button type="submit" name="send_test_email" class="btn">Send Test Email</button>';
            echo '</form>';
        } else {
            echo '<p class="warning">⚠️ Email is not fully configured. Update settings in database.</p>';
        }
        echo '</div>';
        
        // Test 4: SMS Configuration
        echo '<div class="test-section">';
        echo '<h2>✓ Test 4: SMS Configuration</h2>';
        
        $smsSettings = [];
        $result = $conn->query("SELECT setting_key, setting_value FROM communication_settings WHERE setting_type = 'sms'");
        while ($row = $result->fetch_assoc()) {
            $smsSettings[$row['setting_key']] = $row['setting_value'];
        }
        
        $smsProvider = $smsSettings['sms_provider'] ?? 'none';
        
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
        
        foreach ($smsSettings as $key => $value) {
            $displayValue = (strpos($key, 'secret') !== false || strpos($key, 'password') !== false) ? 
                '********' : htmlspecialchars($value);
            $isEmpty = empty($value);
            
            if ($key === 'sms_provider' && $value === 'none') {
                $status = '<span class="status-badge status-warning">DISABLED</span>';
            } elseif ($isEmpty) {
                $status = '<span class="status-badge status-warning">EMPTY</span>';
            } else {
                $status = '<span class="status-badge status-ok">SET</span>';
            }
            
            echo "<tr><td>$key</td><td>$displayValue</td><td>$status</td></tr>";
        }
        echo '</table>';
        
        if ($smsProvider !== 'none') {
            echo '<p class="success">✅ SMS provider: ' . htmlspecialchars($smsProvider) . '</p>';
            echo '<form method="POST" style="margin-top: 15px;">';
            echo '<input type="tel" name="test_phone" placeholder="Enter phone (+1234567890)" required style="padding: 8px; width: 250px; margin-right: 10px;">';
            echo '<button type="submit" name="send_test_sms" class="btn btn-secondary">Send Test SMS</button>';
            echo '</form>';
        } else {
            echo '<p class="warning">⚠️ SMS is not configured. This is optional.</p>';
        }
        echo '</div>';
        
        // Test 5: Members Data
        echo '<div class="test-section">';
        echo '<h2>✓ Test 5: Members Data</h2>';
        
        $memberStats = $conn->query("
            SELECT 
                COUNT(*) as total_members,
                COUNT(email) as members_with_email,
                COUNT(phone) as members_with_phone,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_members
            FROM members
        ")->fetch_assoc();
        
        echo '<table>';
        echo '<tr><th>Metric</th><th>Count</th></tr>';
        echo '<tr><td>Total Members</td><td>' . $memberStats['total_members'] . '</td></tr>';
        echo '<tr><td>Active Members</td><td>' . $memberStats['active_members'] . '</td></tr>';
        echo '<tr><td>Members with Email</td><td>' . $memberStats['members_with_email'] . '</td></tr>';
        echo '<tr><td>Members with Phone</td><td>' . $memberStats['members_with_phone'] . '</td></tr>';
        echo '</table>';
        
        if ($memberStats['active_members'] > 0) {
            echo '<p class="success">✅ You have ' . $memberStats['active_members'] . ' active members!</p>';
        } else {
            echo '<p class="warning">⚠️ No active members found. Add members first.</p>';
        }
        echo '</div>';
        
        // Test 6: Message Groups
        echo '<div class="test-section">';
        echo '<h2>✓ Test 6: Message Groups</h2>';
        
        $groupCount = $conn->query("SELECT COUNT(*) as cnt FROM message_groups")->fetch_assoc()['cnt'];
        
        if ($groupCount > 0) {
            echo '<p class="success">✅ You have ' . $groupCount . ' message groups configured!</p>';
            
            $groups = $conn->query("
                SELECT g.group_name, 
                       (SELECT COUNT(*) FROM message_group_members WHERE group_id = g.group_id) as member_count
                FROM message_groups g
            ");
            
            echo '<table>';
            echo '<tr><th>Group Name</th><th>Members</th></tr>';
            while ($group = $groups->fetch_assoc()) {
                echo '<tr><td>' . htmlspecialchars($group['group_name']) . '</td><td>' . $group['member_count'] . '</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warning">⚠️ No message groups found. Default groups should have been created.</p>';
        }
        echo '</div>';
        
        // Handle test email
        if (isset($_POST['send_test_email'])) {
            echo '<div class="test-section">';
            echo '<h2>📧 Email Test Result</h2>';
            
            require_once 'email_handler.php';
            
            $testEmail = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
            if ($testEmail) {
                $result = sendEmail(
                    $testEmail,
                    'Test Email - Church Management System',
                    "Congratulations! Your email configuration is working correctly.\n\nThis is a test email from your Communication System.\n\nYou can now send emails to your members!",
                    null
                );
                
                if ($result) {
                    echo '<p class="success">✅ Test email sent successfully to ' . htmlspecialchars($testEmail) . '!</p>';
                    echo '<p>Check your inbox (and spam folder).</p>';
                } else {
                    echo '<p class="error">❌ Failed to send test email.</p>';
                    echo '<p>Check email_logs table for error details:</p>';
                    
                    $logs = $conn->query("SELECT * FROM email_logs ORDER BY created_at DESC LIMIT 1");
                    if ($log = $logs->fetch_assoc()) {
                        echo '<div class="info">';
                        echo '<strong>Error:</strong> ' . htmlspecialchars($log['error_message'] ?? 'Unknown error');
                        echo '</div>';
                    }
                }
            } else {
                echo '<p class="error">❌ Invalid email address</p>';
            }
            echo '</div>';
        }
        
        // Handle test SMS
        if (isset($_POST['send_test_sms'])) {
            echo '<div class="test-section">';
            echo '<h2>📱 SMS Test Result</h2>';
            
            require_once 'sms_handler.php';
            
            $testPhone = $_POST['test_phone'];
            $result = sendSMS(
                $testPhone,
                'Test SMS from Church Management System. Your SMS is working!',
                null
            );
            
            if ($result) {
                echo '<p class="success">✅ Test SMS sent successfully to ' . htmlspecialchars($testPhone) . '!</p>';
            } else {
                echo '<p class="error">❌ Failed to send test SMS.</p>';
                echo '<p>Check sms_logs table for error details:</p>';
                
                $logs = $conn->query("SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 1");
                if ($log = $logs->fetch_assoc()) {
                    echo '<div class="info">';
                    echo '<strong>Error:</strong> ' . htmlspecialchars($log['error_message'] ?? 'Unknown error');
                    echo '</div>';
                }
            }
            echo '</div>';
        }
        
        // Final Summary
        echo '<div class="test-section" style="border-left-color: #4CAF50; background: #E8F5E9;">';
        echo '<h2>📋 Summary</h2>';
        
        if ($allGood && $emailConfigured) {
            echo '<p class="success" style="font-size: 18px;">🎉 Your communication system is ready to use!</p>';
            echo '<a href="communication.html" class="btn" style="display: inline-block; text-decoration: none; margin-top: 10px;">Go to Communication Page</a>';
        } else {
            echo '<p class="warning" style="font-size: 18px;">⚠️ Setup is incomplete. Please complete the missing steps.</p>';
            echo '<div class="info" style="margin-top: 15px;">';
            echo '<strong>Next Steps:</strong><br>';
            if (!$allGood) echo '1. Run communication_system_setup.sql in phpMyAdmin<br>';
            if (!$emailConfigured) echo '2. Configure email settings (see QUICK_SETUP_GUIDE.txt)<br>';
            echo '3. Return to this page to verify setup';
            echo '</div>';
        }
        echo '</div>';
        
        $conn->close();
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
            <h3>📚 Documentation</h3>
            <ul>
                <li><strong>QUICK_SETUP_GUIDE.txt</strong> - Quick setup instructions</li>
                <li><strong>COMMUNICATION_SYSTEM_GUIDE.md</strong> - Complete documentation</li>
                <li><strong>communication.html</strong> - Main interface</li>
            </ul>
        </div>
    </div>
</body>
</html>
