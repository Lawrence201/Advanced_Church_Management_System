<?php
/**
 * Save Member API
 * Handles both creating new members and updating existing ones
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendResponse(false, 'Invalid JSON input');
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Sanitize inputs
    $member_id = isset($input['member_id']) ? intval($input['member_id']) : null;
    $first_name = sanitizeInput($input['first_name'] ?? $input['firstName']);
    $last_name = sanitizeInput($input['last_name'] ?? $input['lastName']);
    $email = sanitizeInput($input['email']);
    $phone = sanitizeInput($input['phone']);
    $date_of_birth = isset($input['date_of_birth']) ? sanitizeInput($input['date_of_birth']) : null;
    $gender = isset($input['gender']) ? sanitizeInput($input['gender']) : '';
    $marital_status = isset($input['marital_status']) ? sanitizeInput($input['marital_status']) : '';
    $occupation = isset($input['occupation']) ? sanitizeInput($input['occupation']) : null;
    $address = isset($input['address']) ? sanitizeInput($input['address']) : null;
    $city = isset($input['city']) ? sanitizeInput($input['city']) : null;
    $region = isset($input['region']) ? sanitizeInput($input['region']) : null;
    $status = isset($input['status']) ? strtolower(sanitizeInput($input['status'])) : 'active';
    $church_group = isset($input['church_group']) ? sanitizeInput($input['church_group']) : (isset($input['group']) ? sanitizeInput($input['group']) : '');
    $leadership_role = isset($input['leadership_role']) ? sanitizeInput($input['leadership_role']) : 'none';
    $baptism_status = isset($input['baptism_status']) ? sanitizeInput($input['baptism_status']) : '';
    $spiritual_growth = isset($input['spiritual_growth']) ? sanitizeInput($input['spiritual_growth']) : '';
    $membership_type = isset($input['membership_type']) ? sanitizeInput($input['membership_type']) : '';
    $notes = isset($input['notes']) ? sanitizeInput($input['notes']) : null;
    $photo_path = isset($input['photo_path']) ? sanitizeInput($input['photo_path']) : null;

    // Handle base64 photo upload
    if (isset($input['photo']) && !empty($input['photo'])) {
        // Extract base64 data
        $photo_data = $input['photo'];

        if (preg_match('/^data:image\/(\w+);base64,/', $photo_data, $matches)) {
            $image_type = $matches[1];
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);

            // Create uploads directory if it doesn't exist
            $upload_dir = '../Add_Members/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $filename = 'member_' . time() . '_' . uniqid() . '.' . $image_type;
            $file_path = $upload_dir . $filename;

            // Save the file
            if (file_put_contents($file_path, $photo_data)) {
                $photo_path = 'uploads/' . $filename;
            }
        }
    }

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        sendResponse(false, 'First name, last name, email, and phone are required');
    }

    if ($member_id) {
        // UPDATE existing member
        $stmt = $conn->prepare("UPDATE members SET
            first_name = ?,
            last_name = ?,
            email = ?,
            phone = ?,
            date_of_birth = ?,
            gender = ?,
            marital_status = ?,
            occupation = ?,
            address = ?,
            city = ?,
            region = ?,
            status = ?,
            church_group = ?,
            leadership_role = ?,
            baptism_status = ?,
            spiritual_growth = ?,
            membership_type = ?,
            notes = ?,
            photo_path = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE member_id = ?");

        $stmt->bind_param(
            "sssssssssssssssssssi",
            $first_name,
            $last_name,
            $email,
            $phone,
            $date_of_birth,
            $gender,
            $marital_status,
            $occupation,
            $address,
            $city,
            $region,
            $status,
            $church_group,
            $leadership_role,
            $baptism_status,
            $spiritual_growth,
            $membership_type,
            $notes,
            $photo_path,
            $member_id
        );

        $stmt->execute();

        if ($stmt->affected_rows === 0 && $stmt->errno === 0) {
            // No error but no rows affected means member exists but data unchanged
            $message = "No changes made to member";
        } else {
            $message = "Member updated successfully";
        }

        $new_member_id = $member_id;

    } else {
        // INSERT new member
        $stmt = $conn->prepare("INSERT INTO members (
            first_name,
            last_name,
            email,
            phone,
            date_of_birth,
            gender,
            marital_status,
            occupation,
            address,
            city,
            region,
            status,
            church_group,
            leadership_role,
            baptism_status,
            spiritual_growth,
            membership_type,
            notes,
            photo_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssssssssssssssss",
            $first_name,
            $last_name,
            $email,
            $phone,
            $date_of_birth,
            $gender,
            $marital_status,
            $occupation,
            $address,
            $city,
            $region,
            $status,
            $church_group,
            $leadership_role,
            $baptism_status,
            $spiritual_growth,
            $membership_type,
            $notes,
            $photo_path
        );

        $stmt->execute();
        $new_member_id = $conn->insert_id;
        $message = "Member added successfully";
    }

    // Handle emergency contact
    if (isset($input['emergency_name']) && !empty($input['emergency_name'])) {
        // Delete existing emergency contact
        $conn->query("DELETE FROM emergency_contacts WHERE member_id = $new_member_id");

        // Insert new emergency contact
        $emergency_stmt = $conn->prepare("INSERT INTO emergency_contacts (
            member_id,
            emergency_name,
            emergency_phone,
            emergency_relation
        ) VALUES (?, ?, ?, ?)");

        $emergency_name = sanitizeInput($input['emergency_name']);
        $emergency_phone = sanitizeInput($input['emergency_phone']);
        $emergency_relation = sanitizeInput($input['emergency_relation']);

        $emergency_stmt->bind_param(
            "isss",
            $new_member_id,
            $emergency_name,
            $emergency_phone,
            $emergency_relation
        );
        $emergency_stmt->execute();
        $emergency_stmt->close();
    }

    // Handle ministries
    if (isset($input['ministries']) && is_array($input['ministries'])) {
        // Delete existing ministry associations
        $conn->query("DELETE FROM member_ministries WHERE member_id = $new_member_id");

        // Insert new ministry associations
        $ministry_stmt = $conn->prepare("INSERT INTO member_ministries (member_id, ministry_id) VALUES (?, ?)");

        foreach ($input['ministries'] as $ministry_id) {
            $ministry_id = intval($ministry_id);
            $ministry_stmt->bind_param("ii", $new_member_id, $ministry_id);
            $ministry_stmt->execute();
        }
        $ministry_stmt->close();
    }

    // Handle departments
    if (isset($input['departments']) && is_array($input['departments'])) {
        // Delete existing department associations
        $conn->query("DELETE FROM member_departments WHERE member_id = $new_member_id");

        // Insert new department associations
        $dept_stmt = $conn->prepare("INSERT INTO member_departments (member_id, department_id) VALUES (?, ?)");

        foreach ($input['departments'] as $dept_id) {
            $dept_id = intval($dept_id);
            $dept_stmt->bind_param("ii", $new_member_id, $dept_id);
            $dept_stmt->execute();
        }
        $dept_stmt->close();
    }

    $stmt->close();
    $conn->commit();

    sendResponse(true, $message, ['member_id' => $new_member_id]);

} catch (Exception $e) {
    $conn->rollback();
    sendResponse(false, 'Error: ' . $e->getMessage());
} finally {
    $conn->close();
}
