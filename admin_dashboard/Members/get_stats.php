<?php
/**
 * Get Member Statistics API
 * Returns member statistics for dashboard display
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

$conn = getDBConnection();

try {
    $stats = [];

    // Total members
    $result = $conn->query("SELECT COUNT(*) as total FROM members");
    $stats['total_members'] = $result->fetch_assoc()['total'];

    // Active members
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status = 'Active'");
    $stats['active_members'] = $result->fetch_assoc()['total'];

    // Inactive members
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE status = 'Inactive'");
    $stats['inactive_members'] = $result->fetch_assoc()['total'];

    // Leadership Roles
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE leadership_role = 'Pastor'");
    $stats['pastors'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE leadership_role = 'Minister'");
    $stats['ministers'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE leadership_role = 'Group leader'");
    $stats['group_leaders'] = $result->fetch_assoc()['total'];

    // Gender
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE gender = 'Male'");
    $stats['males'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE gender = 'Female'");
    $stats['females'] = $result->fetch_assoc()['total'];

    // Children (assuming children are those under 18)
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18");
    $stats['children'] = $result->fetch_assoc()['total'];

    // Baptism Status
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE baptism_status = 'Baptized'");
    $stats['baptized'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE baptism_status = 'Not baptized'");
    $stats['not_baptized'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE baptism_status = 'Pending'");
    $stats['pending_baptism'] = $result->fetch_assoc()['total'];

    // Church Groups
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE church_group = 'Kabod'");
    $stats['kabod'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE church_group = 'Dunamis'");
    $stats['dunamis'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE church_group = 'Judah'");
    $stats['judah'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE church_group = 'Karis'");
    $stats['karis'] = $result->fetch_assoc()['total'];

    // Birthday this month
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE MONTH(date_of_birth) = MONTH(CURDATE())");
    $stats['birthday_this_month'] = $result->fetch_assoc()['total'];

    // Total Events (from events table)
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM events WHERE status != 'Cancelled'");
        $stats['total_events'] = $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        $stats['total_events'] = 0;
    }

    // New members (joined in last 3 months)
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)");
    $stats['new_members'] = $result->fetch_assoc()['total'];

    // Visitors
    $result = $conn->query("SELECT COUNT(*) as total FROM members WHERE membership_type = 'Visitor'");
    $stats['visitors'] = $result->fetch_assoc()['total'];

    // Group statistics
    $result = $conn->query("SELECT church_group, COUNT(*) as count FROM members WHERE church_group != '' AND church_group IS NOT NULL GROUP BY church_group");
    $stats['groups'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['groups'][$row['church_group']] = $row['count'];
    }

    // Ministry statistics
    $result = $conn->query("
        SELECT mi.ministry_name, COUNT(*) as count
        FROM member_ministries mm
        JOIN ministries mi ON mm.ministry_id = mi.ministry_id
        GROUP BY mi.ministry_name
    ");
    $stats['ministries'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['ministries'][$row['ministry_name']] = $row['count'];
    }

    $conn->close();

    sendResponse(true, 'Statistics fetched successfully', $stats);

} catch (Exception $e) {
    $conn->close();
    sendResponse(false, 'Error: ' . $e->getMessage());
}
?>
