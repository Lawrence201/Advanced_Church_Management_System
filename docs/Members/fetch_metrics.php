<?php
// fetch_metrics.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For testing, remove in production

$host = 'localhost';
$dbname = 'church_management_system';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Total Members
    $totalMembers = $conn->query("SELECT COUNT(*) FROM members")->fetchColumn();

    // Active/Inactive Members
    $activeMembers = $conn->query("SELECT COUNT(*) FROM members WHERE status = 'Active'")->fetchColumn();
    $inactiveMembers = $conn->query("SELECT COUNT(*) FROM members WHERE status = 'Inactive'")->fetchColumn();

    // Leadership Roles
    $pastors = $conn->query("SELECT COUNT(*) FROM members WHERE leadership_role = 'Pastor'")->fetchColumn();
    $ministers = $conn->query("SELECT COUNT(*) FROM members WHERE leadership_role = 'Minister'")->fetchColumn();
    $groupLeaders = $conn->query("SELECT COUNT(*) FROM members WHERE leadership_role = 'Group leader'")->fetchColumn();

    // Gender
    $males = $conn->query("SELECT COUNT(*) FROM members WHERE gender = 'Male'")->fetchColumn();
    $females = $conn->query("SELECT COUNT(*) FROM members WHERE gender = 'Female'")->fetchColumn();

    // Children (age < 18 years)
    $children = $conn->query("SELECT COUNT(*) FROM members WHERE TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18")->fetchColumn();

    // Baptism Status
    $baptized = $conn->query("SELECT COUNT(*) FROM members WHERE baptism_status = 'Baptized'")->fetchColumn();
    $notBaptized = $conn->query("SELECT COUNT(*) FROM members WHERE baptism_status = 'Not baptized'")->fetchColumn();
    $pendingBaptism = $conn->query("SELECT COUNT(*) FROM members WHERE baptism_status = 'Pending'")->fetchColumn();

    // Church Groups
    $kabod = $conn->query("SELECT COUNT(*) FROM members WHERE church_group = 'Kabod'")->fetchColumn();
    $dunamis = $conn->query("SELECT COUNT(*) FROM members WHERE church_group = 'Dunamis'")->fetchColumn();
    $judah = $conn->query("SELECT COUNT(*) FROM members WHERE church_group = 'Judah'")->fetchColumn();
    $karis = $conn->query("SELECT COUNT(*) FROM members WHERE church_group = 'Karis'")->fetchColumn();

    // Birthday This Month (current month)
    $currentMonth = date('m');
    $birthdayThisMonth = $conn->query("SELECT COUNT(*) FROM members WHERE MONTH(date_of_birth) = $currentMonth")->fetchColumn();

    // Total Events (from events table)
    $totalEvents = 0;
    try {
        $totalEvents = $conn->query("SELECT COUNT(*) FROM events WHERE status != 'Cancelled'")->fetchColumn();
    } catch (PDOException $e) {
        // If events table doesn't exist, just set to 0
        $totalEvents = 0;
    }

    $metrics = [
        'total_members' => (int)$totalMembers,
        'active_members' => (int)$activeMembers,
        'inactive_members' => (int)$inactiveMembers,
        'pastors' => (int)$pastors,
        'ministers' => (int)$ministers,
        'group_leaders' => (int)$groupLeaders,
        'males' => (int)$males,
        'females' => (int)$females,
        'children' => (int)$children,
        'baptized' => (int)$baptized,
        'not_baptized' => (int)$notBaptized,
        'pending_baptism' => (int)$pendingBaptism,
        'kabod' => (int)$kabod,
        'dunamis' => (int)$dunamis,
        'judah' => (int)$judah,
        'karis' => (int)$karis,
        'birthday_this_month' => (int)$birthdayThisMonth,
        'total_events' => (int)$totalEvents
    ];

    echo json_encode($metrics);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
$conn = null;
