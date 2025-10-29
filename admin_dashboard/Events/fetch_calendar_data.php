<?php
/**
 * Fetch Calendar Data API
 * Retrieves events and birthdays for calendar display
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_config.php';

try {
    $conn = getDBConnection();

    // Get month and year parameters
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

    // Validate month (1-12)
    if ($month < 1 || $month > 12) {
        $month = date('n');
    }

    // Calculate first and last day of the month
    $firstDay = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
    $lastDay = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

    // Fetch events for the month
    $eventsSql = "SELECT
        id,
        name,
        type,
        category,
        start_date,
        start_time,
        end_date,
        end_time,
        location,
        status,
        is_virtual,
        max_capacity
    FROM events
    WHERE status != 'Cancelled'
    AND (
        (start_date BETWEEN ? AND ?)
        OR (end_date BETWEEN ? AND ?)
        OR (start_date <= ? AND end_date >= ?)
    )
    ORDER BY start_date ASC, start_time ASC";

    $eventsStmt = $conn->prepare($eventsSql);
    $eventsStmt->bind_param('ssssss', $firstDay, $lastDay, $firstDay, $lastDay, $firstDay, $lastDay);
    $eventsStmt->execute();
    $eventsResult = $eventsStmt->get_result();

    $events = [];
    while ($row = $eventsResult->fetch_assoc()) {
        $events[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'type' => $row['type'],
            'category' => $row['category'],
            'start_date' => $row['start_date'],
            'start_time' => $row['start_time'],
            'end_date' => $row['end_date'],
            'end_time' => $row['end_time'],
            'location' => $row['location'],
            'status' => $row['status'],
            'is_virtual' => intval($row['is_virtual']),
            'max_capacity' => intval($row['max_capacity']),
            'event_type' => 'event'
        ];
    }
    $eventsStmt->close();

    // Fetch birthdays for the month
    // Using DAY() and MONTH() to match birthdays regardless of year
    $birthdaysSql = "SELECT
        member_id,
        first_name,
        last_name,
        date_of_birth,
        phone,
        email
    FROM members
    WHERE status = 'active'
    AND date_of_birth IS NOT NULL
    AND MONTH(date_of_birth) = ?
    ORDER BY DAY(date_of_birth) ASC";

    $birthdaysStmt = $conn->prepare($birthdaysSql);
    $birthdaysStmt->bind_param('i', $month);
    $birthdaysStmt->execute();
    $birthdaysResult = $birthdaysStmt->get_result();

    $birthdays = [];
    while ($row = $birthdaysResult->fetch_assoc()) {
        // Calculate the birthday date for the current year
        $birthDay = date('d', strtotime($row['date_of_birth']));
        $birthdayDate = sprintf('%04d-%02d-%02d', $year, $month, $birthDay);

        // Calculate age
        $birthYear = date('Y', strtotime($row['date_of_birth']));
        $age = $year - $birthYear;

        $birthdays[] = [
            'member_id' => intval($row['member_id']),
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'date_of_birth' => $row['date_of_birth'],
            'birthday_date' => $birthdayDate,
            'day' => intval($birthDay),
            'age' => $age,
            'phone' => $row['phone'],
            'email' => $row['email'],
            'event_type' => 'birthday'
        ];
    }
    $birthdaysStmt->close();

    // Create a calendar structure with dates and their associated events/birthdays
    $calendarDays = [];
    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $dayEvents = array_filter($events, function($event) use ($currentDate) {
            return $event['start_date'] <= $currentDate && $event['end_date'] >= $currentDate;
        });

        $dayBirthdays = array_filter($birthdays, function($birthday) use ($day) {
            return $birthday['day'] === $day;
        });

        $calendarDays[$day] = [
            'date' => $currentDate,
            'day' => $day,
            'events' => array_values($dayEvents),
            'birthdays' => array_values($dayBirthdays),
            'has_events' => count($dayEvents) > 0,
            'has_birthdays' => count($dayBirthdays) > 0,
            'has_activities' => (count($dayEvents) + count($dayBirthdays)) > 0,
            'event_count' => count($dayEvents),
            'birthday_count' => count($dayBirthdays)
        ];
    }

    // Calculate calendar grid info (for rendering the calendar)
    $firstDayOfWeek = date('w', mktime(0, 0, 0, $month, 1, $year)); // 0 = Sunday
    $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));

    $conn->close();

    sendResponse(true, 'Calendar data retrieved successfully', [
        'year' => $year,
        'month' => $month,
        'month_name' => $monthName,
        'first_day_of_week' => $firstDayOfWeek,
        'days_in_month' => $daysInMonth,
        'calendar_days' => $calendarDays,
        'all_events' => $events,
        'all_birthdays' => $birthdays,
        'total_events' => count($events),
        'total_birthdays' => count($birthdays)
    ]);

} catch (Exception $e) {
    sendResponse(false, 'Error fetching calendar data: ' . $e->getMessage());
}
?>
