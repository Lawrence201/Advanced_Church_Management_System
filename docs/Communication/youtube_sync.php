<?php
/**
 * YouTube Real-Time Statistics Sync
 * Fetches live data from YouTube Data API v3
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

// YouTube API Configuration
define('YOUTUBE_API_KEY', 'YOUR_API_KEY_HERE'); // You'll need to get this from Google Cloud Console
define('YOUTUBE_CHANNEL_ID', 'UC_YOUR_CHANNEL_ID'); // We'll extract this from your handle
define('YOUTUBE_CHANNEL_HANDLE', '@LawrenceBlaze-vv7fd');

/**
 * Get Channel ID from Handle
 */
function getChannelIdFromHandle($handle) {
    $apiKey = YOUTUBE_API_KEY;
    
    // Remove @ symbol if present
    $handle = ltrim($handle, '@');
    
    // Method 1: Try using forHandle parameter (newer API)
    $url = "https://www.googleapis.com/youtube/v3/channels?part=id,snippet&forHandle={$handle}&key={$apiKey}";
    
    $response = @file_get_contents($url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['items'][0]['id'])) {
            return $data['items'][0]['id'];
        }
    }
    
    // Method 2: Try using forUsername parameter
    $url = "https://www.googleapis.com/youtube/v3/channels?part=id,snippet&forUsername={$handle}&key={$apiKey}";
    
    $response = @file_get_contents($url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['items'][0]['id'])) {
            return $data['items'][0]['id'];
        }
    }
    
    return null;
}

/**
 * Fetch YouTube Channel Statistics
 */
function fetchYouTubeStats($channelId) {
    $apiKey = YOUTUBE_API_KEY;
    
    // Get channel statistics
    $url = "https://www.googleapis.com/youtube/v3/channels?part=statistics,snippet&id={$channelId}&key={$apiKey}";
    
    $response = @file_get_contents($url);
    
    if (!$response) {
        return ['error' => 'Failed to fetch YouTube data. Check API key and channel ID.'];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['items'][0])) {
        $channel = $data['items'][0];
        $stats = $channel['statistics'];
        
        return [
            'success' => true,
            'channel_id' => $channelId,
            'channel_title' => $channel['snippet']['title'],
            'subscribers' => (int)$stats['subscriberCount'],
            'total_views' => (int)$stats['viewCount'],
            'video_count' => (int)$stats['videoCount'],
            'thumbnail' => $channel['snippet']['thumbnails']['default']['url'],
            'fetched_at' => date('Y-m-d H:i:s')
        ];
    }
    
    return ['error' => 'Channel not found'];
}

/**
 * Get Recent Videos Statistics
 */
function fetchRecentVideos($channelId, $maxResults = 10) {
    $apiKey = YOUTUBE_API_KEY;
    
    // Get channel's uploads playlist ID
    $url = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id={$channelId}&key={$apiKey}";
    $response = @file_get_contents($url);
    
    if (!$response) {
        return ['error' => 'Failed to fetch channel details'];
    }
    
    $data = json_decode($response, true);
    $uploadsPlaylistId = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
    
    // Get videos from uploads playlist
    $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet,contentDetails&playlistId={$uploadsPlaylistId}&maxResults={$maxResults}&key={$apiKey}";
    $response = @file_get_contents($url);
    
    if (!$response) {
        return ['error' => 'Failed to fetch videos'];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['items'])) {
        return ['error' => 'No videos found'];
    }
    
    // Get video IDs
    $videoIds = array_map(function($item) {
        return $item['contentDetails']['videoId'];
    }, $data['items']);
    
    // Get video statistics
    $videoIdsStr = implode(',', $videoIds);
    $url = "https://www.googleapis.com/youtube/v3/videos?part=statistics,snippet&id={$videoIdsStr}&key={$apiKey}";
    $response = @file_get_contents($url);
    
    if (!$response) {
        return ['error' => 'Failed to fetch video stats'];
    }
    
    $videoData = json_decode($response, true);
    
    $videos = [];
    foreach ($videoData['items'] as $video) {
        $videos[] = [
            'video_id' => $video['id'],
            'title' => $video['snippet']['title'],
            'views' => (int)$video['statistics']['viewCount'],
            'likes' => (int)($video['statistics']['likeCount'] ?? 0),
            'comments' => (int)($video['statistics']['commentCount'] ?? 0),
            'published_at' => $video['snippet']['publishedAt']
        ];
    }
    
    return ['success' => true, 'videos' => $videos];
}

