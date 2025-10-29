<?php
/**
 * Get Member Payment History
 * Fetches individual member's tithe and welfare payment records
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../Members/db_config.php';

$conn = getDBConnection();
$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'get_members':
            // Get all members for dropdown
            $response['data'] = getAllMembers($conn);
            $response['success'] = true;
            break;

        case 'get_payment_history':
            // Get payment history for specific member
            $member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

            if ($member_id <= 0) {
                throw new Exception('Invalid member ID');
            }

            $response['data'] = getMemberPaymentHistory($conn, $member_id, $start_date, $end_date);
            $response['success'] = true;
            break;

        case 'get_all_members_summary':
            // Get summary for all members in a period
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
            
            $response['data'] = getAllMembersSummary($conn, $start_date, $end_date);
            $response['success'] = true;
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['error'] = true;
}

$conn->close();
echo json_encode($response);

// ========================================
// FUNCTIONS
// ========================================

function getAllMembers($conn) {
    $sql = "SELECT 
                member_id as id,
                CONCAT(first_name, ' ', last_name) as full_name,
                email,
                phone,
                photo_path
            FROM members 
            ORDER BY first_name, last_name";
    
    $result = $conn->query($sql);
    $members = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
    }
    
    return $members;
}

function getMemberPaymentHistory($conn, $member_id, $start_date, $end_date) {
    $data = [
        'member_info' => [],
        'tithes' => [],
        'welfare' => [],
        'summary' => [
            'total_tithes' => 0,
            'total_welfare' => 0,
            'grand_total' => 0,
            'tithe_count' => 0,
            'welfare_count' => 0,
            'total_transactions' => 0
        ]
    ];

    // Get member information
    $sql = "SELECT 
                member_id as id,
                CONCAT(first_name, ' ', last_name) as full_name,
                email,
                phone,
                date_of_birth
            FROM members 
            WHERE member_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data['member_info'] = $result->fetch_assoc();
    } else {
        throw new Exception('Member not found');
    }

    // Build date filter
    $date_filter = "";
    $params = [$member_id];
    $param_types = "i";

    if (!empty($start_date) && !empty($end_date)) {
        $date_filter = " AND date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $param_types .= "ss";
    } elseif (!empty($start_date)) {
        $date_filter = " AND date >= ?";
        $params[] = $start_date;
        $param_types .= "s";
    } elseif (!empty($end_date)) {
        $date_filter = " AND date <= ?";
        $params[] = $end_date;
        $param_types .= "s";
    }

    // Get TITHE payments
    $sql = "SELECT 
                tithe_id as id,
                amount,
                date,
                payment_method,
                receipt_number as reference_number,
                notes,
                'Tithe' as payment_type
            FROM tithes 
            WHERE member_id = ? $date_filter
            ORDER BY date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tithe_total = 0;
    while ($row = $result->fetch_assoc()) {
        $data['tithes'][] = $row;
        $tithe_total += floatval($row['amount']);
    }
    
    $data['summary']['total_tithes'] = $tithe_total;
    $data['summary']['tithe_count'] = count($data['tithes']);

    // Get WELFARE payments
    $sql = "SELECT 
                welfare_id as id,
                amount,
                date,
                payment_method,
                payment_period as reference_number,
                notes,
                'Welfare' as payment_type
            FROM welfare_contributions 
            WHERE member_id = ? $date_filter
            ORDER BY date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $welfare_total = 0;
    while ($row = $result->fetch_assoc()) {
        $data['welfare'][] = $row;
        $welfare_total += floatval($row['amount']);
    }
    
    $data['summary']['total_welfare'] = $welfare_total;
    $data['summary']['welfare_count'] = count($data['welfare']);
    
    // Calculate totals
    $data['summary']['grand_total'] = $tithe_total + $welfare_total;
    $data['summary']['total_transactions'] = count($data['tithes']) + count($data['welfare']);

    return $data;
}

function getAllMembersSummary($conn, $start_date, $end_date) {
    $date_filter = "";
    $params = [];
    $param_types = "";

    if (!empty($start_date) && !empty($end_date)) {
        $date_filter = " WHERE date BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        $param_types = "ss";
    } elseif (!empty($start_date)) {
        $date_filter = " WHERE date >= ?";
        $params = [$start_date];
        $param_types = "s";
    } elseif (!empty($end_date)) {
        $date_filter = " WHERE date <= ?";
        $params = [$end_date];
        $param_types = "s";
    }

    $sql = "SELECT 
                m.member_id as id,
                CONCAT(m.first_name, ' ', m.last_name) as full_name,
                COALESCE(SUM(t.amount), 0) as total_tithes,
                COUNT(DISTINCT t.tithe_id) as tithe_count,
                COALESCE(SUM(w.amount), 0) as total_welfare,
                COUNT(DISTINCT w.welfare_id) as welfare_count,
                (COALESCE(SUM(t.amount), 0) + COALESCE(SUM(w.amount), 0)) as grand_total
            FROM members m
            LEFT JOIN tithes t ON m.member_id = t.member_id $date_filter
            LEFT JOIN welfare_contributions w ON m.member_id = w.member_id $date_filter
            GROUP BY m.member_id, m.first_name, m.last_name
            HAVING grand_total > 0
            ORDER BY grand_total DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        // Bind parameters twice (once for tithes, once for welfare)
        $all_params = array_merge($params, $params);
        $all_types = $param_types . $param_types;
        $stmt->bind_param($all_types, ...$all_params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    return $members;
}
?>
