<?php
/**
 * Get Recipients API
 * Retrieve available recipients for message sending
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'groups':
        getGroups($conn);
        break;
    case 'departments':
        getDepartments($conn);
        break;
    case 'ministries':
        getMinistries($conn);
        break;
    case 'members':
        getMembers($conn);
        break;
    case 'group_members':
        getGroupMembers($conn);
        break;
    case 'search':
        searchMembers($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();

/**
 * Get all message groups
 */
function getGroups($conn) {
    $sql = "SELECT group_id, group_name, group_type, description, 
            (SELECT COUNT(*) FROM message_group_members WHERE group_id = message_groups.group_id) as member_count
            FROM message_groups ORDER BY group_name";
    $result = $conn->query($sql);
    
    $groups = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'groups' => $groups]);
}

/**
 * Get all church groups/departments
 */
function getDepartments($conn) {
    $sql = "SELECT DISTINCT church_group as name, COUNT(*) as member_count 
            FROM members 
            WHERE church_group IS NOT NULL AND church_group != '' AND LOWER(status) = 'active'
            GROUP BY church_group 
            ORDER BY church_group";
    $result = $conn->query($sql);
    
    $departments = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'departments' => $departments]);
}

/**
 * Get all ministries/leadership roles
 */
function getMinistries($conn) {
    $sql = "SELECT DISTINCT leadership_role as name, COUNT(*) as member_count 
            FROM members 
            WHERE leadership_role IS NOT NULL AND leadership_role != '' AND LOWER(status) = 'active'
            GROUP BY leadership_role 
            ORDER BY leadership_role";
    $result = $conn->query($sql);
    
    $ministries = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ministries[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'ministries' => $ministries]);
}

/**
 * Get all members
 */
function getMembers($conn) {
    $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone, church_group, photo_path 
            FROM members 
            WHERE LOWER(status) = 'active' 
            ORDER BY first_name, last_name";
    $result = $conn->query($sql);
    
    $members = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'members' => $members]);
}

/**
 * Get members in a specific group
 */
function getGroupMembers($conn) {
    $groupId = intval($_GET['group_id'] ?? 0);
    
    if ($groupId <= 0) {
        echo json_encode(['error' => 'Invalid group ID']);
        return;
    }
    
    $sql = "SELECT m.member_id, CONCAT(m.first_name, ' ', m.last_name) as name, m.email, m.phone 
            FROM members m 
            INNER JOIN message_group_members mgm ON m.member_id = mgm.member_id 
            WHERE mgm.group_id = ? AND LOWER(m.status) = 'active'
            ORDER BY m.first_name, m.last_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $groupId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'members' => $members]);
}

/**
 * Search members
 */
function searchMembers($conn) {
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'members' => []]);
        return;
    }
    
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    
    $sql = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name, email, phone, church_group, photo_path 
            FROM members 
            WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?) 
            AND LOWER(status) = 'active'
            ORDER BY first_name, last_name
            LIMIT 20";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'members' => $members]);
}
?>
