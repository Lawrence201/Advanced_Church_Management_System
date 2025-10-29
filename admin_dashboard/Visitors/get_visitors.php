<?php
require_once '../Attendance/config.php';

header('Content-Type: application/json');

try {
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    // Base query
    $query = "SELECT 
        v.visitor_id,
        v.name,
        v.phone,
        v.email,
        v.source,
        v.visitors_purpose,
        v.follow_up_status,
        v.follow_up_date,
        v.follow_up_notes,
        v.assigned_to,
        v.visit_count,
        v.last_visit_date,
        v.converted_to_member,
        v.created_at,
        v.updated_at,
        COUNT(DISTINCT a.attendance_id) as total_visits,
        MAX(a.check_in_date) as latest_visit,
        MIN(a.check_in_date) as first_visit,
        GROUP_CONCAT(DISTINCT DATE_FORMAT(a.check_in_date, '%Y-%m-%d') ORDER BY a.check_in_date DESC) as visit_dates
    FROM visitors v
    LEFT JOIN attendance a ON v.visitor_id = a.visitor_id
    WHERE 1=1";
    
    // Apply filters
    $params = [];
    $types = '';
    
    if ($filter === 'pending') {
        $query .= " AND v.follow_up_status = 'pending'";
    } elseif ($filter === 'contacted') {
        $query .= " AND v.follow_up_status = 'contacted'";
    } elseif ($filter === 'scheduled') {
        $query .= " AND v.follow_up_status = 'scheduled'";
    } elseif ($filter === 'completed') {
        $query .= " AND v.follow_up_status = 'completed'";
    } elseif ($filter === 'no_response') {
        $query .= " AND v.follow_up_status = 'no_response'";
    } elseif ($filter === 'converted') {
        $query .= " AND v.converted_to_member = TRUE";
    } elseif ($filter === 'new') {
        $query .= " AND v.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter === 'returning') {
        $query .= " AND (SELECT COUNT(*) FROM attendance WHERE visitor_id = v.visitor_id) > 1";
    }
    
    // Apply search
    if (!empty($search)) {
        $query .= " AND (v.name LIKE ? OR v.phone LIKE ? OR v.email LIKE ? OR v.source LIKE ?)";
        $searchTerm = "%{$search}%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $types = 'ssss';
    }
    
    $query .= " GROUP BY v.visitor_id ORDER BY v.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    foreach ($visitors as &$visitor) {
        // Update visit count from actual attendance
        $visitor['visit_count'] = (int)$visitor['total_visits'];
        
        // Calculate days since last visit
        if ($visitor['latest_visit']) {
            $lastVisit = new DateTime($visitor['latest_visit']);
            $now = new DateTime();
            $diff = $now->diff($lastVisit);
            $visitor['days_since_visit'] = $diff->days;
        } else {
            $visitor['days_since_visit'] = null;
        }
        
        // Determine urgency for follow-up
        if ($visitor['follow_up_status'] === 'pending' && $visitor['days_since_visit'] !== null) {
            if ($visitor['days_since_visit'] <= 3) {
                $visitor['urgency'] = 'high';
            } elseif ($visitor['days_since_visit'] <= 7) {
                $visitor['urgency'] = 'medium';
            } else {
                $visitor['urgency'] = 'low';
            }
        } else {
            $visitor['urgency'] = 'none';
        }
        
        // Format dates
        $visitor['formatted_first_visit'] = $visitor['first_visit'] ? date('M d, Y', strtotime($visitor['first_visit'])) : 'N/A';
        $visitor['formatted_latest_visit'] = $visitor['latest_visit'] ? date('M d, Y', strtotime($visitor['latest_visit'])) : 'N/A';
        $visitor['formatted_follow_up_date'] = $visitor['follow_up_date'] ? date('M d, Y', strtotime($visitor['follow_up_date'])) : null;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $visitors,
        'count' => count($visitors)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
