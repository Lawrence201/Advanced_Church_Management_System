<?php
/**
 * Executive Summary API
 * Comprehensive system-wide analytics and insights
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/executive_summary_errors.log');

// Database connection
$host = 'localhost';
$dbname = 'church_management_system';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    $summary = [];
    
    // ==================== MEMBERSHIP ANALYTICS ====================
    
    // Total Members & Growth
    $sql = "SELECT 
                COUNT(*) as total_members,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_members,
                SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive_members,
                SUM(CASE WHEN membership_type = 'Visitor' THEN 1 ELSE 0 END) as visitors,
                SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_members_30d,
                SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as new_members_90d
            FROM members";
    $result = $conn->query($sql);
    $membership = $result->fetch_assoc();
    
    // Member growth rate
    $sql = "SELECT COUNT(*) as last_month FROM members WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $result = $conn->query($sql);
    $lastMonth = $result->fetch_assoc()['last_month'];
    $growthRate = $lastMonth > 0 ? round((($membership['new_members_30d'] - $lastMonth) / $lastMonth) * 100, 1) : 0;
    
    $summary['membership'] = [
        'total' => intval($membership['total_members']),
        'active' => intval($membership['active_members']),
        'inactive' => intval($membership['inactive_members']),
        'visitors' => intval($membership['visitors']),
        'new_30d' => intval($membership['new_members_30d']),
        'new_90d' => intval($membership['new_members_90d']),
        'growth_rate' => $growthRate,
        'retention_rate' => $membership['total_members'] > 0 ? round(($membership['active_members'] / $membership['total_members']) * 100, 1) : 0
    ];
    
    // ==================== FINANCIAL ANALYTICS ====================
    
    // Total Income (All Sources)
    $sql = "SELECT 
                COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount_collected as amount, date FROM offerings WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
                UNION ALL
                SELECT amount, date FROM tithes WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
                UNION ALL
                SELECT amount_collected as amount, date FROM project_offerings WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
                UNION ALL
                SELECT amount, date FROM welfare_contributions WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
            ) as all_income";
    $result = $conn->query($sql);
    $currentIncome = floatval($result->fetch_assoc()['total']);
    
    // Last month income
    $sql = "SELECT 
                COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount_collected as amount FROM offerings WHERE MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                UNION ALL
                SELECT amount FROM tithes WHERE MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                UNION ALL
                SELECT amount_collected as amount FROM project_offerings WHERE MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                UNION ALL
                SELECT amount FROM welfare_contributions WHERE MONTH(date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            ) as all_income";
    $result = $conn->query($sql);
    $lastMonthIncome = floatval($result->fetch_assoc()['total']);
    
    $incomeGrowth = $lastMonthIncome > 0 ? round((($currentIncome - $lastMonthIncome) / $lastMonthIncome) * 100, 1) : 0;
    
    // Expenses
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $expenses = floatval($result->fetch_assoc()['total']);
    
    $summary['financial'] = [
        'total_income' => $currentIncome,
        'last_month_income' => $lastMonthIncome,
        'income_growth' => $incomeGrowth,
        'total_expenses' => $expenses,
        'net_income' => $currentIncome - $expenses,
        'financial_health' => $currentIncome > 0 ? round((($currentIncome - $expenses) / $currentIncome) * 100, 1) : 0
    ];
    
    // ==================== ATTENDANCE ANALYTICS ====================
    
    // Current month attendance (count of present check-ins)
    $sql = "SELECT COUNT(*) as total_attendance
            FROM attendance 
            WHERE MONTH(check_in_date) = MONTH(CURDATE()) 
            AND YEAR(check_in_date) = YEAR(CURDATE())
            AND status = 'present'";
    $result = $conn->query($sql);
    $currentAttendance = intval($result->fetch_assoc()['total_attendance']);
    
    // Last month attendance
    $sql = "SELECT COUNT(*) as total_attendance 
            FROM attendance 
            WHERE MONTH(check_in_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND YEAR(check_in_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND status = 'present'";
    $result = $conn->query($sql);
    $lastMonthAttendance = intval($result->fetch_assoc()['total_attendance']);
    
    $attendanceGrowth = $lastMonthAttendance > 0 ? round((($currentAttendance - $lastMonthAttendance) / $lastMonthAttendance) * 100, 1) : 0;
    
    $summary['attendance'] = [
        'total_records' => $currentAttendance,
        'avg_attendance' => $currentAttendance,
        'last_month_avg' => $lastMonthAttendance,
        'growth_rate' => $attendanceGrowth,
        'attendance_rate' => $membership['active_members'] > 0 ? round(($currentAttendance / $membership['active_members']) * 100, 1) : 0
    ];
    
    // ==================== EVENTS ANALYTICS ====================
    
    $sql = "SELECT 
                COUNT(*) as total_events,
                SUM(CASE WHEN start_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
                SUM(CASE WHEN start_date < CURDATE() THEN 1 ELSE 0 END) as past_events
            FROM events 
            WHERE status = 'Published'";
    $result = $conn->query($sql);
    $events = $result->fetch_assoc();
    
    // Event attendance (unique members who attended this month)
    $sql = "SELECT COUNT(DISTINCT member_id) as unique_attendees 
            FROM attendance 
            WHERE MONTH(check_in_date) = MONTH(CURDATE())
            AND YEAR(check_in_date) = YEAR(CURDATE())
            AND status = 'present'";
    $result = $conn->query($sql);
    $eventAttendees = intval($result->fetch_assoc()['unique_attendees']);
    
    $summary['events'] = [
        'total' => intval($events['total_events']),
        'upcoming' => intval($events['upcoming_events']),
        'past' => intval($events['past_events']),
        'unique_attendees' => $eventAttendees,
        'engagement_rate' => $membership['active_members'] > 0 ? round(($eventAttendees / $membership['active_members']) * 100, 1) : 0
    ];
    
    // ==================== COMMUNICATION ANALYTICS ====================
    
    $sql = "SELECT 
                COUNT(*) as total_messages,
                SUM(total_recipients) as total_recipients,
                SUM(total_sent) as total_sent,
                AVG(total_sent / NULLIF(total_recipients, 0) * 100) as delivery_rate
            FROM messages 
            WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $messages = $result->fetch_assoc();
    
    $summary['communication'] = [
        'total_messages' => intval($messages['total_messages']),
        'total_recipients' => intval($messages['total_recipients'] ?? 0),
        'total_sent' => intval($messages['total_sent'] ?? 0),
        'delivery_rate' => round(floatval($messages['delivery_rate'] ?? 0), 1)
    ];
    
    // ==================== MINISTRY BREAKDOWN ====================
    
    // Get all church groups with member counts (including inactive)
    $sql = "SELECT 
                church_group,
                COUNT(*) as count
            FROM members 
            WHERE church_group IS NOT NULL 
            AND church_group != ''
            GROUP BY church_group
            ORDER BY church_group";
    $result = $conn->query($sql);
    $ministries = [];
    while ($row = $result->fetch_assoc()) {
        $ministries[] = [
            'name' => $row['church_group'],
            'count' => intval($row['count'])
        ];
    }
    
    // If no ministries found, add default groups with 0 count
    if (empty($ministries)) {
        $defaultGroups = ['Dunamis', 'Kabod', 'Judah', 'Karis'];
        foreach ($defaultGroups as $group) {
            $ministries[] = [
                'name' => $group,
                'count' => 0
            ];
        }
    }
    
    $summary['ministries'] = $ministries;
    
    // ==================== ENGAGEMENT METRICS ====================
    
    // Members who attended in last 30 days
    $sql = "SELECT COUNT(DISTINCT member_id) as engaged 
            FROM attendance 
            WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND status = 'present'";
    $result = $conn->query($sql);
    $engaged = intval($result->fetch_assoc()['engaged']);
    
    // Members at risk (no attendance in 30+ days)
    $sql = "SELECT COUNT(*) as at_risk FROM members m 
            WHERE status = 'Active' 
            AND member_id NOT IN (
                SELECT DISTINCT member_id 
                FROM attendance 
                WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND status = 'present'
            )";
    $result = $conn->query($sql);
    $atRisk = intval($result->fetch_assoc()['at_risk']);
    
    $summary['engagement'] = [
        'engaged_members' => $engaged,
        'at_risk_members' => $atRisk,
        'engagement_rate' => $membership['active_members'] > 0 ? round(($engaged / $membership['active_members']) * 100, 1) : 0
    ];
    
    // ==================== TRENDS (Last 6 Months) ====================
    
    // Membership trend
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM members 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month";
    $result = $conn->query($sql);
    $membershipTrend = [];
    while ($row = $result->fetch_assoc()) {
        $membershipTrend[] = [
            'month' => date('M Y', strtotime($row['month'] . '-01')),
            'count' => intval($row['count'])
        ];
    }
    
    // Financial trend
    $sql = "SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                SUM(amount) as total
            FROM (
                SELECT date, amount_collected as amount FROM offerings WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                UNION ALL
                SELECT date, amount FROM tithes WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                UNION ALL
                SELECT date, amount_collected as amount FROM project_offerings WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                UNION ALL
                SELECT date, amount FROM welfare_contributions WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            ) as all_income
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month";
    $result = $conn->query($sql);
    $financialTrend = [];
    while ($row = $result->fetch_assoc()) {
        $financialTrend[] = [
            'month' => date('M Y', strtotime($row['month'] . '-01')),
            'amount' => floatval($row['total'])
        ];
    }
    
    // Attendance trend (count of present check-ins per month)
    $sql = "SELECT 
                DATE_FORMAT(check_in_date, '%Y-%m') as month,
                COUNT(*) as total_attendance
            FROM attendance 
            WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            AND status = 'present'
            GROUP BY DATE_FORMAT(check_in_date, '%Y-%m')
            ORDER BY month";
    $result = $conn->query($sql);
    $attendanceTrend = [];
    while ($row = $result->fetch_assoc()) {
        $attendanceTrend[] = [
            'month' => date('M Y', strtotime($row['month'] . '-01')),
            'attendance' => intval($row['total_attendance'])
        ];
    }
    
    $summary['trends'] = [
        'membership' => $membershipTrend,
        'financial' => $financialTrend,
        'attendance' => $attendanceTrend
    ];
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => $summary,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('Executive Summary Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
