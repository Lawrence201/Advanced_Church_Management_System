<!DOCTYPE html>
<html>
<head>
    <title>Verify Communication Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        .btn {
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>✅ Communication Setup Verification</h1>
        
        <h2>Step 1: Database Connection</h2>
        <?php
        require_once 'db_connect.php';
        
        if ($conn) {
            echo '<p class="success">✅ Database connected successfully!</p>';
            echo '<p>Connection: MySQLi</p>';
            echo '<p>Database: church_management_system</p>';
        } else {
            echo '<p class="error">❌ Database connection failed!</p>';
            exit;
        }
        ?>
        
        <h2>Step 2: Check Members Table</h2>
        <?php
        $sql = "SELECT COUNT(*) as total FROM members";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        
        echo '<p class="success">✅ Members table exists!</p>';
        echo '<p>Total members: ' . $row['total'] . '</p>';
        
        // Get active members
        $sql = "SELECT COUNT(*) as total FROM members WHERE LOWER(status) = 'active'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        echo '<p>Active members: ' . $row['total'] . '</p>';
        ?>
        
        <h2>Step 3: Test API Endpoint</h2>
        <button class="btn" onclick="testAPI()">Test get_recipients.php</button>
        <div id="apiResult"></div>
        
        <h2>Step 4: Search for Lawrence</h2>
        <?php
        $sql = "SELECT member_id, first_name, last_name, 
                CONCAT(first_name, ' ', last_name) as full_name, 
                email, phone, status 
                FROM members 
                WHERE first_name LIKE '%Lawrence%' OR last_name LIKE '%Lawrence%'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo '<p class="success">✅ Found Lawrence in database!</p>';
            while ($row = $result->fetch_assoc()) {
                echo '<p>';
                echo '<strong>Name:</strong> ' . $row['full_name'] . '<br>';
                echo '<strong>Status:</strong> ' . $row['status'] . '<br>';
                echo '<strong>Email:</strong> ' . ($row['email'] ?? 'N/A') . '<br>';
                echo '<strong>Phone:</strong> ' . ($row['phone'] ?? 'N/A');
                echo '</p>';
                
                if (strtolower($row['status']) !== 'active') {
                    echo '<p class="error">⚠️ Lawrence status is "' . $row['status'] . '" not "Active"</p>';
                    echo '<p>Fix with SQL: <code>UPDATE members SET status = "Active" WHERE member_id = ' . $row['member_id'] . ';</code></p>';
                }
            }
        } else {
            echo '<p class="error">❌ Lawrence not found in database!</p>';
        }
        ?>
        
        <h2>Step 5: Next Steps</h2>
        <p>If all tests pass, try these:</p>
        <a href="communication.html" class="btn">Open Communication Page</a>
        <a href="test_member_search.html" class="btn">Test Member Search</a>
        
        <?php $conn->close(); ?>
    </div>
    
    <script>
        function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>Loading...</p>';
            
            fetch('get_recipients.php?action=members')
                .then(response => response.json())
                .then(data => {
                    console.log('API Response:', data);
                    
                    let html = '<h3>API Response:</h3>';
                    
                    if (data.success && data.members) {
                        html += '<p class="success">✅ API working! Returned ' + data.members.length + ' members</p>';
                        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        
                        // Search for Lawrence
                        const lawrence = data.members.find(m => m.name.toLowerCase().includes('lawrence'));
                        if (lawrence) {
                            html += '<p class="success">✅ Lawrence found in API: ' + lawrence.name + '</p>';
                        } else {
                            html += '<p class="error">❌ Lawrence NOT in API results (check status)</p>';
                        }
                    } else {
                        html += '<p class="error">❌ API error</p>';
                        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    }
                    
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<p class="error">❌ API Error: ' + error.message + '</p>';
                    console.error('Error:', error);
                });
        }
    </script>
</body>
</html>
