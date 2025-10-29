<?php
$conn = new mysqli('localhost', 'root', '', 'church_management_system');

echo "<h2>Attendance Table Structure</h2>";

$result = $conn->query("DESCRIBE attendance");
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
    
    // Show sample data
    echo "<h3>Sample Data</h3>";
    $result = $conn->query("SELECT * FROM attendance LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        $first = true;
        while ($row = $result->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $col) {
                    echo "<th>$col</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>$val</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No data in attendance table";
    }
} else {
    echo "Table does not exist";
}

$conn->close();
?>
