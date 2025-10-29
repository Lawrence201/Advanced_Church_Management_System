<?php
/**
 * Fetch Single Event API
 * Retrieves a specific event by ID for editing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

try {
    $conn = getDBConnection();

    // Get event ID
    $event_id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$event_id) {
        sendResponse(false, 'Event ID is required');
    }

    // Fetch event with all details
    $sql = "SELECT
        e.*,
        GROUP_CONCAT(DISTINCT CONCAT(vr.role_name, ':', vr.quantity_needed) SEPARATOR '; ') AS volunteer_roles,
        GROUP_CONCAT(DISTINCT et.tag SEPARATOR ', ') AS tags
    FROM events e
    LEFT JOIN event_volunteer_roles vr ON e.id = vr.event_id
    LEFT JOIN event_tags et ON e.id = et.event_id
    WHERE e.id = ?
    GROUP BY e.id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse(false, 'Event not found');
    }

    $row = $result->fetch_assoc();

    // Parse volunteer roles
    $volunteerRoles = [];
    if ($row['volunteer_roles']) {
        $roles = explode('; ', $row['volunteer_roles']);
        foreach ($roles as $role) {
            $parts = explode(':', $role);
            if (count($parts) === 2) {
                $volunteerRoles[] = [
                    'name' => $parts[0],
                    'quantity' => intval($parts[1])
                ];
            }
        }
    }

    // Parse tags
    $tags = $row['tags'] ? explode(', ', $row['tags']) : [];

    // Format the event data
    $event = [
        'id' => intval($row['id']),
        'name' => $row['name'],
        'type' => $row['type'],
        'type_other' => $row['type_other'],
        'category' => $row['category'],
        'category_other' => $row['category_other'],
        'description' => $row['description'],
        'start_date' => $row['start_date'],
        'start_time' => $row['start_time'],
        'end_date' => $row['end_date'],
        'end_time' => $row['end_time'],
        'is_recurring' => intval($row['is_recurring']),
        'location' => $row['location'],
        'room_building' => $row['room_building'],
        'full_address' => $row['full_address'],
        'is_virtual' => intval($row['is_virtual']),
        'virtual_link' => $row['virtual_link'],
        'max_capacity' => intval($row['max_capacity']),
        'registration_deadline' => $row['registration_deadline'],
        'require_registration' => intval($row['require_registration']),
        'open_to_public' => intval($row['open_to_public']),
        'volunteers_needed' => intval($row['volunteers_needed']),
        'contact_person' => $row['contact_person'],
        'contact_email' => $row['contact_email'],
        'contact_phone' => $row['contact_phone'],
        'age_group' => $row['age_group'],
        'special_notes' => $row['special_notes'],
        'image_path' => $row['image_path'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'volunteer_roles' => $volunteerRoles,
        'tags' => $tags
    ];

    $stmt->close();
    $conn->close();

    sendResponse(true, 'Event retrieved successfully', $event);

} catch (Exception $e) {
    sendResponse(false, 'Error fetching event: ' . $e->getMessage());
}
?>
