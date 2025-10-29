<?php
/**
 * Get Birthday Analytics Data
 * Returns birthday distribution by month for charts
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $conn = new PDO("mysql:host=localhost;dbname=church_management_system", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get birthday count for each month
    $stmt = $conn->query("
        SELECT
            MONTH(date_of_birth) as month,
            COUNT(*) as count
        FROM members
        WHERE date_of_birth IS NOT NULL
        GROUP BY MONTH(date_of_birth)
        ORDER BY MONTH(date_of_birth)
    ");

    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize all 12 months with 0
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $chartData = [];
    foreach ($months as $monthNum => $monthName) {
        $chartData[] = [
            'month' => $monthNum,
            'month_name' => $monthName,
            'count' => 0
        ];
    }

    // Fill in actual data
    foreach ($monthlyData as $data) {
        $monthIndex = (int)$data['month'] - 1;
        $chartData[$monthIndex]['count'] = (int)$data['count'];
    }

    // Calculate statistics
    $totalBirthdays = array_sum(array_column($chartData, 'count'));
    $maxMonth = $chartData[0];
    $minMonth = $chartData[0];

    foreach ($chartData as $data) {
        if ($data['count'] > $maxMonth['count']) {
            $maxMonth = $data;
        }
        if ($data['count'] < $minMonth['count'] && $data['count'] > 0) {
            $minMonth = $data;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'monthly' => $chartData,
            'total_birthdays' => $totalBirthdays,
            'highest_month' => $maxMonth,
            'lowest_month' => $minMonth,
            'average_per_month' => round($totalBirthdays / 12, 1)
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
