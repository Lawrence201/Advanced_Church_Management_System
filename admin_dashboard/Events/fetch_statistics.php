<?php
/**
 * Fetch Event Statistics API
 * Retrieves statistics and insights for events
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

try {
    $conn = getDBConnection();

    // Get total events count
    $totalEventsQuery = "SELECT COUNT(*) as total FROM events WHERE status != 'Cancelled'";
    $totalResult = $conn->query($totalEventsQuery);
    $totalEvents = $totalResult->fetch_assoc()['total'];

    // Get upcoming events count
    $upcomingQuery = "SELECT COUNT(*) as total FROM events
                     WHERE start_date >= CURDATE() AND status = 'Published'";
    $upcomingResult = $conn->query($upcomingQuery);
    $upcomingEvents = $upcomingResult->fetch_assoc()['total'];

    // Get draft events count
    $draftQuery = "SELECT COUNT(*) as total FROM events WHERE status = 'Draft'";
    $draftResult = $conn->query($draftQuery);
    $draftEvents = $draftResult->fetch_assoc()['total'];

    // Get events by type
    $eventsByTypeQuery = "SELECT
        type,
        COUNT(*) as count
    FROM events
    WHERE status != 'Cancelled'
    GROUP BY type
    ORDER BY count DESC";
    $typeResult = $conn->query($eventsByTypeQuery);
    $eventsByType = [];
    while ($row = $typeResult->fetch_assoc()) {
        $eventsByType[] = [
            'type' => $row['type'],
            'count' => intval($row['count'])
        ];
    }

    // Get events by category
    $eventsByCategoryQuery = "SELECT
        category,
        COUNT(*) as count
    FROM events
    WHERE status != 'Cancelled'
    GROUP BY category
    ORDER BY count DESC";
    $categoryResult = $conn->query($eventsByCategoryQuery);
    $eventsByCategory = [];
    while ($row = $categoryResult->fetch_assoc()) {
        $eventsByCategory[] = [
            'category' => $row['category'],
            'count' => intval($row['count'])
        ];
    }

    // Get events needing volunteers
    $needVolunteersQuery = "SELECT COUNT(*) as total FROM events
                           WHERE volunteers_needed > 0
                           AND start_date >= CURDATE()
                           AND status = 'Published'";
    $volunteerResult = $conn->query($needVolunteersQuery);
    $needsVolunteers = $volunteerResult->fetch_assoc()['total'];

    // Get total volunteer roles needed
    $totalVolunteersQuery = "SELECT SUM(quantity_needed) as total
                            FROM event_volunteer_roles vr
                            INNER JOIN events e ON vr.event_id = e.id
                            WHERE e.start_date >= CURDATE()
                            AND e.status = 'Published'";
    $totalVolResult = $conn->query($totalVolunteersQuery);
    $totalVolunteersNeeded = $totalVolResult->fetch_assoc()['total'] ?? 0;

    // Get this month's events count
    $thisMonthQuery = "SELECT COUNT(*) as total FROM events
                      WHERE MONTH(start_date) = MONTH(CURDATE())
                      AND YEAR(start_date) = YEAR(CURDATE())
                      AND status != 'Cancelled'";
    $monthResult = $conn->query($thisMonthQuery);
    $thisMonthEvents = $monthResult->fetch_assoc()['total'];

    // Get next week's events
    $nextWeekQuery = "SELECT
        id,
        name,
        type,
        start_date,
        start_time,
        location
    FROM events
    WHERE start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND status = 'Published'
    ORDER BY start_date ASC, start_time ASC
    LIMIT 5";
    $nextWeekResult = $conn->query($nextWeekQuery);
    $nextWeekEvents = [];
    while ($row = $nextWeekResult->fetch_assoc()) {
        $nextWeekEvents[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'type' => $row['type'],
            'start_date' => $row['start_date'],
            'start_time' => $row['start_time'],
            'location' => $row['location'],
            'date_formatted' => formatDate($row['start_date']),
            'time_formatted' => formatTime($row['start_time'])
        ];
    }

    // Get recent events (past 30 days)
    $recentQuery = "SELECT COUNT(*) as total FROM events
                   WHERE start_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
                   AND status != 'Cancelled'";
    $recentResult = $conn->query($recentQuery);
    $recentEvents = $recentResult->fetch_assoc()['total'];

    // Get virtual events count
    $virtualQuery = "SELECT COUNT(*) as total FROM events
                    WHERE is_virtual = 1
                    AND start_date >= CURDATE()
                    AND status = 'Published'";
    $virtualResult = $conn->query($virtualQuery);
    $virtualEvents = $virtualResult->fetch_assoc()['total'];

    // Get events requiring registration
    $registrationQuery = "SELECT COUNT(*) as total FROM events
                         WHERE require_registration = 1
                         AND start_date >= CURDATE()
                         AND status = 'Published'";
    $registrationResult = $conn->query($registrationQuery);
    $requiresRegistration = $registrationResult->fetch_assoc()['total'];

    // Calculate mock attendance growth (you can replace with real data)
    $attendanceGrowth = 22; // Mock data - replace with actual calculation

    $conn->close();

    sendResponse(true, 'Statistics retrieved successfully', [
        'total_events' => intval($totalEvents),
        'upcoming_events' => intval($upcomingEvents),
        'draft_events' => intval($draftEvents),
        'this_month_events' => intval($thisMonthEvents),
        'recent_events' => intval($recentEvents),
        'virtual_events' => intval($virtualEvents),
        'requires_registration' => intval($requiresRegistration),
        'needs_volunteers' => intval($needsVolunteers),
        'total_volunteers_needed' => intval($totalVolunteersNeeded),
        'attendance_growth' => $attendanceGrowth,
        'events_by_type' => $eventsByType,
        'events_by_category' => $eventsByCategory,
        'next_week_events' => $nextWeekEvents
    ]);

} catch (Exception $e) {
    sendResponse(false, 'Error fetching statistics: ' . $e->getMessage());
}
?>
