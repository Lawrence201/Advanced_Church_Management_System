<?php
/**
 * Manage Message Groups API
 * Create, update, delete message groups and manage members
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn, $data);
        break;
    case 'PUT':
        handlePut($conn, $data);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid method']);
}

$conn->close();

/**
 * Get groups or group details
 */
function handleGet($conn) {
    $groupId = $_GET['group_id'] ?? null;
    
    if ($groupId) {
        // Get specific group with members
        $sql = "SELECT * FROM message_groups WHERE group_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $group = $result->fetch_assoc();
        $stmt->close();
        
        if ($group) {
            // Get members
            $sql = "SELECT m.member_id, CONCAT(m.first_name, ' ', m.last_name) as name, m.email, m.phone 
                    FROM members m 
                    INNER JOIN message_group_members mgm ON m.member_id = mgm.member_id 
                    WHERE mgm.group_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $groupId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $members = [];
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }
            $stmt->close();
            
            $group['members'] = $members;
        }
        
        echo json_encode(['success' => true, 'group' => $group]);
    } else {
        // Get all groups
        $sql = "SELECT g.*, 
                (SELECT COUNT(*) FROM message_group_members WHERE group_id = g.group_id) as member_count
                FROM message_groups g 
                ORDER BY g.group_name";
        $result = $conn->query($sql);
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        
        echo json_encode(['success' => true, 'groups' => $groups]);
    }
}

/**
 * Create new group
 */
function handlePost($conn, $data) {
    if (!isset($data['group_name'])) {
        echo json_encode(['error' => 'Group name is required']);
        return;
    }
    
    $groupName = $conn->real_escape_string($data['group_name']);
    $groupType = $data['group_type'] ?? 'static';
    $description = $data['description'] ?? null;
    $filterCriteria = isset($data['filter_criteria']) ? json_encode($data['filter_criteria']) : null;
    
    $sql = "INSERT INTO message_groups (group_name, group_type, description, filter_criteria) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $groupName, $groupType, $description, $filterCriteria);
    
    if ($stmt->execute()) {
        $groupId = $conn->insert_id;
        
        // Add members if provided
        if (isset($data['member_ids']) && is_array($data['member_ids'])) {
            addMembersToGroup($conn, $groupId, $data['member_ids']);
        }
        
        echo json_encode(['success' => true, 'group_id' => $groupId]);
    } else {
        echo json_encode(['error' => 'Failed to create group: ' . $conn->error]);
    }
    
    $stmt->close();
}

/**
 * Update group
 */
function handlePut($conn, $data) {
    if (!isset($data['group_id'])) {
        echo json_encode(['error' => 'Group ID is required']);
        return;
    }
    
    $groupId = intval($data['group_id']);
    $updates = [];
    $types = '';
    $values = [];
    
    if (isset($data['group_name'])) {
        $updates[] = 'group_name = ?';
        $types .= 's';
        $values[] = $data['group_name'];
    }
    if (isset($data['description'])) {
        $updates[] = 'description = ?';
        $types .= 's';
        $values[] = $data['description'];
    }
    if (isset($data['filter_criteria'])) {
        $updates[] = 'filter_criteria = ?';
        $types .= 's';
        $values[] = json_encode($data['filter_criteria']);
    }
    
    if (empty($updates)) {
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $values[] = $groupId;
    $types .= 'i';
    
    $sql = "UPDATE message_groups SET " . implode(', ', $updates) . " WHERE group_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        // Update members if provided
        if (isset($data['member_ids'])) {
            // Remove all existing members
            $delSql = "DELETE FROM message_group_members WHERE group_id = ?";
            $delStmt = $conn->prepare($delSql);
            $delStmt->bind_param('i', $groupId);
            $delStmt->execute();
            $delStmt->close();
            
            // Add new members
            if (is_array($data['member_ids']) && !empty($data['member_ids'])) {
                addMembersToGroup($conn, $groupId, $data['member_ids']);
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update group']);
    }
    
    $stmt->close();
}

/**
 * Delete group
 */
function handleDelete($conn) {
    $groupId = $_GET['group_id'] ?? null;
    
    if (!$groupId) {
        echo json_encode(['error' => 'Group ID is required']);
        return;
    }
    
    $sql = "DELETE FROM message_groups WHERE group_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $groupId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete group']);
    }
    
    $stmt->close();
}

/**
 * Add members to group
 */
function addMembersToGroup($conn, $groupId, $memberIds) {
    $sql = "INSERT INTO message_group_members (group_id, member_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($memberIds as $memberId) {
        $stmt->bind_param('ii', $groupId, $memberId);
        $stmt->execute();
    }
    
    $stmt->close();
}
?>
