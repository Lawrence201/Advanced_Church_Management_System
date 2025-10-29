<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['action'])) {
            if ($data['action'] === 'send') {
                // Send message
                $stmt = $pdo->prepare("
                    INSERT INTO messages (message_type, audience, title, content, delivery_channels, status)
                    VALUES (?, ?, ?, ?, ?, 'sent')
                ");
                $stmt->execute([
                    $data['messageType'],
                    $data['audience'],
                    $data['title'],
                    $data['content'],
                    json_encode($data['deliveryChannels'])
                ]);
                echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
            } elseif ($data['action'] === 'save_draft') {
                // Save draft
                $stmt = $pdo->prepare("
                    INSERT INTO messages (message_type, audience, title, content, delivery_channels, status)
                    VALUES (?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([
                    $data['messageType'],
                    $data['audience'],
                    $data['title'],
                    $data['content'],
                    json_encode($data['deliveryChannels'])
                ]);
                echo json_encode(['success' => true, 'message' => 'Draft saved successfully']);
            } elseif ($data['action'] === 'schedule') {
                // Schedule message
                $stmt = $pdo->prepare("
                    INSERT INTO messages (message_type, audience, title, content, delivery_channels, status, scheduled_at)
                    VALUES (?, ?, ?, ?, ?, 'scheduled', ?)
                ");
                $stmt->execute([
                    $data['messageType'],
                    $data['audience'],
                    $data['title'],
                    $data['content'],
                    json_encode($data['deliveryChannels']),
                    $data['scheduledAt']
                ]);
                echo json_encode(['success' => true, 'message' => 'Message scheduled successfully']);
            }
        }
        break;

    case 'GET':
        // Fetch sent messages
        $stmt = $pdo->query("SELECT * FROM messages WHERE status = 'sent' ORDER BY created_at DESC");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($messages);
        break;
}
