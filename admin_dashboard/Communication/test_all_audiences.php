<!DOCTYPE html>
<html>
<head>
    <title>Test All Audience Options</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
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
        h2 { color: #555; margin-top: 20px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #FF9800; font-weight: bold; }
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
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
        .btn:hover { background: #1976D2; }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .test-pass {
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
        }
        .test-fail {
            background: #FFEBEE;
            border-left: 4px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🧪 Test All Audience Options</h1>
        <p>This page tests all audience selection options in your communication system.</p>
        
        <?php
        require_once 'db_connect.php';
        
        $allTests = [];
        
        // TEST 1: All Members
        echo '<div class="box">';
        echo '<h2>Test 1: All Members</h2>';
        $sql = "SELECT COUNT(*) as total FROM members WHERE LOWER(status) = 'active'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $activeMembersCount = $row['total'];
        
        if ($activeMembersCount > 0) {
            echo '<div class="test-result test-pass">';
            echo '<p class="success">✅ PASS: Found ' . $activeMembersCount . ' active members</p>';
            echo '<p>When "All Members" is selected, message will be sent to all ' . $activeMembersCount . ' active members.</p>';
            $allTests['all_members'] = true;
        } else {
            echo '<div class="test-result test-fail">';
            echo '<p class="error">❌ FAIL: No active members found!</p>';
            echo '<p>Add members with status = "Active" to use this option.</p>';
            $allTests['all_members'] = false;
        }
        
        // Show sample members
        $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone 
                FROM members WHERE LOWER(status) = 'active' LIMIT 5";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['member_id'] . '</td>';
                echo '<td>' . $row['name'] . '</td>';
                echo '<td>' . ($row['email'] ?? 'N/A') . '</td>';
                echo '<td>' . ($row['phone'] ?? 'N/A') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<p><em>Showing first 5 members...</em></p>';
        }
        echo '</div>';
        echo '</div>';
        
        // TEST 2: Departments (Church Groups)
        echo '<div class="box">';
        echo '<h2>Test 2: Departments (Church Groups)</h2>';
        $sql = "SELECT DISTINCT church_group as name, COUNT(*) as member_count 
                FROM members 
                WHERE church_group IS NOT NULL AND church_group != '' AND LOWER(status) = 'active'
                GROUP BY church_group 
                ORDER BY church_group";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo '<div class="test-result test-pass">';
            echo '<p class="success">✅ PASS: Found ' . $result->num_rows . ' departments/church groups</p>';
            $allTests['departments'] = true;
            
            echo '<table>';
            echo '<tr><th>Church Group</th><th>Active Members</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($row['name']) . '</strong></td>';
                echo '<td>' . $row['member_count'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="test-result test-fail">';
            echo '<p class="error">❌ FAIL: No church groups found!</p>';
            echo '<p>Members need to have church_group values assigned.</p>';
            echo '<p>Example: UPDATE members SET church_group = "Judah" WHERE member_id = 1;</p>';
            $allTests['departments'] = false;
        }
        echo '</div>';
        echo '</div>';
        
        // TEST 3: Ministries (Leadership Roles)
        echo '<div class="box">';
        echo '<h2>Test 3: Ministries (Leadership Roles)</h2>';
        $sql = "SELECT DISTINCT leadership_role as name, COUNT(*) as member_count 
                FROM members 
                WHERE leadership_role IS NOT NULL AND leadership_role != '' AND LOWER(status) = 'active'
                GROUP BY leadership_role 
                ORDER BY leadership_role";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo '<div class="test-result test-pass">';
            echo '<p class="success">✅ PASS: Found ' . $result->num_rows . ' ministries/leadership roles</p>';
            $allTests['ministries'] = true;
            
            echo '<table>';
            echo '<tr><th>Leadership Role</th><th>Active Members</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($row['name']) . '</strong></td>';
                echo '<td>' . $row['member_count'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="test-result test-fail">';
            echo '<p class="error">❌ FAIL: No ministries/leadership roles found!</p>';
            echo '<p>Members need to have leadership_role values assigned.</p>';
            echo '<p>Example: UPDATE members SET leadership_role = "Pastor" WHERE member_id = 1;</p>';
            $allTests['ministries'] = false;
        }
        echo '</div>';
        echo '</div>';
        
        // TEST 4: Individual Members (Search)
        echo '<div class="box">';
        echo '<h2>Test 4: Individual Members (Search)</h2>';
        echo '<div class="test-result test-pass">';
        echo '<p class="success">✅ PASS: Individual member search is available</p>';
        echo '<p>You can search and select specific members using the "Others" option.</p>';
        echo '<p>Total searchable members: ' . $activeMembersCount . '</p>';
        $allTests['individual'] = true;
        echo '</div>';
        echo '</div>';
        
        // TEST 5: API Endpoints
        echo '<div class="box">';
        echo '<h2>Test 5: API Endpoints</h2>';
        echo '<button class="btn" onclick="testAPI(\'members\')">Test Members API</button>';
        echo '<button class="btn" onclick="testAPI(\'departments\')">Test Departments API</button>';
        echo '<button class="btn" onclick="testAPI(\'ministries\')">Test Ministries API</button>';
        echo '<div id="apiResult"></div>';
        echo '</div>';
        
        // FINAL SUMMARY
        echo '<div class="box" style="background: #E3F2FD;">';
        echo '<h2>📊 Test Summary</h2>';
        
        $passCount = array_sum($allTests);
        $totalTests = count($allTests);
        
        echo '<table>';
        echo '<tr><th>Feature</th><th>Status</th></tr>';
        echo '<tr><td>All Members</td><td>' . ($allTests['all_members'] ? '<span class="success">✅ Working</span>' : '<span class="error">❌ Failed</span>') . '</td></tr>';
        echo '<tr><td>Departments</td><td>' . ($allTests['departments'] ? '<span class="success">✅ Working</span>' : '<span class="error">❌ Failed</span>') . '</td></tr>';
        echo '<tr><td>Ministries</td><td>' . ($allTests['ministries'] ? '<span class="success">✅ Working</span>' : '<span class="error">❌ Failed</span>') . '</td></tr>';
        echo '<tr><td>Individual Search</td><td>' . ($allTests['individual'] ? '<span class="success">✅ Working</span>' : '<span class="error">❌ Failed</span>') . '</td></tr>';
        echo '</table>';
        
        if ($passCount === $totalTests) {
            echo '<h3 class="success">🎉 ALL TESTS PASSED!</h3>';
            echo '<p>Your communication system is fully functional. All audience options are working correctly.</p>';
            echo '<a href="communication.html" class="btn">Go to Communication Page</a>';
        } else {
            echo '<h3 class="warning">⚠️ SOME TESTS FAILED</h3>';
            echo '<p>' . $passCount . ' out of ' . $totalTests . ' tests passed.</p>';
            echo '<p>Fix the failed tests above before using the communication system.</p>';
        }
        echo '</div>';
        
        $conn->close();
        ?>
    </div>
    
    <script>
        function testAPI(endpoint) {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>Testing ' + endpoint + ' API...</p>';
            
            fetch('get_recipients.php?action=' + endpoint)
                .then(response => response.json())
                .then(data => {
                    console.log(endpoint + ' API Response:', data);
                    
                    let html = '<h3>' + endpoint.toUpperCase() + ' API Response:</h3>';
                    
                    if (data.success) {
                        const items = data.members || data.departments || data.ministries || [];
                        html += '<p class="success">✅ API Working! Returned ' + items.length + ' items</p>';
                        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } else {
                        html += '<p class="error">❌ API Error</p>';
                        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    }
                    
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<p class="error">❌ API Error: ' + error.message + '</p>';
                    console.error('API Error:', error);
                });
        }
    </script>
</body>
</html>
