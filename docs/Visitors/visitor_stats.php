<?php
require_once '../Attendance/config.php';

header('Content-Type: application/json');

try {
    $stats = [];
    
    // Total visitors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM visitors");
    $stats['total_visitors'] = $stmt->fetch()['total'];
    
    // New visitors (last 7 days)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['new_visitors'] = $stmt->fetch()['count'];
    
    // Pending follow-ups
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE follow_up_status = 'pending'");
    $stats['pending_followups'] = $stmt->fetch()['count'];
    
    // Contacted visitors
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE follow_up_status = 'contacted'");
    $stats['contacted'] = $stmt->fetch()['count'];
    
    // Scheduled follow-ups
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE follow_up_status = 'scheduled'");
    $stats['scheduled'] = $stmt->fetch()['count'];
    
    // Converted to members
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM visitors WHERE converted_to_member = TRUE");
    $stats['converted_members'] = $stmt->fetch()['count'];
    
    // Returning visitors (visited more than once)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT v.visitor_id) as count 
        FROM visitors v 
        INNER JOIN (
            SELECT visitor_id, COUNT(*) as visit_count 
            FROM attendance 
            WHERE visitor_id IS NOT NULL 
            GROUP BY visitor_id 
            HAVING visit_count > 1
        ) a ON v.visitor_id = a.visitor_id");
    $stats['returning_visitors'] = $stmt->fetch()['count'];
    
    // First-time visitors
    $stats['first_time_visitors'] = $stats['total_visitors'] - $stats['returning_visitors'];
    
    // Conversion rate
    if ($stats['total_visitors'] > 0) {
        $stats['conversion_rate'] = round(($stats['converted_members'] / $stats['total_visitors']) * 100, 1);
    } else {
        $stats['conversion_rate'] = 0;
    }
    
    // Visitors by source
    $stmt = $pdo->query("SELECT source, COUNT(*) as count 
        FROM visitors 
        GROUP BY source 
        ORDER BY count DESC");
    $stats['by_source'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent visitors (last 10 with full details)
    $stmt = $pdo->query("SELECT v.name, v.phone, v.email, v.source,
        v.follow_up_status, v.assigned_to, v.created_at,
        MAX(a.check_in_date) as last_visit
        FROM visitors v
        LEFT JOIN attendance a ON v.visitor_id = a.visitor_id
        GROUP BY v.visitor_id
        ORDER BY v.created_at DESC
        LIMIT 10");
    $stats['recent_visitors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Urgent follow-ups (pending and visited over 3 days ago)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT v.visitor_id) as count
        FROM visitors v
        LEFT JOIN attendance a ON v.visitor_id = a.visitor_id
        WHERE v.follow_up_status = 'pending'
        AND a.check_in_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)");
    $stats['urgent_followups'] = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
