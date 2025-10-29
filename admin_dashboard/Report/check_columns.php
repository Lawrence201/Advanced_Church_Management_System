<?php
$conn = new mysqli('localhost', 'root', '', 'church_management_system');

$tables = ['offerings', 'tithes', 'project_offerings', 'welfare_contributions', 'expenses'];

echo "<h2>Financial Table Columns</h2>";

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>{$row['Field']}</strong></td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "Table does not exist<br><br>";
    }
}

$conn->close();
?>