/**
 * Update Database with YouTube Stats
 */
function updateYouTubeStatsInDB($stats) {
    global $pdo;
    
    // Calculate engagement rate
    $engagementRate = 0;
    if ($stats['subscribers'] > 0) {
        // Simple engagement: (views per subscriber) * 100
        $engagementRate = round(($stats['total_views'] / $stats['subscribers']) * 0.1, 2);
        if ($engagementRate > 100) $engagementRate = 100;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE social_media_accounts 
            SET 
                followers_count = ?,
                engagement_count = 0,
                engagement_rate = ?,
                additional_metrics = ?,
                account_name = ?,
                last_updated = NOW()
            WHERE platform = 'youtube'
        ");
        
        $additionalMetrics = json_encode([
            'views' => $stats['total_views'],
            'growth' => 0,
            'video_count' => $stats['video_count'],
            'channel_id' => $stats['channel_id']
        ]);
        
        $stmt->execute([
            $stats['subscribers'],
            $engagementRate,
            $additionalMetrics,
            $stats['channel_title']
        ]);
        
        // Log to analytics
        $stmt2 = $pdo->prepare("
            INSERT INTO social_media_analytics 
            (platform, date, followers_count, engagement_rate, reach, impressions)
            VALUES ('youtube', CURDATE(), ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                followers_count = VALUES(followers_count),
                engagement_rate = VALUES(engagement_rate),
                reach = VALUES(reach),
                impressions = VALUES(impressions)
        ");
        
        $stmt2->execute([
            $stats['subscribers'],
            $engagementRate,
            $stats['video_count'],
            $stats['total_views']
        ]);
        
        return ['success' => true, 'message' => 'Database updated successfully'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Main execution
$action = $_GET['action'] ?? 'sync';

switch ($action) {
    case 'sync':
        // Sync YouTube data
        if (YOUTUBE_API_KEY === 'YOUR_API_KEY_HERE') {
            echo json_encode([
                'success' => false,
                'error' => 'YouTube API key not configured. See YOUTUBE_SETUP.md for instructions.',
                'setup_required' => true
            ]);
            exit;
        }
        
        // Get or find channel ID
        $channelId = YOUTUBE_CHANNEL_ID;
        if ($channelId === 'UC_YOUR_CHANNEL_ID') {
            $channelId = getChannelIdFromHandle(YOUTUBE_CHANNEL_HANDLE);
            if (!$channelId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Could not find channel ID. Please set it manually in youtube_sync.php'
                ]);
                exit;
            }
        }
        
        // Fetch stats
        $stats = fetchYouTubeStats($channelId);
        
        if (isset($stats['error'])) {
            echo json_encode(['success' => false, 'error' => $stats['error']]);
            exit;
        }
        
        // Update database
        $result = updateYouTubeStatsInDB($stats);
        
        if ($result['success']) {
            $stats['db_updated'] = true;
            echo json_encode($stats);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
        break;
        
    case 'test':
        // Test API connection
        echo json_encode([
            'api_key_set' => YOUTUBE_API_KEY !== 'YOUR_API_KEY_HERE',
            'channel_id_set' => YOUTUBE_CHANNEL_ID !== 'UC_YOUR_CHANNEL_ID',
            'channel_handle' => YOUTUBE_CHANNEL_HANDLE,
            'php_version' => phpversion(),
            'allow_url_fopen' => ini_get('allow_url_fopen') ? true : false
        ]);
        break;
        
    case 'videos':
        // Get recent videos
        $channelId = YOUTUBE_CHANNEL_ID;
        if ($channelId === 'UC_YOUR_CHANNEL_ID') {
            echo json_encode(['error' => 'Channel ID not set']);
            exit;
        }
        
        $videos = fetchRecentVideos($channelId);
        echo json_encode($videos);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
