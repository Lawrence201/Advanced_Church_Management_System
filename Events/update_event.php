<?php
/**
 * Update Event Script
 * Handles event updates including image changes
 */

require_once 'db_config.php';
require_once '../Add_Event/upload_handler.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

try {
    $conn = getDBConnection();
    $conn->begin_transaction();

    // Get event ID
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;

    if (!$event_id) {
        throw new Exception('Event ID is required');
    }

    // Verify event exists
    $checkStmt = $conn->prepare("SELECT id, image_path FROM events WHERE id = ?");
    $checkStmt->bind_param('i', $event_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Event not found');
    }

    $existingEvent = $result->fetch_assoc();
    $checkStmt->close();

    // Extract and sanitize all fields
    $name = sanitizeInput($_POST['eventName'] ?? '');
    $type = formatEnumValue($_POST['eventType'] ?? '');
    $type_other = ($type === 'Other') ? sanitizeInput($_POST['eventTypeOther'] ?? '') : null;
    $category = formatEnumValue($_POST['eventCategory'] ?? '');
    $category_other = ($category === 'Other') ? sanitizeInput($_POST['eventCategoryOther'] ?? '') : null;
    $description = sanitizeInput($_POST['eventDescription'] ?? '');

    $start_date = sanitizeInput($_POST['startDate'] ?? '');
    $start_time = sanitizeInput($_POST['startTime'] ?? '');
    $end_date = sanitizeInput($_POST['endDate'] ?? '');
    $end_time = sanitizeInput($_POST['endTime'] ?? '');
    $is_recurring = isset($_POST['recurringEvent']) ? 1 : 0;

    $location = formatEnumValue($_POST['eventLocation'] ?? '');
    $room_building = sanitizeInput($_POST['roomBuilding'] ?? null);
    $full_address = sanitizeInput($_POST['fullAddress'] ?? null);
    $is_virtual = isset($_POST['virtualEvent']) ? 1 : 0;
    $virtual_link = $is_virtual ? sanitizeInput($_POST['virtualLink'] ?? null) : null;

    $max_capacity = intval($_POST['maxCapacity'] ?? 0);
    $registration_deadline = sanitizeInput($_POST['registrationDeadline'] ?? null);
    $require_registration = isset($_POST['requireRegistration']) ? 1 : 0;
    $open_to_public = isset($_POST['openToPublic']) ? 1 : 0;

    $volunteers_needed = intval($_POST['volunteersNeeded'] ?? 0);

    $contact_person = sanitizeInput($_POST['contactPerson'] ?? null);
    $contact_email = sanitizeInput($_POST['contactEmail'] ?? null);
    $contact_phone = sanitizeInput($_POST['contactPhone'] ?? null);
    $age_group = formatEnumValue($_POST['ageGroup'] ?? 'All');
    $special_notes = sanitizeInput($_POST['specialNotes'] ?? null);
    $status = sanitizeInput($_POST['status'] ?? 'Published');

    // Handle image upload (if new image provided)
    $image_path = $existingEvent['image_path'];
    if (isset($_FILES['eventImage']) && $_FILES['eventImage']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['eventImage']);
        if ($uploadResult['success']) {
            // Delete old image if it exists
            if ($existingEvent['image_path']) {
                deleteUploadedImage($existingEvent['image_path']);
            }
            $image_path = $uploadResult['path'];
        }
    }

    // Validate required fields
    if (empty($name) || empty($type) || empty($category) || empty($description) ||
        empty($start_date) || empty($start_time) || empty($end_date) || empty($end_time) ||
        empty($location) || $max_capacity <= 0) {
        throw new Exception('Missing required fields');
    }

    // Update event
    $sql = "UPDATE events SET
        name = ?,
        type = ?,
        type_other = ?,
        category = ?,
        category_other = ?,
        description = ?,
        start_date = ?,
        start_time = ?,
        end_date = ?,
        end_time = ?,
        is_recurring = ?,
        location = ?,
        room_building = ?,
        full_address = ?,
        is_virtual = ?,
        virtual_link = ?,
        max_capacity = ?,
        registration_deadline = ?,
        require_registration = ?,
        open_to_public = ?,
        volunteers_needed = ?,
        contact_person = ?,
        contact_email = ?,
        contact_phone = ?,
        age_group = ?,
        special_notes = ?,
        image_path = ?,
        status = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param(
        'ssssssssssisssisisiiissssssi',
        $name, $type, $type_other, $category, $category_other, $description,
        $start_date, $start_time, $end_date, $end_time, $is_recurring,
        $location, $room_building, $full_address, $is_virtual, $virtual_link,
        $max_capacity, $registration_deadline, $require_registration, $open_to_public,
        $volunteers_needed, $contact_person, $contact_email, $contact_phone,
        $age_group, $special_notes, $image_path, $status,
        $event_id
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to update event: ' . $stmt->error);
    }

    $stmt->close();

    // Delete existing volunteer roles
    $deleteRolesStmt = $conn->prepare("DELETE FROM event_volunteer_roles WHERE event_id = ?");
    $deleteRolesStmt->bind_param('i', $event_id);
    $deleteRolesStmt->execute();
    $deleteRolesStmt->close();

    // Insert updated volunteer roles
    if (isset($_POST['volunteerRoles'])) {
        $volunteer_roles = json_decode($_POST['volunteerRoles'], true);
        if (is_array($volunteer_roles) && count($volunteer_roles) > 0) {
            $role_sql = "INSERT INTO event_volunteer_roles (event_id, role_name, quantity_needed) VALUES (?, ?, ?)";
            $role_stmt = $conn->prepare($role_sql);

            foreach ($volunteer_roles as $role) {
                if (!empty($role['name']) && !empty($role['quantity'])) {
                    $role_name = sanitizeInput($role['name']);
                    $quantity = intval($role['quantity']);
                    $role_stmt->bind_param('isi', $event_id, $role_name, $quantity);
                    $role_stmt->execute();
                }
            }
            $role_stmt->close();
        }
    }

    // Delete existing tags
    $deleteTagsStmt = $conn->prepare("DELETE FROM event_tags WHERE event_id = ?");
    $deleteTagsStmt->bind_param('i', $event_id);
    $deleteTagsStmt->execute();
    $deleteTagsStmt->close();

    // Insert updated tags
    if (isset($_POST['eventTags'])) {
        $event_tags = json_decode($_POST['eventTags'], true);
        if (is_array($event_tags) && count($event_tags) > 0) {
            $tag_sql = "INSERT INTO event_tags (event_id, tag) VALUES (?, ?)";
            $tag_stmt = $conn->prepare($tag_sql);

            foreach ($event_tags as $tag) {
                $tag = sanitizeInput($tag);
                if (!empty($tag)) {
                    $tag_stmt->bind_param('is', $event_id, $tag);
                    $tag_stmt->execute();
                }
            }
            $tag_stmt->close();
        }
    }

    // Commit transaction
    $conn->commit();
    $conn->close();

    sendResponse(true, 'Event updated successfully!', ['event_id' => $event_id]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    sendResponse(false, 'Error updating event: ' . $e->getMessage());
}

function formatEnumValue($value) {
    if (empty($value)) {
        return null;
    }
    $parts = explode('-', $value);
    $parts[0] = ucfirst($parts[0]);
    return implode('-', $parts);
}
?>
