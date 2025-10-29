<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'accounts') {
                // Fetch all social media accounts
                $stmt = $pdo->query("
                    SELECT 
                        id,
                        platform,
                        account_name,
                        is_connected,
                        followers_count,
                        engagement_count,
                        engagement_rate,
                        additional_metrics,
                        last_updated
                    FROM social_media_accounts 
                    ORDER BY 
                        CASE platform
                            WHEN 'facebook' THEN 1
                            WHEN 'instagram' THEN 2
                            WHEN 'twitter' THEN 3
                            WHEN 'youtube' THEN 4
                            ELSE 5
                        END
                ");
                $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Parse JSON additional_metrics
                foreach ($accounts as &$account) {
                    $account['additional_metrics'] = json_decode($account['additional_metrics'], true);
                }
                
                echo json_encode(['success' => true, 'data' => $accounts]);
                
            } elseif ($action === 'posts') {
                // Fetch recent posts
                $platform = $_GET['platform'] ?? null;
                $limit = $_GET['limit'] ?? 10;
                
                $sql = "SELECT * FROM social_media_posts";
                if ($platform) {
                    $sql .= " WHERE platform = :platform";
                }
                $sql .= " ORDER BY created_at DESC LIMIT :limit";
                
                $stmt = $pdo->prepare($sql);
                if ($platform) {
                    $stmt->bindValue(':platform', $platform);
                }
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->execute();
                
                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $posts]);
                
            } elseif ($action === 'analytics') {
                // Fetch analytics for the last 30 days
                $platform = $_GET['platform'] ?? null;
                
                $sql = "SELECT * FROM social_media_analytics";
                if ($platform) {
                    $sql .= " WHERE platform = :platform";
                }
                $sql .= " ORDER BY date DESC LIMIT 30";
                
                $stmt = $pdo->prepare($sql);
                if ($platform) {
                    $stmt->bindValue(':platform', $platform);
                }
                $stmt->execute();
                
                $analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $analytics]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'post') {
                // Create new social media post
                $stmt = $pdo->prepare("
                    INSERT INTO social_media_posts 
                    (platform, post_type, content, media_path, status, scheduled_at)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $status = isset($data['scheduled_at']) ? 'scheduled' : 'published';
                $scheduled_at = $data['scheduled_at'] ?? null;
                
                $stmt->execute([
                    $data['platform'],
                    $data['post_type'] ?? 'text',
                    $data['content'],
                    $data['media_path'] ?? null,
                    $status,
                    $scheduled_at
                ]);
                
                // Update social_posts table if exists
                try {
                    $stmt2 = $pdo->prepare("
                        INSERT INTO social_posts 
                        (platform, content, media_path, status, scheduled_at)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt2->execute([
                        $data['platform'],
                        $data['content'],
                        $data['media_path'] ?? null,
                        $status,
                        $scheduled_at
                    ]);
                } catch (PDOException $e) {
                    // Table might not exist, ignore
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Post created successfully',
                    'post_id' => $pdo->lastInsertId()
                ]);
                
            } elseif ($action === 'update_metrics') {
                // Update social media account metrics
                $stmt = $pdo->prepare("
                    UPDATE social_media_accounts 
                    SET 
                        followers_count = ?,
                        engagement_count = ?,
                        engagement_rate = ?,
                        additional_metrics = ?
                    WHERE platform = ?
                ");
                
                $additional_metrics = isset($data['additional_metrics']) 
                    ? json_encode($data['additional_metrics']) 
                    : null;
                
                $stmt->execute([
                    $data['followers_count'],
                    $data['engagement_count'],
                    $data['engagement_rate'],
                    $additional_metrics,
                    $data['platform']
                ]);
                
                // Also log to analytics table
                $stmt2 = $pdo->prepare("
                    INSERT INTO social_media_analytics 
                    (platform, date, followers_count, engagement_count, engagement_rate, posts_count)
                    VALUES (?, CURDATE(), ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        followers_count = VALUES(followers_count),
                        engagement_count = VALUES(engagement_count),
                        engagement_rate = VALUES(engagement_rate),
                        posts_count = VALUES(posts_count)
                ");
                
                $stmt2->execute([
                    $data['platform'],
                    $data['followers_count'],
                    $data['engagement_count'],
                    $data['engagement_rate'],
                    $data['posts_count'] ?? 0
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Metrics updated successfully']);
                
            } elseif ($action === 'connect') {
                // Connect/disconnect social media account
                $stmt = $pdo->prepare("
                    UPDATE social_media_accounts 
                    SET 
                        is_connected = ?,
                        account_name = ?,
                        account_id = ?,
                        access_token = ?
                    WHERE platform = ?
                ");
                
                $stmt->execute([
                    $data['is_connected'] ? 1 : 0,
                    $data['account_name'] ?? null,
                    $data['account_id'] ?? null,
                    $data['access_token'] ?? null,
                    $data['platform']
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Connection status updated']);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'post') {
                // Update existing post
                $stmt = $pdo->prepare("
                    UPDATE social_media_posts 
                    SET 
                        content = ?,
                        media_path = ?,
                        status = ?,
                        scheduled_at = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $data['content'],
                    $data['media_path'] ?? null,
                    $data['status'],
                    $data['scheduled_at'] ?? null,
                    $data['id']
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
            }
            break;
            
        case 'DELETE':
            if ($action === 'post') {
                // Delete post
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    throw new Exception('Post ID required');
                }
                
                $stmt = $pdo->prepare("DELETE FROM social_media_posts WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
            }
            break;
            
        default:
            throw new Exception('Invalid request method');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
