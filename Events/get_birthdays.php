<?php
/**
 * Get Member Birthdays API
 * Returns birthday data filtered by month with categories
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $conn = new PDO("mysql:host=localhost;dbname=church_management_system", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get month parameter (default to current month)
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    $currentDay = (int)date('d');
    $currentMonth = (int)date('m');

    // Get all members with birthdays in the specified month
    $stmt = $conn->prepare("
        SELECT
            member_id,
            CONCAT(first_name, ' ', last_name) as name,
            first_name,
            last_name,
            date_of_birth,
            phone,
            email,
            church_group,
            DAY(date_of_birth) as birth_day,
            MONTH(date_of_birth) as birth_month,
            TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) as age
        FROM members
        WHERE MONTH(date_of_birth) = :month
        AND date_of_birth IS NOT NULL
        ORDER BY DAY(date_of_birth) ASC
    ");
    $stmt->execute(['month' => $month]);
    $allBirthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Categorize birthdays
    $celebrated = [];
    $upcoming = [];

    foreach ($allBirthdays as $member) {
        $birthDay = (int)$member['birth_day'];

        // If it's the current month
        if ($month == $currentMonth) {
            if ($birthDay < $currentDay) {
                // Already celebrated this month
                $celebrated[] = $member;
            } else {
                // Upcoming this month (including today)
                $upcoming[] = $member;
            }
        } else {
            // For other months, all are upcoming
            $upcoming[] = $member;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'month' => $month,
            'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
            'all_birthdays' => $allBirthdays,
            'celebrated' => $celebrated,
            'upcoming' => $upcoming,
            'total_count' => count($allBirthdays),
            'celebrated_count' => count($celebrated),
            'upcoming_count' => count($upcoming)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn = null;
?>
