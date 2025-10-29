<!DOCTYPE html>
<html>
<head>
    <title>Executive Summary Diagnostics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .test { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { border-left: 4px solid #10b981; }
        .error { border-left: 4px solid #ef4444; }
        .warning { border-left: 4px solid #f59e0b; }
        .status { font-weight: bold; font-size: 18px; }
        .success .status { color: #10b981; }
        .error .status { color: #ef4444; }
        .warning .status { color: #f59e0b; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>🔍 Executive Summary Diagnostics</h1>
    
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Test 1: PHP Version
    echo '<div class="test success">';
    echo '<div class="status">✅ PHP Version</div>';
    echo '<p>PHP Version: ' . phpversion() . '</p>';
    echo '</div>';
    
    // Test 2: Database Connection
    echo '<h2>Database Connection</h2>';
    try {
        $conn = new mysqli('localhost', 'root', '', 'church_management_system');
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        echo '<div class="test success">';
        echo '<div class="status">✅ Database Connected</div>';
        echo '<p>Successfully connected to: church_management_system</p>';
        echo '</div>';
        
        // Test 3: Check Tables
        echo '<h2>Database Tables</h2>';
        $tables = ['members', 'attendance', 'events', 'messages', 'offerings', 'tithes', 'project_offerings', 'welfare_contributions', 'expenses'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
                echo '<div class="test success">';
                echo '<div class="status">✅ Table: ' . $table . '</div>';
                echo '<p>Records: ' . $count . '</p>';
                echo '</div>';
            } else {
                echo '<div class="test error">';
                echo '<div class="status">❌ Table: ' . $table . '</div>';
                echo '<p>Table does not exist!</p>';
                echo '</div>';
            }
        }
        
        // Test 4: Test API Query
        echo '<h2>API Query Test</h2>';
        $sql = "SELECT 
                    COUNT(*) as total_members,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_members
                FROM members";
        $result = $conn->query($sql);
        if ($result) {
            $data = $result->fetch_assoc();
            echo '<div class="test success">';
            echo '<div class="status">✅ Query Successful</div>';
            echo '<p>Total Members: ' . $data['total_members'] . '</p>';
            echo '<p>Active Members: ' . $data['active_members'] . '</p>';
            echo '</div>';
        } else {
            echo '<div class="test error">';
            echo '<div class="status">❌ Query Failed</div>';
            echo '<p>Error: ' . $conn->error . '</p>';
            echo '</div>';
        }
        
        // Test 5: Test Full API
        echo '<h2>Full API Test</h2>';
        $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/get_executive_summary.php';
        echo '<div class="test">';
        echo '<p><strong>API URL:</strong> <a href="' . $apiUrl . '" target="_blank">' . $apiUrl . '</a></p>';
        echo '<p>Click the link above to test the API directly</p>';
        echo '</div>';
        
        $conn->close();
        
    } catch (Exception $e) {
        echo '<div class="test error">';
        echo '<div class="status">❌ Database Error</div>';
        echo '<p>Error: ' . $e->getMessage() . '</p>';
        echo '</div>';
    }
    
    // Test 6: File Permissions
    echo '<h2>File Permissions</h2>';
    $files = [
        'get_executive_summary.php',
        'executive_summary.js',
        'report.html'
    ];
    foreach ($files as $file) {
        if (file_exists($file)) {
            $perms = fileperms($file);
            echo '<div class="test success">';
            echo '<div class="status">✅ File: ' . $file . '</div>';
            echo '<p>Readable: ' . (is_readable($file) ? 'Yes' : 'No') . '</p>';
            echo '<p>Size: ' . filesize($file) . ' bytes</p>';
            echo '</div>';
        } else {
            echo '<div class="test error">';
            echo '<div class="status">❌ File: ' . $file . '</div>';
            echo '<p>File not found!</p>';
            echo '</div>';
        }
    }
    
    // Test 7: JavaScript Test
    echo '<h2>JavaScript Test</h2>';
    echo '<div class="test">';
    echo '<div id="js-test-result">Testing JavaScript...</div>';
    echo '<script>
        document.getElementById("js-test-result").innerHTML = "<div class=\'status\' style=\'color: #10b981;\'>✅ JavaScript Working</div><p>JavaScript is enabled and functioning</p>";
    </script>';
    echo '</div>';
    
    // Test 8: Fetch API Test
    echo '<h2>Fetch API Test</h2>';
    echo '<div class="test">';
    echo '<div id="fetch-test-result">Testing API fetch...</div>';
    echo '<button onclick="testFetch()" class="btn">Test Fetch API</button>';
    echo '<pre id="fetch-response" style="display:none;"></pre>';
    echo '</div>';
    
    echo '<script>
    async function testFetch() {
        const resultDiv = document.getElementById("fetch-test-result");
        const responseDiv = document.getElementById("fetch-response");
        
        try {
            resultDiv.innerHTML = "Fetching...";
            const response = await fetch("get_executive_summary.php");
            const data = await response.json();
            
            if (data.success) {
                resultDiv.innerHTML = "<div class=\'status\' style=\'color: #10b981;\'>✅ API Fetch Successful</div><p>Data received successfully</p>";
                responseDiv.style.display = "block";
                responseDiv.textContent = JSON.stringify(data, null, 2);
            } else {
                resultDiv.innerHTML = "<div class=\'status\' style=\'color: #ef4444;\'>❌ API Error</div><p>" + data.message + "</p>";
                responseDiv.style.display = "block";
                responseDiv.textContent = JSON.stringify(data, null, 2);
            }
        } catch (error) {
            resultDiv.innerHTML = "<div class=\'status\' style=\'color: #ef4444;\'>❌ Fetch Failed</div><p>" + error.message + "</p>";
        }
    }
    </script>';
    ?>
    
    <h2>Actions</h2>
    <div class="test">
        <a href="get_executive_summary.php" target="_blank" class="btn">Test API Directly</a>
        <a href="report.html" class="btn" style="background: #10b981;">Open Report Page</a>
        <a href="diagnose.php" class="btn" style="background: #f59e0b;">Refresh Diagnostics</a>
    </div>
    
    <h2>Next Steps</h2>
    <div class="test">
        <ol>
            <li>If all tests pass ✅, the Executive Summary should work</li>
            <li>If any test fails ❌, fix that issue first</li>
            <li>Click "Test API Directly" to see the raw JSON response</li>
            <li>Click "Test Fetch API" to test the JavaScript fetch</li>
            <li>Open "Report Page" and check browser console (F12) for errors</li>
        </ol>
    </div>
</body>
</html>
