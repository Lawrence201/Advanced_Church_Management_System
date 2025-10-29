<?php
header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'data' => []];

try {
    $type = isset($_GET['type']) ? $_GET['type'] : 'overview';
    $dateRange = isset($_GET['range']) ? $_GET['range'] : 'month';

    // Calculate date ranges
    $today = date('Y-m-d');
    $startDate = '';
    $endDate = $today;

    // Handle custom date range
    if ($dateRange === 'custom') {
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $today;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            throw new Exception('Invalid date format. Use YYYY-MM-DD');
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            throw new Exception('Start date must be before or equal to end date');
        }
    } else {
        // Handle predefined ranges
        switch ($dateRange) {
            case 'today':
                $startDate = $today;
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'month':
                $startDate = date('Y-m-01');
                break;
            case 'quarter':
                $startDate = date('Y-m-01', strtotime('-3 months'));
                break;
            case 'year':
                $startDate = date('Y-01-01');
                break;
            case 'last_year':
                $startDate = date('Y-01-01', strtotime('-1 year'));
                $endDate = date('Y-12-31', strtotime('-1 year'));
                break;
            case 'all':
                $startDate = '1900-01-01';
                $endDate = '9999-12-31';
                break;
            default:
                $startDate = date('Y-m-01');
        }
    }

    switch ($type) {
        case 'overview':
            $response['data'] = getAttendanceOverview($pdo, $startDate, $endDate);
            break;
        case 'trends':
            $weeks = isset($_GET['weeks']) ? intval($_GET['weeks']) : 12;
            $response['data'] = getAttendanceTrends($pdo, $weeks);
            break;
        case 'demographics':
            $response['data'] = getDemographics($pdo, $startDate, $endDate);
            break;
        case 'service_breakdown':
            $response['data'] = getServiceBreakdown($pdo, $startDate, $endDate);
            break;
        case 'growth_metrics':
            $response['data'] = getGrowthMetrics($pdo);
            break;
        case 'peak_times':
            $response['data'] = getPeakTimes($pdo, $startDate, $endDate);
            break;
        case 'retention':
            $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
            $response['data'] = getRetentionMetrics($pdo, $months);
            break;
        default:
            throw new Exception('Invalid type parameter');
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

// ========================================
// HELPER FUNCTIONS
// ========================================

function getAttendanceOverview($pdo, $startDate, $endDate) {
    $data = [];

    // Total members registered
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM members");
    $stmt->execute();
    $data['total_members'] = intval($stmt->fetch()['total']);

    // Total unique members attended in period
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT member_id) as total
        FROM attendance
        WHERE member_id IS NOT NULL
        AND check_in_date BETWEEN ? AND ?
        AND status = 'present'
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['members_attended'] = intval($stmt->fetch()['total']);

    // Total visitors in period
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT visitor_id) as total
        FROM attendance
        WHERE visitor_id IS NOT NULL
        AND check_in_date BETWEEN ? AND ?
        AND status = 'visitor'
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['total_visitors'] = intval($stmt->fetch()['total']);

    // Average attendance per service
    $stmt = $pdo->prepare("
        SELECT AVG(service_count) as avg_attendance
        FROM (
            SELECT check_in_date, service_id, COUNT(*) as service_count
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND status IN ('present', 'visitor')
            GROUP BY check_in_date, service_id
        ) as daily_counts
    ");
    $stmt->execute([$startDate, $endDate]);
    $result = $stmt->fetch();
    $data['avg_attendance'] = $result['avg_attendance'] ? round(floatval($result['avg_attendance'])) : 0;

    // Peak attendance
    $stmt = $pdo->prepare("
        SELECT MAX(service_count) as peak, check_in_date, service_id
        FROM (
            SELECT check_in_date, service_id, COUNT(*) as service_count
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND status IN ('present', 'visitor')
            GROUP BY check_in_date, service_id
        ) as daily_counts
        GROUP BY check_in_date, service_id
        ORDER BY peak DESC
        LIMIT 1
    ");
    $stmt->execute([$startDate, $endDate]);
    $result = $stmt->fetch();
    $data['peak_attendance'] = $result ? intval($result['peak']) : 0;

    // Attendance rate
    if ($data['total_members'] > 0) {
        $data['attendance_rate'] = round(($data['members_attended'] / $data['total_members']) * 100, 1);
    } else {
        $data['attendance_rate'] = 0;
    }

    // Gender breakdown
    $stmt = $pdo->prepare("
        SELECT m.gender, COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN members m ON a.member_id = m.member_id
        WHERE a.check_in_date BETWEEN ? AND ?
        AND a.status = 'present'
        GROUP BY m.gender
    ");
    $stmt->execute([$startDate, $endDate]);
    $genderData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data['males_count'] = 0;
    $data['females_count'] = 0;

    foreach ($genderData as $row) {
        if (strtolower($row['gender']) === 'male') {
            $data['males_count'] = intval($row['count']);
        } elseif (strtolower($row['gender']) === 'female') {
            $data['females_count'] = intval($row['count']);
        }
    }

    // Children count (under 18)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN members m ON a.member_id = m.member_id
        WHERE a.check_in_date BETWEEN ? AND ?
        AND a.status = 'present'
        AND (YEAR(CURDATE()) - YEAR(m.date_of_birth)) < 18
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['children_count'] = intval($stmt->fetch()['count']);

    return $data;
}

function getAttendanceTrends($pdo, $weeks = 12) {
    $data = [];
    $currentDate = new DateTime();

    for ($i = $weeks - 1; $i >= 0; $i--) {
        $weekStart = clone $currentDate;
        $weekStart->modify("-$i weeks")->modify('monday this week');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');

        $weekLabel = $weekStart->format('M d');
        $startStr = $weekStart->format('Y-m-d');
        $endStr = $weekEnd->format('Y-m-d');

        // Total attendance for the week
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND status IN ('present', 'visitor')
        ");
        $stmt->execute([$startStr, $endStr]);
        $total = intval($stmt->fetch()['total']);

        // Members
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND status = 'present'
            AND member_id IS NOT NULL
        ");
        $stmt->execute([$startStr, $endStr]);
        $members = intval($stmt->fetch()['total']);

        // Visitors
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND status = 'visitor'
            AND visitor_id IS NOT NULL
        ");
        $stmt->execute([$startStr, $endStr]);
        $visitors = intval($stmt->fetch()['total']);

        $data[] = [
            'week' => $weekLabel,
            'total' => $total,
            'members' => $members,
            'visitors' => $visitors
        ];
    }

    return $data;
}

function getDemographics($pdo, $startDate, $endDate) {
    $data = [];

    // Age groups
    $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN (YEAR(CURDATE()) - YEAR(m.date_of_birth)) < 18 THEN 'Children (0-17)'
                WHEN (YEAR(CURDATE()) - YEAR(m.date_of_birth)) BETWEEN 18 AND 29 THEN 'Youth (18-29)'
                WHEN (YEAR(CURDATE()) - YEAR(m.date_of_birth)) BETWEEN 30 AND 49 THEN 'Adults (30-49)'
                WHEN (YEAR(CURDATE()) - YEAR(m.date_of_birth)) >= 50 THEN 'Seniors (50+)'
                ELSE 'Unknown'
            END as age_group,
            COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN members m ON a.member_id = m.member_id
        WHERE a.check_in_date BETWEEN ? AND ?
        AND a.status = 'present'
        GROUP BY age_group
        ORDER BY
            CASE age_group
                WHEN 'Children (0-17)' THEN 1
                WHEN 'Youth (18-29)' THEN 2
                WHEN 'Adults (30-49)' THEN 3
                WHEN 'Seniors (50+)' THEN 4
                ELSE 5
            END
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['age_groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marital status distribution
    $stmt = $pdo->prepare("
        SELECT m.marital_status, COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN members m ON a.member_id = m.member_id
        WHERE a.check_in_date BETWEEN ? AND ?
        AND a.status = 'present'
        AND m.marital_status IS NOT NULL
        GROUP BY m.marital_status
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['marital_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Church group distribution
    $stmt = $pdo->prepare("
        SELECT m.church_group, COUNT(DISTINCT a.member_id) as count
        FROM attendance a
        JOIN members m ON a.member_id = m.member_id
        WHERE a.check_in_date BETWEEN ? AND ?
        AND a.status = 'present'
        AND m.church_group IS NOT NULL
        GROUP BY m.church_group
        ORDER BY count DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['church_groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}

function getServiceBreakdown($pdo, $startDate, $endDate) {
    $data = [];

    // Attendance by service
    $stmt = $pdo->prepare("
        SELECT
            service_id,
            COUNT(*) as total_attendance,
            COUNT(DISTINCT check_in_date) as service_count,
            ROUND(COUNT(*) / COUNT(DISTINCT check_in_date)) as avg_per_service
        FROM attendance
        WHERE check_in_date BETWEEN ? AND ?
        AND status IN ('present', 'visitor')
        GROUP BY service_id
        ORDER BY total_attendance DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}

function getGrowthMetrics($pdo) {
    $data = [];

    // Current month
    $currentMonthStart = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');

    // Previous month
    $previousMonthStart = date('Y-m-01', strtotime('-1 month'));
    $previousMonthEnd = date('Y-m-t', strtotime('-1 month'));

    // Current month total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM attendance
        WHERE check_in_date BETWEEN ? AND ?
        AND status IN ('present', 'visitor')
    ");
    $stmt->execute([$currentMonthStart, $currentMonthEnd]);
    $currentTotal = intval($stmt->fetch()['total']);

    // Previous month total
    $stmt->execute([$previousMonthStart, $previousMonthEnd]);
    $previousTotal = intval($stmt->fetch()['total']);

    // Calculate growth
    if ($previousTotal > 0) {
        $growth = round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1);
    } else {
        $growth = $currentTotal > 0 ? 100 : 0;
    }

    $data['current_month'] = $currentTotal;
    $data['previous_month'] = $previousTotal;
    $data['growth_percentage'] = $growth;

    // New members attended this month
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT member_id) as total
        FROM attendance
        WHERE check_in_date BETWEEN ? AND ?
        AND status = 'present'
        AND member_id NOT IN (
            SELECT DISTINCT member_id
            FROM attendance
            WHERE check_in_date < ?
            AND member_id IS NOT NULL
        )
    ");
    $stmt->execute([$currentMonthStart, $currentMonthEnd, $currentMonthStart]);
    $data['new_attendees'] = intval($stmt->fetch()['total']);

    return $data;
}

function getPeakTimes($pdo, $startDate, $endDate) {
    $data = [];

    // Peak hours
    $stmt = $pdo->prepare("
        SELECT
            HOUR(check_in_time) as hour,
            COUNT(*) as count
        FROM attendance
        WHERE check_in_date BETWEEN ? AND ?
        AND check_in_time IS NOT NULL
        GROUP BY HOUR(check_in_time)
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['peak_hours'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Peak days
    $stmt = $pdo->prepare("
        SELECT
            DAYNAME(check_in_date) as day_name,
            COUNT(*) as count
        FROM attendance
        WHERE check_in_date BETWEEN ? AND ?
        GROUP BY DAYNAME(check_in_date), DAYOFWEEK(check_in_date)
        ORDER BY DAYOFWEEK(check_in_date)
    ");
    $stmt->execute([$startDate, $endDate]);
    $data['days_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}

function getRetentionMetrics($pdo, $months = 6) {
    $data = [];
    $currentDate = new DateTime();

    for ($i = $months - 1; $i >= 0; $i--) {
        $monthDate = clone $currentDate;
        $monthDate->modify("-$i months");
        $monthStart = $monthDate->format('Y-m-01');
        $monthEnd = $monthDate->format('Y-m-t');
        $monthName = $monthDate->format('M');

        // Previous month dates
        $prevMonthDate = clone $monthDate;
        $prevMonthDate->modify('-1 month');
        $prevMonthStart = $prevMonthDate->format('Y-m-01');
        $prevMonthEnd = $prevMonthDate->format('Y-m-t');

        // Regular attendees (attended this month and last month)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT member_id) as count
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND member_id IS NOT NULL
            AND status = 'present'
            AND member_id IN (
                SELECT DISTINCT member_id
                FROM attendance
                WHERE check_in_date BETWEEN ? AND ?
                AND member_id IS NOT NULL
            )
        ");
        $stmt->execute([$monthStart, $monthEnd, $prevMonthStart, $prevMonthEnd]);
        $regular = intval($stmt->fetch()['count']);

        // Returning attendees (attended this month, not last month, but before)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT member_id) as count
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND member_id IS NOT NULL
            AND status = 'present'
            AND member_id NOT IN (
                SELECT DISTINCT member_id
                FROM attendance
                WHERE check_in_date BETWEEN ? AND ?
                AND member_id IS NOT NULL
            )
            AND member_id IN (
                SELECT DISTINCT member_id
                FROM attendance
                WHERE check_in_date < ?
                AND member_id IS NOT NULL
            )
        ");
        $stmt->execute([$monthStart, $monthEnd, $prevMonthStart, $prevMonthEnd, $prevMonthStart]);
        $returning = intval($stmt->fetch()['count']);

        // First timers (attended this month for the first time ever)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT member_id) as count
            FROM attendance
            WHERE check_in_date BETWEEN ? AND ?
            AND member_id IS NOT NULL
            AND status = 'present'
            AND member_id NOT IN (
                SELECT DISTINCT member_id
                FROM attendance
                WHERE check_in_date < ?
                AND member_id IS NOT NULL
            )
        ");
        $stmt->execute([$monthStart, $monthEnd, $monthStart]);
        $firstTimers = intval($stmt->fetch()['count']);

        $data[] = [
            'month' => $monthName,
            'regular' => $regular,
            'returning' => $returning,
            'first_timers' => $firstTimers
        ];
    }

    return $data;
}
?>
