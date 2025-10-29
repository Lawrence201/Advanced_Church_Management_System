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

    // Special case: if service_id is '0' and date is '1970-01-01', return all members for initial count
    if ($service_id === '0' && $check_in_date === '1970-01-01') {
        $stmt = $pdo->prepare("
            SELECT
                m.member_id,
                CONCAT(m.first_name, ' ', m.last_name) as name,
                m.phone,
                m.email,
                m.church_group as ministry,
                COALESCE(m.leadership_role, 'Member') as position,
                m.status,
                m.photo_path,
                GROUP_CONCAT(min.ministry_name SEPARATOR ', ') as ministries
            FROM members m
            LEFT JOIN member_ministries mm ON m.member_id = mm.member_id
            LEFT JOIN ministries min ON mm.ministry_id = min.ministry_id
            GROUP BY m.member_id, m.first_name, m.last_name, m.phone, m.email, m.church_group, m.leadership_role, m.status, m.photo_path
            ORDER BY m.last_name, m.first_name
        ");
        $stmt->execute();
        $allMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $allMembers]);
        exit();
    }

    if (empty($check_in_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Date is required']);
        exit();
    }

    // Get all members who haven't checked in for the specific date (regardless of status)
    // Query by date only if service_id is '0' or empty
    if (empty($service_id) || $service_id === '0') {
        $stmt = $pdo->prepare("
            SELECT
                m.member_id,
                CONCAT(m.first_name, ' ', m.last_name) as name,
                m.phone,
                m.email,
                m.church_group as ministry,
                COALESCE(m.leadership_role, 'Member') as position,
                m.status,
                m.photo_path,
                GROUP_CONCAT(min.ministry_name SEPARATOR ', ') as ministries
            FROM members m
            LEFT JOIN member_ministries mm ON m.member_id = mm.member_id
            LEFT JOIN ministries min ON mm.ministry_id = min.ministry_id
            WHERE m.member_id NOT IN (
                SELECT a.member_id
                FROM attendance a
                WHERE a.member_id IS NOT NULL
                AND a.check_in_date = ?
                AND a.status = 'present'
            )
            GROUP BY m.member_id, m.first_name, m.last_name, m.phone, m.email, m.church_group, m.leadership_role, m.status, m.photo_path
            ORDER BY m.last_name, m.first_name
        ");
        $stmt->execute([$check_in_date]);
    } else {
        $stmt = $pdo->prepare("
            SELECT
                m.member_id,
                CONCAT(m.first_name, ' ', m.last_name) as name,
                m.phone,
                m.email,
                m.church_group as ministry,
                COALESCE(m.leadership_role, 'Member') as position,
                m.status,
                m.photo_path,
                GROUP_CONCAT(min.ministry_name SEPARATOR ', ') as ministries
            FROM members m
            LEFT JOIN member_ministries mm ON m.member_id = mm.member_id
            LEFT JOIN ministries min ON mm.ministry_id = min.ministry_id
            WHERE m.member_id NOT IN (
                SELECT a.member_id
                FROM attendance a
                WHERE a.member_id IS NOT NULL
                AND a.service_id = ?
                AND a.check_in_date = ?
                AND a.status = 'present'
            )
            GROUP BY m.member_id, m.first_name, m.last_name, m.phone, m.email, m.church_group, m.leadership_role, m.status, m.photo_path
            ORDER BY m.last_name, m.first_name
        ");
        $stmt->execute([$service_id, $check_in_date]);
    }
    $absentMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $absentMembers]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
