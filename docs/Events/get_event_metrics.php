<?php
/**
 * Get Event Metrics API
 * Returns event statistics for dashboard display
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

try {
    $conn = new PDO("mysql:host=localhost;dbname=church_management_system", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $metrics = [];

    // Total Events (excluding cancelled)
    $stmt = $conn->query("SELECT COUNT(*) as total FROM events WHERE status != 'Cancelled'");
    $metrics['total_events'] = (int)$stmt->fetchColumn();

    // Upcoming Events (future events)
    $stmt = $conn->query("
        SELECT COUNT(*) as total
        FROM events
        WHERE status != 'Cancelled'
        AND (
            start_date > CURDATE()
            OR (start_date = CURDATE() AND start_time > CURTIME())
        )
    ");
    $metrics['upcoming_events'] = (int)$stmt->fetchColumn();

    // In Service / Active Events (happening today or in progress)
    $stmt = $conn->query("
        SELECT COUNT(*) as total
        FROM events
        WHERE status != 'Cancelled'
        AND start_date = CURDATE()
    ");
    $metrics['in_service_events'] = (int)$stmt->fetchColumn();

    // Past Events
    $stmt = $conn->query("
        SELECT COUNT(*) as total
        FROM events
        WHERE status != 'Cancelled'
        AND (
            end_date < CURDATE()
            OR (end_date = CURDATE() AND end_time < CURTIME())
        )
    ");
    $metrics['past_events'] = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => $metrics
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn = null;
?>
