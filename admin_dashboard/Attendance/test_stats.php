<?php
// Direct test to see if we can fetch data
require_once 'config.php';

echo "<h2>Testing Database Connection and Stats</h2>";

try {
    // Test 1: Check total members
    echo "<h3>Test 1: Total Active Members</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM members WHERE status = 'Active'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Total Active Members: " . $result['total'] . "<br>";
    
    // Test 2: Show all members
    echo "<h3>Test 2: All Active Members</h3>";
    $stmt = $pdo->prepare("SELECT member_id, first_name, last_name, gender, date_of_birth FROM members WHERE status = 'Active'");
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($members);
    echo "</pre>";
    
    // Test 3: Check attendance records
    echo "<h3>Test 3: All Attendance Records</h3>";
    $stmt = $pdo->prepare("SELECT * FROM attendance ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($attendance);
    echo "</pre>";
    
    // Test 4: Check visitors
    echo "<h3>Test 4: All Visitors</h3>";
    $stmt = $pdo->prepare("SELECT * FROM visitors ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($visitors);
    echo "</pre>";
    
    echo "<h3>✅ Database Connection Successful!</h3>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>