<?php
/**
 * Test Executive Summary API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Executive Summary API</h2>";

// Test 1: Check if db_config.php exists
echo "<h3>Test 1: Database Config File</h3>";
$dbConfigPath = '../Members/db_config.php';
if (file_exists($dbConfigPath)) {
    echo "✅ db_config.php exists<br>";
    require_once $dbConfigPath;
    
    // Test 2: Try to connect
    echo "<h3>Test 2: Database Connection</h3>";
    try {
        $conn = getDBConnection();
        echo "✅ Database connection successful<br>";
        echo "Connection type: " . get_class($conn) . "<br>";
        
        // Test 3: Test a simple query
        echo "<h3>Test 3: Simple Query</h3>";
        $result = $conn->query("SELECT COUNT(*) as count FROM members");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Query successful<br>";
            echo "Total members: " . $row['count'] . "<br>";
        } else {
            echo "❌ Query failed: " . $conn->error . "<br>";
        }
        
        // Test 4: Call the actual API
        echo "<h3>Test 4: API Response</h3>";
        echo "<a href='get_executive_summary.php' target='_blank'>Click to test API</a><br>";
        
        $conn->close();
        
    } catch (Exception $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ db_config.php NOT found at: " . realpath($dbConfigPath) . "<br>";
    echo "Current directory: " . __DIR__ . "<br>";
    
    // Try alternative path
    echo "<h3>Trying alternative path...</h3>";
    $altPath = __DIR__ . '/../Members/db_config.php';
    echo "Checking: $altPath<br>";
    if (file_exists($altPath)) {
        echo "✅ Found at alternative path!<br>";
    } else {
        echo "❌ Not found at alternative path either<br>";
    }
}

echo "<hr>";
echo "<h3>Directory Structure</h3>";
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Parent directory: " . dirname(__DIR__) . "<br>";

// List files in parent directory
echo "<h4>Files in parent directory:</h4>";
$parentDir = dirname(__DIR__);
if (is_dir($parentDir)) {
    $files = scandir($parentDir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $fullPath = $parentDir . '/' . $file;
            $type = is_dir($fullPath) ? '[DIR]' : '[FILE]';
            echo "<li>$type $file</li>";
        }
    }
    echo "</ul>";
}
?>
