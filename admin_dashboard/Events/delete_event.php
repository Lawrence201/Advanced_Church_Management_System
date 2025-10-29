<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    // Get database connection
    $conn = getDBConnection();

    // Get event ID
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

    if ($event_id <= 0) {
        throw new Exception('Invalid event ID');
    }

    // Start transaction
    $conn->begin_transaction();

    // Get event details (including image path) before deletion
    $stmt = $conn->prepare("SELECT image_path FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Event not found');
    }

    $event = $result->fetch_assoc();
    $stmt->close();

    // Delete related records first (foreign key constraints)

    // Delete volunteer roles
    $stmt = $conn->prepare("DELETE FROM event_volunteer_roles WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();

    // Delete tags
    $stmt = $conn->prepare("DELETE FROM event_tags WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();

    // Delete the event itself
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to delete event: ' . $stmt->error);
    }

    $stmt->close();

    // Commit transaction
    $conn->commit();

    // Delete the image file if it exists
    if (!empty($event['image_path'])) {
        $image_full_path = '../' . $event['image_path'];
        if (file_exists($image_full_path)) {
            @unlink($image_full_path);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Event deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
