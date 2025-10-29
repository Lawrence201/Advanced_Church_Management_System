<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $service_id = $_GET['service_id'] ?? '';
    $check_in_date = $_GET['check_in_date'] ?? date('Y-m-d');

    if (!$check_in_date) {
        echo json_encode(['success' => false, 'message' => 'Date is required']);
        exit;
    }

    // Note: Auto-initialization of absent records has been removed
    // Absent members are now calculated dynamically in get_stats.php and absent.php

    // Fetch attendance data with better structure
    // If service_id is '0' or empty, query by date only (for historical data)
    if (empty($service_id) || $service_id === '0') {
        $stmt = $pdo->prepare("
            SELECT 
                a.attendance_id,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN CONCAT(m.first_name, ' ', m.last_name)
                    ELSE v.name 
                END as name,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.phone
                    ELSE v.phone 
                END as phone,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.email
                    ELSE v.email 
                END as email,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.church_group
                    ELSE 'Visitor' 
                END as ministry,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN COALESCE(m.leadership_role, 'Member')
                    ELSE 'Visitor' 
                END as position,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.photo_path
                    ELSE NULL 
                END as photo,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.member_id
                    ELSE NULL 
                END as member_id,
                a.check_in_time,
                a.status
            FROM attendance a
            LEFT JOIN members m ON a.member_id = m.member_id
            LEFT JOIN visitors v ON a.visitor_id = v.visitor_id
            WHERE a.check_in_date = ?
            AND a.status IN ('present', 'visitor')
            ORDER BY a.check_in_time DESC
        ");
        $stmt->execute([$check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                a.attendance_id,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN CONCAT(m.first_name, ' ', m.last_name)
                    ELSE v.name 
                END as name,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.phone
                    ELSE v.phone 
                END as phone,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.email
                    ELSE v.email 
                END as email,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.church_group
                    ELSE 'Visitor' 
                END as ministry,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN COALESCE(m.leadership_role, 'Member')
                    ELSE 'Visitor' 
                END as position,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.photo_path
                    ELSE NULL 
                END as photo,
                CASE 
                    WHEN m.member_id IS NOT NULL THEN m.member_id
                    ELSE NULL 
                END as member_id,
                a.check_in_time,
                a.status
            FROM attendance a
            LEFT JOIN members m ON a.member_id = m.member_id
            LEFT JOIN visitors v ON a.visitor_id = v.visitor_id
            WHERE a.service_id = ? AND a.check_in_date = ?
            AND a.status IN ('present', 'visitor')
            ORDER BY a.check_in_time DESC
        ");
        $stmt->execute([$service_id, $check_in_date]);
    }
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch ministries for each member
    foreach ($attendance as &$record) {
        if ($record['member_id']) {
            $ministryStmt = $pdo->prepare("
                SELECT GROUP_CONCAT(min.ministry_name SEPARATOR ', ') as ministries
                FROM member_ministries mm
                JOIN ministries min ON mm.ministry_id = min.ministry_id
                WHERE mm.member_id = ?
            ");
            $ministryStmt->execute([$record['member_id']]);
            $ministryResult = $ministryStmt->fetch(PDO::FETCH_ASSOC);
            $record['ministries'] = $ministryResult['ministries'] ?? 'None';
        } else {
            $record['ministries'] = 'Visitor';
        }
    }

    echo json_encode(['success' => true, 'data' => $attendance]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
