<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['action']) && $data['action'] === 'post') {
            // Post to social media
            $stmt = $pdo->prepare("
                INSERT INTO social_posts (platform, content, status, scheduled_at)
                VALUES (?, ?, 'posted', NULL)
            ");
            $stmt->execute([
                $data['platform'],
                $data['content']
            ]);
            echo json_encode(['success' => true, 'message' => 'Post published successfully']);
        }
        break;
}
