<!DOCTYPE html>
<html>
<head>
    <title>Test API - Get Members</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #2196F3;
        }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #2196F3;
            color: white;
        }
        .btn {
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 API Test - Get Members</h1>
        
        <div class="test-section">
            <h2>Test 1: Direct Database Query</h2>
            <p>Checking members directly from database...</p>
            <?php
            require_once 'db_connect.php';
            
            // Get all members
            $sql = "SELECT member_id, first_name, last_name, 
                    CONCAT(first_name, ' ', last_name) as full_name, 
                    email, phone, status 
                    FROM members 
                    ORDER BY first_name, last_name";
            $result = $conn->query($sql);
            
            if ($result) {
                echo '<p class="success">✅ Found ' . $result->num_rows . ' members in database</p>';
                
                echo '<table>';
                echo '<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Status</th></tr>';
                
                while ($row = $result->fetch_assoc()) {
                    $statusColor = $row['status'] === 'Active' ? 'green' : 'red';
                    echo '<tr>';
                    echo '<td>' . $row['member_id'] . '</td>';
                    echo '<td>' . $row['first_name'] . '</td>';
                    echo '<td>' . $row['last_name'] . '</td>';
                    echo '<td><strong>' . $row['full_name'] . '</strong></td>';
                    echo '<td>' . ($row['email'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($row['phone'] ?? 'N/A') . '</td>';
                    echo '<td style="color: ' . $statusColor . '"><strong>' . $row['status'] . '</strong></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="error">❌ Database query failed: ' . $conn->error . '</p>';
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>Test 2: Active Members Only</h2>
            <p>Members with status = 'Active' (what API returns)...</p>
            <?php
            $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone, status 
                    FROM members 
                    WHERE status = 'Active' 
                    ORDER BY first_name, last_name";
            $result = $conn->query($sql);
            
            if ($result) {
                echo '<p class="success">✅ Found ' . $result->num_rows . ' active members</p>';
                
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>';
                
                $members = [];
                while ($row = $result->fetch_assoc()) {
                    $members[] = $row;
                    echo '<tr>';
                    echo '<td>' . $row['member_id'] . '</td>';
                    echo '<td><strong>' . $row['name'] . '</strong></td>';
                    echo '<td>' . ($row['email'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($row['phone'] ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '<h3>JSON Output (what API returns):</h3>';
                echo '<pre>' . json_encode(['success' => true, 'members' => $members], JSON_PRETTY_PRINT) . '</pre>';
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>Test 3: Search for "Lawrence"</h2>
            <?php
            $searchTerm = '%Lawrence%';
            $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone, status 
                    FROM members 
                    WHERE (first_name LIKE ? OR last_name LIKE ?) 
                    ORDER BY first_name, last_name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            
            echo '<p>Searching for members with "Lawrence" in first or last name...</p>';
            
            if ($result->num_rows > 0) {
                echo '<p class="success">✅ Found ' . $result->num_rows . ' members</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr>';
                
                while ($row = $result->fetch_assoc()) {
                    $statusColor = $row['status'] === 'Active' ? 'green' : 'red';
                    echo '<tr>';
                    echo '<td>' . $row['member_id'] . '</td>';
                    echo '<td><strong>' . $row['name'] . '</strong></td>';
                    echo '<td>' . ($row['email'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($row['phone'] ?? 'N/A') . '</td>';
                    echo '<td style="color: ' . $statusColor . '"><strong>' . $row['status'] . '</strong></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="error">❌ No members found matching "Lawrence"</p>';
            }
            $stmt->close();
            ?>
        </div>
        
        <div class="test-section">
            <h2>Test 4: Live API Test</h2>
            <p>Click button to test the actual API endpoint:</p>
            <button class="btn" onclick="testAPI()">Test get_recipients.php?action=members</button>
            <div id="apiResult"></div>
        </div>
        
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
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    
                    if (data.success && data.members) {
                        html += '<p class="success">✅ API returned ' + data.members.length + ' members</p>';
                        html += '<table>';
                        html += '<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>';
                        
                        data.members.forEach(member => {
                            html += '<tr>';
                            html += '<td>' + member.member_id + '</td>';
                            html += '<td><strong>' + member.name + '</strong></td>';
                            html += '<td>' + (member.email || 'N/A') + '</td>';
                            html += '<td>' + (member.phone || 'N/A') + '</td>';
                            html += '</tr>';
                        });
                        html += '</table>';
                        
                        // Test search for Lawrence
                        const lawrence = data.members.find(m => m.name.toLowerCase().includes('lawrence'));
                        if (lawrence) {
                            html += '<p class="success">✅ FOUND Lawrence in API results: ' + lawrence.name + '</p>';
                        } else {
                            html += '<p class="error">❌ Lawrence NOT found in API results</p>';
                        }
                    } else {
                        html += '<p class="error">❌ API error or no members returned</p>';
                    }
                    
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<p class="error">❌ Error: ' + error.message + '</p>';
                    console.error('API Error:', error);
                });
        }
    </script>
</body>
</html>
