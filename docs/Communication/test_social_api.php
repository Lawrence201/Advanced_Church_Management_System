<?php
/**
 * Test script for Social Media API
 * Access: http://localhost/Church_Management_System/admin_dashboard/Communication/test_social_api.php
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media API Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
        }
        h2 {
            color: #1e40af;
            margin-top: 30px;
        }
        .test-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        .test-btn:hover {
            background: #1e40af;
        }
        .result {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .success { color: #059669; font-weight: bold; }
        .error { color: #dc2626; font-weight: bold; }
        .info { color: #0284c7; }
        .status {
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
            margin: 5px 0;
        }
        .status.ok { background: #d1fae5; color: #065f46; }
        .status.fail { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🚀 Social Media API Test Suite</h1>
        <p>Test your social media tracking API endpoints</p>

        <h2>1. Database Connection Test</h2>
        <?php
        try {
            require_once 'db_connect.php';
            echo '<div class="status ok">✓ Database connected successfully</div>';
            
            // Check if tables exist
            $tables = ['social_media_accounts', 'social_media_posts', 'social_media_analytics'];
            echo '<p><strong>Tables Check:</strong></p>';
            foreach ($tables as $table) {
                $result = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() > 0) {
                    echo '<div class="status ok">✓ Table `' . $table . '` exists</div>';
                } else {
                    echo '<div class="status fail">✗ Table `' . $table . '` missing - Run social_media_setup.sql</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="status fail">✗ Database error: ' . $e->getMessage() . '</div>';
        }
        ?>

        <h2>2. API Endpoint Tests</h2>
        <button class="test-btn" onclick="testEndpoint('accounts')">Test Get Accounts</button>
        <button class="test-btn" onclick="testEndpoint('posts')">Test Get Posts</button>
        <button class="test-btn" onclick="testEndpoint('analytics')">Test Get Analytics</button>
        <button class="test-btn" onclick="testCreatePost()">Test Create Post</button>
        <button class="test-btn" onclick="testUpdateMetrics()">Test Update Metrics</button>
        
        <div id="apiResult" class="result" style="display: none;"></div>

        <h2>3. Current Database Data</h2>
        <button class="test-btn" onclick="showAccounts()">Show Accounts</button>
        <button class="test-btn" onclick="showPosts()">Show Posts</button>
        <button class="test-btn" onclick="showAnalytics()">Show Analytics</button>
        
        <div id="dataResult" class="result" style="display: none;"></div>

        <h2>4. Quick Actions</h2>
        <button class="test-btn" onclick="populateSampleData()">Populate Sample Data</button>
        <button class="test-btn" onclick="clearAllPosts()">Clear All Posts</button>
        
        <div id="actionResult" class="result" style="display: none;"></div>
    </div>

    <script>
        function formatJSON(data) {
            return JSON.stringify(data, null, 2);
        }

        async function testEndpoint(action) {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Testing endpoint: ' + action + '...</span>';

            try {
                const response = await fetch('social_media_api.php?action=' + action);
                const data = await response.json();
                
                resultDiv.innerHTML = '<span class="success">✓ Success</span>\n' + formatJSON(data);
            } catch (error) {
                resultDiv.innerHTML = '<span class="error">✗ Error: ' + error.message + '</span>';
            }
        }

        async function testCreatePost() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Creating test post...</span>';

            try {
                const response = await fetch('social_media_api.php?action=post', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        platform: 'facebook',
                        content: 'Test post created at ' + new Date().toLocaleString(),
                        post_type: 'text'
                    })
                });
                const data = await response.json();
                
                resultDiv.innerHTML = '<span class="success">✓ Post created successfully</span>\n' + formatJSON(data);
            } catch (error) {
                resultDiv.innerHTML = '<span class="error">✗ Error: ' + error.message + '</span>';
            }
        }

        async function testUpdateMetrics() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Updating metrics...</span>';

            try {
                const response = await fetch('social_media_api.php?action=update_metrics', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        platform: 'facebook',
                        followers_count: Math.floor(Math.random() * 1000) + 4000,
                        engagement_count: Math.floor(Math.random() * 200) + 300,
                        engagement_rate: (Math.random() * 5 + 7).toFixed(2),
                        posts_count: Math.floor(Math.random() * 10) + 20
                    })
                });
                const data = await response.json();
                
                resultDiv.innerHTML = '<span class="success">✓ Metrics updated</span>\n' + formatJSON(data);
            } catch (error) {
                resultDiv.innerHTML = '<span class="error">✗ Error: ' + error.message + '</span>';
            }
        }

        async function showAccounts() {
            const resultDiv = document.getElementById('dataResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Loading accounts...</span>';

            try {
                const response = await fetch('social_media_api.php?action=accounts');
                const data = await response.json();
                
                if (data.success && data.data) {
                    let html = '<span class="success">✓ Found ' + data.data.length + ' accounts</span>\n\n';
                    data.data.forEach(account => {
                        html += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
                        html += 'Platform: ' + account.platform.toUpperCase() + '\n';
                        html += 'Connected: ' + (account.is_connected ? 'Yes' : 'No') + '\n';
                        html += 'Followers: ' + account.followers_count.toLocaleString() + '\n';
                        html += 'Engagement: ' + account.engagement_count.toLocaleString() + '\n';
                        html += 'Rate: ' + account.engagement_rate + '%\n';
                        html += 'Last Updated: ' + account.last_updated + '\n';
                    });
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = '<span class="error">No accounts found</span>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<span class="error">✗ Error: ' + error.message + '</span>';
            }
        }

        async function showPosts() {
            const resultDiv = document.getElementById('dataResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Loading posts...</span>';

            try {
                const response = await fetch('social_media_api.php?action=posts&limit=20');
                const data = await response.json();
                
                if (data.success && data.data && data.data.length > 0) {
                    let html = '<span class="success">✓ Found ' + data.data.length + ' posts</span>\n\n';
                    data.data.forEach(post => {
                        html += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
                        html += 'ID: ' + post.id + ' | Platform: ' + post.platform + '\n';
                        html += 'Type: ' + post.post_type + ' | Status: ' + post.status + '\n';
                        html += 'Content: ' + post.content.substring(0, 100) + '...\n';
                        html += 'Created: ' + post.created_at + '\n';
                    });
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = '<span class="info">No posts found. Create some posts first!</span>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<span class="error">✗ Error: ' + error.message + '</span>';
            }
        }

        async function showAnalytics() {
            const resultDiv = document.getElementById('dataResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Loading analytics...</span>';

            try {
                const response = await fetch('social_media_api.php?action=analytics');
                const data = await response.json();
                
                if (data.success && data.data && data.data.length > 0) {
                    resultDiv.innerHTML = '<span class="success">✓ Found ' + data.data.length + ' analytics records</span>\n\n' + formatJSON(data.data);
                } else {
                    resultDiv.innerHTML = '<span class="info">No analytics data yet. Update metrics to generate analytics!</span>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<span class="error">✗ Error: ' + error.message + '</span>';
            }
        }

        async function populateSampleData() {
            const resultDiv = document.getElementById('actionResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Creating sample posts...</span>';

            const platforms = ['facebook', 'instagram', 'twitter', 'youtube'];
            const samplePosts = [
                'Join us this Sunday for worship service at 10 AM!',
                'Youth group meeting tonight - pizza and fellowship!',
                'Thank you to all our volunteers this week! 🙏',
                'New sermon series starting next week - don\'t miss it!',
                'Community outreach event this Saturday at 2 PM'
            ];

            let created = 0;
            for (let i = 0; i < 5; i++) {
                const platform = platforms[Math.floor(Math.random() * platforms.length)];
                const content = samplePosts[i];

                try {
                    const response = await fetch('social_media_api.php?action=post', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ platform, content, post_type: 'text' })
                    });
                    const data = await response.json();
                    if (data.success) created++;
                } catch (error) {
                    console.error('Error creating post:', error);
                }
            }

            resultDiv.innerHTML = '<span class="success">✓ Created ' + created + ' sample posts</span>';
        }

        async function clearAllPosts() {
            if (!confirm('Are you sure you want to delete all posts?')) return;

            const resultDiv = document.getElementById('actionResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<span class="info">Clearing posts...</span>';

            // This would need a DELETE endpoint - for now just show message
            resultDiv.innerHTML = '<span class="info">To clear posts, run this SQL in phpMyAdmin:\nTRUNCATE TABLE social_media_posts;</span>';
        }
    </script>
</body>
</html>
