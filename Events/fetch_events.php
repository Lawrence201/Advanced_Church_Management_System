<?php
/**
 * Fetch Events API
 * Retrieves events from the database with filtering options
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

try {
    $conn = getDBConnection();

    // Get filter parameters
    $filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;

    // Base query
    $sql = "SELECT
        e.id,
        e.name,
        e.type,
        e.type_other,
        e.category,
        e.category_other,
        e.description,
        e.start_date,
        e.start_time,
        e.end_date,
        e.end_time,
        e.is_recurring,
        e.location,
        e.room_building,
        e.full_address,
        e.is_virtual,
        e.virtual_link,
        e.max_capacity,
        e.registration_deadline,
        e.require_registration,
        e.open_to_public,
        e.volunteers_needed,
        e.contact_person,
        e.contact_email,
        e.contact_phone,
        e.age_group,
        e.special_notes,
        e.image_path,
        e.status,
        e.created_at,
        e.updated_at,
        GROUP_CONCAT(DISTINCT CONCAT(vr.role_name, ':', vr.quantity_needed) SEPARATOR '; ') AS volunteer_roles,
        GROUP_CONCAT(DISTINCT et.tag SEPARATOR ', ') AS tags
    FROM events e
    LEFT JOIN event_volunteer_roles vr ON e.id = vr.event_id
    LEFT JOIN event_tags et ON e.id = et.event_id
    WHERE e.status != 'Cancelled'";

    // Add filters
    $params = [];
    $types = '';

    // Date filter
    if (!empty($date)) {
        $sql .= " AND e.start_date = ?";
        $params[] = $date;
        $types .= 's';
    }

    // Filter by type
    if ($filter !== 'all') {
        if ($filter === 'upcoming') {
            $sql .= " AND e.start_date >= CURDATE()";
        } elseif ($filter === 'service') {
            $sql .= " AND e.type = 'Service'";
        } elseif ($filter === 'ministry') {
            $sql .= " AND e.category IN ('Youth', 'Women', 'Men', 'Choir')";
        } else {
            $sql .= " AND (e.type = ? OR e.category = ?)";
            $params[] = ucfirst($filter);
            $params[] = ucfirst($filter);
            $types .= 'ss';
        }
    }

    // Search filter
    if (!empty($search)) {
        $sql .= " AND (e.name LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }

    $sql .= " GROUP BY e.id ORDER BY e.start_date ASC, e.start_time ASC";

    // Add limit if specified
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types .= 'i';
    }

    // Prepare and execute
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
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
            'tags' => $tags,
            // Formatted display fields
            'date_formatted' => formatDate($row['start_date']),
            'time_formatted' => formatTime($row['start_time']),
            // Mock attendance data (you can extend this with actual attendance tracking)
            'attending' => 0, // Will be populated if you have attendance table
            'percentage' => 0
        ];

        $events[] = $event;
    }

    $stmt->close();
    $conn->close();

    sendResponse(true, 'Events retrieved successfully', [
        'events' => $events,
        'total' => count($events)
    ]);

} catch (Exception $e) {
    sendResponse(false, 'Error fetching events: ' . $e->getMessage());
}
?>
