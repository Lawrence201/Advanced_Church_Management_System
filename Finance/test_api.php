<?php
// Simple test to check if the API is working
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Testing Finance API</h1>";

// Test 1: Check database connection
require_once 'config.php';
echo "<h2>Test 1: Database Connection</h2>";
try {
    $conn = getDBConnection();
    echo "✓ Database connected successfully<br>";
    echo "Database: " . DB_NAME . "<br><br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br><br>";
    exit;
}

// Test 2: Count offerings in database
echo "<h2>Test 2: Count Offerings</h2>";
$result = $conn->query("SELECT COUNT(*) as total FROM offerings");
$count = $result->fetch_assoc()['total'];
echo "Total offerings in database: <strong>$count</strong><br><br>";

// Test 3: Get offerings for this year
echo "<h2>Test 3: This Year Offerings</h2>";
$startDate = date('Y-01-01');
$endDate = date('Y-m-d');
echo "Date range: $startDate to $endDate<br>";

$sql = "SELECT * FROM offerings WHERE date BETWEEN '$startDate' AND '$endDate' ORDER BY date DESC LIMIT 10";
$result = $conn->query($sql);
$yearOfferings = [];
while ($row = $result->fetch_assoc()) {
    $yearOfferings[] = $row;
}

echo "Offerings found: <strong>" . count($yearOfferings) . "</strong><br>";
if (count($yearOfferings) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Date</th><th>Service Type</th><th>Amount</th><th>Counted By</th></tr>";
    foreach ($yearOfferings as $offering) {
        echo "<tr>";
        echo "<td>" . $offering['date'] . "</td>";
        echo "<td>" . $offering['service_type'] . "</td>";
        echo "<td>₵" . number_format($offering['amount_collected'], 2) . "</td>";
        echo "<td>" . $offering['counted_by'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No offerings found for this year!</p>";
}
echo "<br>";

// Test 4: Get summary
echo "<h2>Test 4: Summary for This Year</h2>";
$sql = "SELECT
        COALESCE(SUM(amount_collected), 0) as total_amount,
        COUNT(*) as total_count,
        COALESCE(AVG(amount_collected), 0) as avg_amount
        FROM offerings WHERE date BETWEEN '$startDate' AND '$endDate'";
$summary = $conn->query($sql)->fetch_assoc();

echo "Total Amount: <strong>₵" . number_format($summary['total_amount'], 2) . "</strong><br>";
echo "Total Count: <strong>" . $summary['total_count'] . "</strong><br>";
echo "Average: <strong>₵" . number_format($summary['avg_amount'], 2) . "</strong><br><br>";

// Test 5: Test the actual API endpoint
echo "<h2>Test 5: Test API Endpoint</h2>";
$apiUrl = "http://localhost/Church_Management_System/admin_dashboard/Finance/get_finance_data.php?type=offerings&range=year";
echo "API URL: <a href='$apiUrl' target='_blank'>$apiUrl</a><br>";
echo "<iframe src='$apiUrl' width='100%' height='200' style='border: 1px solid #ccc; margin-top: 10px;'></iframe>";

$conn->close();
?>
