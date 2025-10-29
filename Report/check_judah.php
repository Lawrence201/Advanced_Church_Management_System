<?php
$conn = new mysqli('localhost', 'root', '', 'church_management_system');

echo "<h2>Church Group Distribution</h2>";

// Check all members and their church groups
$sql = "SELECT 
            church_group,
            status,
            COUNT(*) as count
        FROM members 
        GROUP BY church_group, status
        ORDER BY church_group, status";

$result = $conn->query($sql);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Church Group</th><th>Status</th><th>Count</th></tr>";

$total = 0;
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>{$row['church_group']}</strong></td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['count']}</td>";
    echo "</tr>";
    $total += $row['count'];
}
echo "<tr style='background: #f0f0f0;'>";
echo "<td colspan='2'><strong>TOTAL</strong></td>";
echo "<td><strong>$total</strong></td>";
echo "</tr>";
echo "</table>";

echo "<h3>Members by Church Group (All Statuses)</h3>";
$sql = "SELECT 
            church_group,
            COUNT(*) as count
        FROM members 
        WHERE church_group IS NOT NULL AND church_group != ''
        GROUP BY church_group
        ORDER BY church_group";

$result = $conn->query($sql);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Church Group</th><th>Total Members</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>{$row['church_group']}</strong></td>";
    echo "<td>{$row['count']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Check for Judah Specifically</h3>";
$sql = "SELECT * FROM members WHERE church_group = 'Judah'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found {$result->num_rows} member(s) in Judah</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Church Group</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['member_id']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td><strong>{$row['church_group']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No members found in Judah church group</p>";
    echo "<p>This is why Judah doesn't appear in the Ministry Distribution chart.</p>";
}

$conn->close();
?>

<hr>
<h3>Solution</h3>
<p>If Judah has no members:</p>
<ol>
    <li>Add members to Judah church group, OR</li>
    <li>Update existing members to assign them to Judah</li>
</ol>

<p><a href="get_executive_summary.php" target="_blank">Test API</a> | <a href="report.html">Back to Report</a></p>
