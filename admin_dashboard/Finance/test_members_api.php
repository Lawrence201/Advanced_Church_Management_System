<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Members API</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #5568d3;
        }
        .member-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Member API Test</h1>
        
        <div class="test-section">
            <h2>Test 1: Direct API Call</h2>
            <button onclick="testDirectAPI()">Test API Endpoint</button>
            <div id="apiResult"></div>
        </div>

        <div class="test-section">
            <h2>Test 2: Load Members (Like Autocomplete)</h2>
            <button onclick="testLoadMembers()">Load Members</button>
            <div id="membersResult"></div>
        </div>

        <div class="test-section">
            <h2>Test 3: Search Members</h2>
            <input type="text" id="searchInput" placeholder="Type to search..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            <div id="searchResults" style="margin-top: 10px;"></div>
        </div>
    </div>

    <script>
        let allMembers = [];

        // Test 1: Direct API call
        function testDirectAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>Loading...</p>';

            fetch('get_member_payments.php?action=get_members')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    resultDiv.innerHTML = `
                        <h3 class="success">✅ Raw Response:</h3>
                        <pre>${text}</pre>
                    `;
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        if (data.success && data.data) {
                            resultDiv.innerHTML += `
                                <h3 class="success">✅ Found ${data.data.length} members!</h3>
                            `;
                        } else {
                            resultDiv.innerHTML += `
                                <h3 class="error">❌ API returned success=false</h3>
                                <p>Message: ${data.message || 'Unknown error'}</p>
                            `;
                        }
                    } catch (e) {
                        resultDiv.innerHTML += `
                            <h3 class="error">❌ Failed to parse JSON</h3>
                            <p>Error: ${e.message}</p>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    resultDiv.innerHTML = `
                        <h3 class="error">❌ Fetch Error:</h3>
                        <pre>${error.message}</pre>
                    `;
                });
        }

        // Test 2: Load members like the autocomplete does
        function testLoadMembers() {
            const resultDiv = document.getElementById('membersResult');
            resultDiv.innerHTML = '<p>Loading members...</p>';

            fetch('get_member_payments.php?action=get_members')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        allMembers = result.data;
                        console.log('Loaded members:', allMembers);
                        
                        resultDiv.innerHTML = `
                            <h3 class="success">✅ Loaded ${allMembers.length} members successfully!</h3>
                        `;
                        
                        allMembers.forEach(member => {
                            const initials = member.full_name.split(' ').map(n => n[0]).join('').toUpperCase();
                            let avatarHTML;
                            if (member.photo_path && member.photo_path !== 'NULL' && member.photo_path.trim() !== '') {
                                avatarHTML = `<img src="../Members/${member.photo_path}" alt="${member.full_name}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb;">`;
                            } else {
                                avatarHTML = `<div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 18px;">${initials}</div>`;
                            }
                            
                            resultDiv.innerHTML += `
                                <div class="member-card" style="display: flex; align-items: center; gap: 12px;">
                                    ${avatarHTML}
                                    <div>
                                        <strong>ID:</strong> ${member.id}<br>
                                        <strong>Name:</strong> ${member.full_name}<br>
                                        <strong>Email:</strong> ${member.email || 'N/A'}<br>
                                        <strong>Phone:</strong> ${member.phone || 'N/A'}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        resultDiv.innerHTML = `
                            <h3 class="error">❌ Failed to load members</h3>
                            <p>Message: ${result.message || 'Unknown error'}</p>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = `
                        <h3 class="error">❌ Error loading members:</h3>
                        <pre>${error.message}</pre>
                    `;
                });
        }

        // Test 3: Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');

            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                
                if (!query || allMembers.length === 0) {
                    searchResults.innerHTML = '<p style="color: #999;">Type to search... (Load members first)</p>';
                    return;
                }

                const filtered = allMembers.filter(member => 
                    member.full_name.toLowerCase().includes(query) ||
                    (member.email && member.email.toLowerCase().includes(query)) ||
                    (member.phone && member.phone.includes(query))
                );

                if (filtered.length === 0) {
                    searchResults.innerHTML = '<p style="color: #999;">No members found</p>';
                    return;
                }

                searchResults.innerHTML = `<h4>Found ${filtered.length} members:</h4>`;
                filtered.slice(0, 10).forEach(member => {
                    const initials = member.full_name.split(' ').map(n => n[0]).join('').toUpperCase();
                    searchResults.innerHTML += `
                        <div class="member-card">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    ${initials}
                                </div>
                                <div>
                                    <strong>${member.full_name}</strong><br>
                                    <small>${member.email || member.phone || 'No contact'}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
            });
        });
    </script>
</body>
</html>
