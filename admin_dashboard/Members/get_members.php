<?php
/**
 * Get Members API
 * Fetches members from database with optional filters
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

$conn = getDBConnection();

// Get filter parameters
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Base query
$query = "SELECT
    m.member_id,
    m.first_name,
    m.last_name,
    CONCAT(m.first_name, ' ', m.last_name) as name,
    m.email,
    m.phone,
    m.gender,
    m.marital_status,
    m.occupation,
    m.address,
    m.city,
    m.region,
    m.status,
    m.church_group,
    m.leadership_role,
    m.baptism_status,
    m.spiritual_growth,
    m.membership_type,
    m.date_of_birth,
    m.notes,
    m.photo_path,
    m.created_at,
    m.updated_at,
    ec.emergency_name,
    ec.emergency_phone,
    ec.emergency_relation,
    GROUP_CONCAT(DISTINCT mi.ministry_name) as ministries,
    GROUP_CONCAT(DISTINCT d.department_name) as departments
FROM members m
LEFT JOIN emergency_contacts ec ON m.member_id = ec.member_id
LEFT JOIN member_ministries mm ON m.member_id = mm.member_id
LEFT JOIN ministries mi ON mm.ministry_id = mi.ministry_id
LEFT JOIN member_departments md ON m.member_id = md.member_id
LEFT JOIN departments d ON md.department_id = d.department_id
WHERE 1=1";

// Apply filters
if ($filter === 'active') {
    $query .= " AND m.status = 'active'";
} elseif ($filter === 'inactive') {
    $query .= " AND m.status = 'inactive'";
} elseif ($filter === 'new') {
    // Members joined in last 3 months
    $query .= " AND m.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
} elseif ($filter === 'visitor') {
    $query .= " AND m.membership_type = 'visitor'";
}

// Apply search
if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $query .= " AND (
        m.first_name LIKE '{$searchTerm}' OR
        m.last_name LIKE '{$searchTerm}' OR
        m.email LIKE '{$searchTerm}' OR
        m.phone LIKE '{$searchTerm}' OR
        m.occupation LIKE '{$searchTerm}'
    )";
}

$query .= " GROUP BY m.member_id ORDER BY m.created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    // Calculate real attendance percentage from attendance table
    // Get total services in the last 90 days
    $attendanceQuery = "SELECT 
        COUNT(DISTINCT DATE(a.check_in_date)) as attended_days,
        (
            SELECT COUNT(DISTINCT DATE(att.check_in_date)) 
            FROM attendance att 
            WHERE att.check_in_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        ) as total_service_days
    FROM attendance a
    WHERE a.member_id = ? 
    AND a.check_in_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    AND a.status = 'present'";
    
    $attendanceStmt = $conn->prepare($attendanceQuery);
    $attendanceStmt->bind_param("i", $row['member_id']);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();
    $attendanceData = $attendanceResult->fetch_assoc();
    
    // Calculate attendance percentage
    if ($attendanceData['total_service_days'] > 0) {
        $row['attendance'] = round(($attendanceData['attended_days'] / $attendanceData['total_service_days']) * 100);
    } else {
        $row['attendance'] = 0;
    }
    $attendanceStmt->close();

    // Calculate engagement based on multiple factors
    // 1. Attendance rate (40% weight)
    // 2. Recent activity - attended in last 30 days (30% weight)
    // 3. Ministry involvement (30% weight)
    
    $engagementScore = 0;
    
    // Attendance score
    $engagementScore += ($row['attendance'] * 0.4);
    
    // Recent activity score
    $recentActivityQuery = "SELECT COUNT(*) as recent_count 
    FROM attendance 
    WHERE member_id = ? 
    AND check_in_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND status = 'present'";
    $recentStmt = $conn->prepare($recentActivityQuery);
    $recentStmt->bind_param("i", $row['member_id']);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();
    $recentData = $recentResult->fetch_assoc();
    $recentStmt->close();
    
    // If attended in last 30 days, add score based on frequency
    if ($recentData['recent_count'] > 0) {
        $recentScore = min(100, $recentData['recent_count'] * 25); // Max 100
        $engagementScore += ($recentScore * 0.3);
    }
    
    // Ministry involvement score
    if ($row['ministries'] && $row['ministries'] != '') {
        $ministryCount = count(explode(',', $row['ministries']));
        $ministryScore = min(100, $ministryCount * 50); // Max 100
        $engagementScore += ($ministryScore * 0.3);
    }
    
    // Categorize engagement
    if ($engagementScore >= 70) {
        $row['engagement'] = 'High';
    } elseif ($engagementScore >= 40) {
        $row['engagement'] = 'Medium';
    } else {
        $row['engagement'] = 'Low';
    }

    // Format last seen date - use last attendance date if available
    $lastSeenQuery = "SELECT MAX(check_in_date) as last_seen 
    FROM attendance 
    WHERE member_id = ? 
    AND status = 'present'";
    $lastSeenStmt = $conn->prepare($lastSeenQuery);
    $lastSeenStmt->bind_param("i", $row['member_id']);
    $lastSeenStmt->execute();
    $lastSeenResult = $lastSeenStmt->get_result();
    $lastSeenData = $lastSeenResult->fetch_assoc();
    $lastSeenStmt->close();
    
    if ($lastSeenData['last_seen']) {
        $row['lastSeen'] = date('n/j/Y', strtotime($lastSeenData['last_seen']));
    } else {
        $row['lastSeen'] = date('n/j/Y', strtotime($row['updated_at']));
    }
    
    $row['joined'] = date('n/j/Y', strtotime($row['created_at']));

    // Format group display name
    $row['group'] = $row['ministries'] ? str_replace(',', ', ', $row['ministries']) : 'No Ministry';

    $members[] = $row;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(DISTINCT m.member_id) as total FROM members m WHERE 1=1";

if ($filter === 'active') {
    $countQuery .= " AND m.status = 'active'";
} elseif ($filter === 'inactive') {
    $countQuery .= " AND m.status = 'inactive'";
} elseif ($filter === 'new') {
    $countQuery .= " AND m.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
} elseif ($filter === 'visitor') {
    $countQuery .= " AND m.membership_type = 'visitor'";
}

if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $countQuery .= " AND (
        m.first_name LIKE '{$searchTerm}' OR
        m.last_name LIKE '{$searchTerm}' OR
        m.email LIKE '{$searchTerm}' OR
        m.phone LIKE '{$searchTerm}'
    )";
}

$countResult = $conn->query($countQuery);
$totalCount = $countResult->fetch_assoc()['total'];

$stmt->close();
$conn->close();

sendResponse(true, 'Members fetched successfully', [
    'members' => $members,
    'total' => $totalCount,
    'showing' => count($members)
]);
?>
