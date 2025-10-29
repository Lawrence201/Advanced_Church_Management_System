<?php
header('Content-Type: application/json');
require_once 'config.php';

$conn = getDBConnection();

// Get search query if provided
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build SQL query
$sql = "SELECT 
    member_id,
    CONCAT(first_name, ' ', last_name) AS full_name,
    email,
    phone,
    church_group
FROM members
WHERE status = 'Active'";

if (!empty($search)) {
    $sql .= " AND (
        first_name LIKE '%$search%' 
        OR last_name LIKE '%$search%' 
        OR email LIKE '%$search%'
        OR CONCAT(first_name, ' ', last_name) LIKE '%$search%'
    )";
}

$sql .= " ORDER BY first_name, last_name LIMIT 50";

$result = $conn->query($sql);

$members = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
}

$conn->close();

echo json_encode([
    'success' => true,
    'members' => $members
]);
?>
