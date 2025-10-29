<?php
/**
 * Update Email Settings to Port 465 (SSL)
 * Run this once to update your database settings
 */

require_once 'db_connect.php';

echo "<h2>📧 Updating Email Settings to Port 465 (SSL)</h2>";

$updates = [
    ['smtp_port', '465'],
    ['smtp_encryption', 'ssl']
];

foreach ($updates as $update) {
    $sql = "UPDATE communication_settings 
            SET setting_value = ? 
            WHERE setting_type = 'email' AND setting_key = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $update[1], $update[0]);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Updated {$update[0]} to: <strong>{$update[1]}</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to update {$update[0]}</p>";
    }
    $stmt->close();
}

echo "<hr>";
echo "<h3>Current Email Settings:</h3>";

$sql = "SELECT setting_key, setting_value FROM communication_settings WHERE setting_type = 'email'";
$result = $conn->query($sql);

if ($result) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $displayValue = $row['setting_key'] === 'smtp_password' 
            ? str_repeat('*', 16) 
            : $row['setting_value'];
        
        $highlight = in_array($row['setting_key'], ['smtp_port', 'smtp_encryption']) 
            ? "background: #d1fae5;" 
            : "";
        
        echo "<tr style='$highlight'><td><strong>{$row['setting_key']}</strong></td><td>{$displayValue}</td></tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<div style='background: #dbeafe; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
echo "<h3>✅ Settings Updated!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Configure Windows Firewall (see instructions below)</li>";
echo "<li>Test sending from Communication page</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
    }
</style>

<hr>
<h2>🔥 Configure Windows Firewall to Allow Port 465</h2>

<div style="background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b;">
    <h3>Option 1: Add Firewall Exception for Apache</h3>
    <ol>
        <li>Press <kbd>Win + R</kbd>, type <code>wf.msc</code>, press Enter</li>
        <li>Click <strong>"Outbound Rules"</strong> in left panel</li>
        <li>Click <strong>"New Rule..."</strong> in right panel</li>
        <li>Select <strong>"Port"</strong>, click Next</li>
        <li>Select <strong>"TCP"</strong>, enter port: <code>465</code>, click Next</li>
        <li>Select <strong>"Allow the connection"</strong>, click Next</li>
        <li>Check all profiles (Domain, Private, Public), click Next</li>
        <li>Name: <code>Apache SMTP Port 465</code>, click Finish</li>
    </ol>
</div>

<div style="background: #dbeafe; padding: 20px; border-radius: 8px; margin-top: 20px;">
    <h3>Option 2: Add Exception for PHP</h3>
    <ol>
        <li>Press <kbd>Win + R</kbd>, type <code>wf.msc</code>, press Enter</li>
        <li>Click <strong>"Outbound Rules"</strong> in left panel</li>
        <li>Click <strong>"New Rule..."</strong> in right panel</li>
        <li>Select <strong>"Program"</strong>, click Next</li>
        <li>Browse to: <code>C:\xampp\php\php.exe</code>, click Next</li>
        <li>Select <strong>"Allow the connection"</strong>, click Next</li>
        <li>Check all profiles, click Next</li>
        <li>Name: <code>PHP Email SMTP</code>, click Finish</li>
    </ol>
</div>

<div style="background: #d1fae5; padding: 20px; border-radius: 8px; margin-top: 20px;">
    <h3>Option 3: Quick Test (Temporary)</h3>
    <p>To test if firewall is the issue:</p>
    <ol>
        <li>Open Windows Security</li>
        <li>Go to <strong>Firewall & network protection</strong></li>
        <li>Click your active network (Private/Public)</li>
        <li>Turn <strong>Microsoft Defender Firewall OFF</strong> temporarily</li>
        <li>Send a test email from Communication page</li>
        <li>Turn firewall back ON</li>
        <li>Then add the proper exception using Option 1 or 2</li>
    </ol>
</div>
