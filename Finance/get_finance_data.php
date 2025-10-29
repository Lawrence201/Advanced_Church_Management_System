<?php
header('Content-Type: application/json');
require_once 'config.php';

$conn = getDBConnection();
$response = ['success' => false, 'data' => []];

try {
    $type = isset($_GET['type']) ? $_GET['type'] : 'overview';
    $dateRange = isset($_GET['range']) ? $_GET['range'] : 'month';
    
    // Calculate date ranges
    $today = date('Y-m-d');
    $startDate = '';
    $endDate = $today;
    
    // Handle custom date range
    if ($dateRange === 'custom') {
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $today;
        
        // Validate dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            throw new Exception('Invalid date format. Use YYYY-MM-DD');
        }
        
        if (strtotime($startDate) > strtotime($endDate)) {
            throw new Exception('Start date must be before or equal to end date');
        }
    } else {
        // Handle predefined ranges
        switch ($dateRange) {
            case 'today':
                $startDate = $today;
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'month':
                $startDate = date('Y-m-01');
                break;
            case 'quarter':
                $startDate = date('Y-m-01', strtotime('-3 months'));
                break;
            case 'year':
                $startDate = date('Y-01-01');
                break;
            case 'last_year':
                $startDate = date('Y-01-01', strtotime('-1 year'));
                $endDate = date('Y-12-31', strtotime('-1 year'));
                break;
            case 'all':
                $startDate = '1900-01-01'; // All time - from year 1900
                $endDate = '9999-12-31'; // Maximum MySQL date - truly unlimited
                break;
            default:
                $startDate = '1900-01-01'; // Default to all time to show all data
                $endDate = '9999-12-31'; // Maximum MySQL date - truly unlimited
        }
    }
    
    switch ($type) {
        case 'overview':
            $response['data'] = getOverviewData($conn, $startDate, $endDate);
            break;
        case 'offerings':
            $response['data'] = getOfferingsData($conn, $startDate, $endDate);
            break;
        case 'project_offerings':
            $response['data'] = getProjectOfferingsData($conn, $startDate, $endDate);
            break;
        case 'tithes':
            $response['data'] = getTithesData($conn, $startDate, $endDate);
            break;
        case 'welfare':
            $response['data'] = getWelfareData($conn, $startDate, $endDate);
            break;
        case 'expenses':
            $response['data'] = getExpensesData($conn, $startDate, $endDate);
            break;
        case 'stats':
            $response['data'] = getFinancialStats($conn);
            break;
        case 'recent_transactions':
            $response['data'] = getRecentTransactions($conn);
            break;
        case 'offering_details':
            if (!isset($_GET['id'])) {
                throw new Exception('Offering ID is required');
            }
            $response['data'] = getOfferingDetails($conn, $_GET['id']);
            break;
        case 'project_offering_details':
            if (!isset($_GET['id'])) {
                throw new Exception('Project Offering ID is required');
            }
            $response['data'] = getProjectOfferingDetails($conn, $_GET['id']);
            break;
        case 'tithe_details':
            if (!isset($_GET['id'])) {
                throw new Exception('Tithe ID is required');
            }
            $response['data'] = getTitheDetails($conn, $_GET['id']);
            break;
        case 'welfare_details':
            if (!isset($_GET['id'])) {
                throw new Exception('Welfare ID is required');
            }
            $response['data'] = getWelfareDetails($conn, $_GET['id']);
            break;
        case 'expense_details':
            if (!isset($_GET['id'])) {
                throw new Exception('Expense ID is required');
            }
            $response['data'] = getExpenseDetails($conn, $_GET['id']);
            break;
        case 'financial_trends':
            $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
            $response['data'] = getFinancialTrendsData($conn, $months);
            break;
        case 'budget':
            $response['data'] = getBudgetData($conn, $startDate, $endDate);
            break;
        case 'donor_retention':
            $months = isset($_GET['months']) ? intval($_GET['months']) : 6;
            $response['data'] = getDonorRetentionData($conn, $months);
            break;
        default:
            throw new Exception('Invalid type parameter');
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);

// ========================================
// HELPER FUNCTIONS
// ========================================

function getOverviewData($conn, $startDate, $endDate) {
    $data = [];
    
    // Get total offerings
    $sql = "SELECT COALESCE(SUM(amount_collected), 0) as total FROM offerings WHERE date BETWEEN '$startDate' AND '$endDate'";
    $result = $conn->query($sql);
    $data['total_offerings'] = $result->fetch_assoc()['total'];
    
    // Get total tithes
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM tithes WHERE date BETWEEN '$startDate' AND '$endDate'";
    $result = $conn->query($sql);
    $data['total_tithes'] = $result->fetch_assoc()['total'];
    
    // Get total project offerings
    $sql = "SELECT COALESCE(SUM(amount_collected), 0) as total FROM project_offerings WHERE date BETWEEN '$startDate' AND '$endDate'";
    $result = $conn->query($sql);
    $data['total_project_offerings'] = $result->fetch_assoc()['total'];
    
    // Get total welfare
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM welfare_contributions WHERE date BETWEEN '$startDate' AND '$endDate'";
    $result = $conn->query($sql);
    $data['total_welfare'] = $result->fetch_assoc()['total'];
    
    // Get total expenses
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date BETWEEN '$startDate' AND '$endDate'";
    $result = $conn->query($sql);
    $data['total_expenses'] = $result->fetch_assoc()['total'];
    
    // Calculate total income
    $data['total_income'] = $data['total_offerings'] + $data['total_tithes'] + 
                            $data['total_project_offerings'] + $data['total_welfare'];
    
    // Calculate net balance
    $data['net_balance'] = $data['total_income'] - $data['total_expenses'];
    
    return $data;
}

function getFinancialStats($conn) {
    $stats = [];
    
    // Current month
    $currentMonth = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');
    
    // Previous month
    $previousMonth = date('Y-m-01', strtotime('-1 month'));
    $previousMonthEnd = date('Y-m-t', strtotime('-1 month'));
    
    // Get current month offerings
    $sql = "SELECT COALESCE(SUM(amount_collected), 0) as total FROM offerings WHERE date BETWEEN '$currentMonth' AND '$currentMonthEnd'";
    $currentOfferings = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    // Get previous month offerings
    $sql = "SELECT COALESCE(SUM(amount_collected), 0) as total FROM offerings WHERE date BETWEEN '$previousMonth' AND '$previousMonthEnd'";
    $previousOfferings = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    // Calculate percentage change
    $stats['offerings'] = [
        'total' => $currentOfferings,
        'change' => calculatePercentageChange($previousOfferings, $currentOfferings)
    ];
    
    // Repeat for tithes
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM tithes WHERE date BETWEEN '$currentMonth' AND '$currentMonthEnd'";
    $currentTithes = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM tithes WHERE date BETWEEN '$previousMonth' AND '$previousMonthEnd'";
    $previousTithes = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $stats['tithes'] = [
        'total' => $currentTithes,
        'change' => calculatePercentageChange($previousTithes, $currentTithes)
    ];
    
    // Project offerings
    $sql = "SELECT COALESCE(SUM(amount_collected), 0) as total FROM project_offerings WHERE date BETWEEN '$currentMonth' AND '$currentMonthEnd'";
    $currentProjectOfferings = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $sql = "SELECT COALESCE(SUM(amount_collected), 0) as total FROM project_offerings WHERE date BETWEEN '$previousMonth' AND '$previousMonthEnd'";
    $previousProjectOfferings = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $stats['project_offerings'] = [
        'total' => $currentProjectOfferings,
        'change' => calculatePercentageChange($previousProjectOfferings, $currentProjectOfferings)
    ];
    
    // Welfare
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM welfare_contributions WHERE date BETWEEN '$currentMonth' AND '$currentMonthEnd'";
    $currentWelfare = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM welfare_contributions WHERE date BETWEEN '$previousMonth' AND '$previousMonthEnd'";
    $previousWelfare = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $stats['welfare'] = [
        'total' => $currentWelfare,
        'change' => calculatePercentageChange($previousWelfare, $currentWelfare)
    ];
    
    // Expenses
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date BETWEEN '$currentMonth' AND '$currentMonthEnd' AND status = 'approved'";
    $currentExpenses = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date BETWEEN '$previousMonth' AND '$previousMonthEnd' AND status = 'approved'";
    $previousExpenses = floatval($conn->query($sql)->fetch_assoc()['total']);
    
    $stats['expenses'] = [
        'total' => $currentExpenses,
        'change' => calculatePercentageChange($previousExpenses, $currentExpenses)
    ];
    
    return $stats;
}

function getOfferingsData($conn, $startDate, $endDate) {
    $data = [];
    
    // Get offerings list
    $sql = "SELECT * FROM offerings WHERE date BETWEEN '$startDate' AND '$endDate' ORDER BY date DESC, service_time DESC LIMIT 50";
    $result = $conn->query($sql);
    
    $data['offerings'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['offerings'][] = $row;
    }
    
    // Get summary stats with explicit zero defaults
    $sql = "SELECT 
            COALESCE(SUM(amount_collected), 0) as total_amount,
            COUNT(*) as total_count,
            COALESCE(AVG(amount_collected), 0) as avg_amount
            FROM offerings WHERE date BETWEEN '$startDate' AND '$endDate'";
    $summary = $conn->query($sql)->fetch_assoc();
    
    // Ensure numeric values
    $data['summary'] = [
        'total_amount' => floatval($summary['total_amount']),
        'total_count' => intval($summary['total_count']),
        'avg_amount' => floatval($summary['avg_amount'])
    ];
    
    return $data;
}

function getProjectOfferingsData($conn, $startDate, $endDate) {
    $data = [];
    
    // Get project offerings list
    $sql = "SELECT * FROM project_offerings WHERE date BETWEEN '$startDate' AND '$endDate' ORDER BY date DESC, service_time DESC LIMIT 50";
    $result = $conn->query($sql);
    
    $data['project_offerings'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['project_offerings'][] = $row;
    }
    
    // Get summary stats with explicit zero defaults
    $sql = "SELECT 
            COALESCE(SUM(amount_collected), 0) as total_amount,
            COUNT(*) as total_count,
            COALESCE(AVG(amount_collected), 0) as avg_amount,
            COUNT(DISTINCT project_name) as active_projects
            FROM project_offerings WHERE date BETWEEN '$startDate' AND '$endDate'";
    $summary = $conn->query($sql)->fetch_assoc();
    
    // Ensure numeric values
    $data['summary'] = [
        'total_amount' => floatval($summary['total_amount']),
        'total_count' => intval($summary['total_count']),
        'avg_amount' => floatval($summary['avg_amount']),
        'active_projects' => intval($summary['active_projects'])
    ];
    
    return $data;
}

function getTithesData($conn, $startDate, $endDate) {
    $data = [];
    
    // Get tithes list with member info
    $sql = "SELECT t.*, 
            CONCAT(m.first_name, ' ', m.last_name) as member_name,
            m.email as member_email,
            m.photo_path as member_photo
            FROM tithes t
            LEFT JOIN members m ON t.member_id = m.member_id
            WHERE t.date BETWEEN '$startDate' AND '$endDate'
            ORDER BY t.date DESC LIMIT 100";
    $result = $conn->query($sql);
    
    $data['tithes'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['tithes'][] = $row;
    }
    
    // Get summary stats
    $sql = "SELECT 
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(*) as total_count,
            COUNT(DISTINCT member_id) as unique_members,
            COALESCE(AVG(amount), 0) as avg_amount
            FROM tithes WHERE date BETWEEN '$startDate' AND '$endDate'";
    $data['summary'] = $conn->query($sql)->fetch_assoc();
    
    return $data;
}

function getWelfareData($conn, $startDate, $endDate) {
    $data = [];
    
    // Get welfare list with member info
    $sql = "SELECT w.*, 
            CONCAT(m.first_name, ' ', m.last_name) as member_name,
            m.email as member_email,
            m.photo_path as member_photo
            FROM welfare_contributions w
            LEFT JOIN members m ON w.member_id = m.member_id
            WHERE w.date BETWEEN '$startDate' AND '$endDate'
            ORDER BY w.date DESC LIMIT 100";
    $result = $conn->query($sql);
    
    $data['welfare'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['welfare'][] = $row;
    }
    
    // Get summary stats
    $sql = "SELECT 
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(*) as total_count,
            COUNT(DISTINCT member_id) as unique_members,
            COALESCE(AVG(amount), 0) as avg_amount
            FROM welfare_contributions WHERE date BETWEEN '$startDate' AND '$endDate'";
    $data['summary'] = $conn->query($sql)->fetch_assoc();
    
    return $data;
}

function getExpensesData($conn, $startDate, $endDate) {
    $data = [];
    
    // Get expenses list
    $sql = "SELECT * FROM expenses WHERE date BETWEEN '$startDate' AND '$endDate' ORDER BY date DESC LIMIT 100";
    $result = $conn->query($sql);
    
    $data['expenses'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['expenses'][] = $row;
    }
    
    // Get summary stats
    $sql = "SELECT 
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
            FROM expenses WHERE date BETWEEN '$startDate' AND '$endDate'";
    $data['summary'] = $conn->query($sql)->fetch_assoc();
    
    // Get category breakdown
    $sql = "SELECT category, COALESCE(SUM(amount), 0) as total 
            FROM expenses 
            WHERE date BETWEEN '$startDate' AND '$endDate' AND status = 'approved'
            GROUP BY category 
            ORDER BY total DESC";
    $result = $conn->query($sql);
    
    $data['categories'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['categories'][] = $row;
    }
    
    return $data;
}

function getRecentTransactions($conn) {
    $transactions = [];
    
    // Get recent from all tables
    // Offerings
    $sql = "SELECT transaction_id, date, 'Offering' as type, service_type as description, 'income' as category, 
            amount_collected as amount, 'General' as member
            FROM offerings ORDER BY date DESC LIMIT 5";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Tithes
    $sql = "SELECT t.transaction_id, t.date, 'Tithe' as type, 'Tithe Payment' as description, 'income' as category,
            t.amount, COALESCE(CONCAT(m.first_name, ' ', m.last_name), 'Unknown') as member
            FROM tithes t
            LEFT JOIN members m ON t.member_id = m.member_id
            ORDER BY t.date DESC LIMIT 5";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Project Offerings
    $sql = "SELECT transaction_id, date, 'Project Offering' as type, project_name as description, 'income' as category,
            amount_collected as amount, 'General' as member
            FROM project_offerings ORDER BY date DESC LIMIT 5";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Welfare
    $sql = "SELECT w.transaction_id, w.date, 'Welfare' as type, 'Welfare Contribution' as description, 'income' as category,
            w.amount, COALESCE(CONCAT(m.first_name, ' ', m.last_name), 'Unknown') as member
            FROM welfare_contributions w
            LEFT JOIN members m ON w.member_id = m.member_id
            ORDER BY w.date DESC LIMIT 5";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Expenses
    $sql = "SELECT transaction_id, date, 'Expense' as type, description, 'expense' as category,
            amount, vendor_payee as member
            FROM expenses WHERE status = 'approved' ORDER BY date DESC LIMIT 5";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    // Sort by date
    usort($transactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Return only top 10
    return array_slice($transactions, 0, 10);
}

function getOfferingDetails($conn, $transactionId) {
    // Escape the transaction ID to prevent SQL injection
    $transactionId = $conn->real_escape_string($transactionId);
    
    $sql = "SELECT * FROM offerings WHERE transaction_id = '$transactionId' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        throw new Exception('Offering not found');
    }
}

function getProjectOfferingDetails($conn, $transactionId) {
    // Escape the transaction ID to prevent SQL injection
    $transactionId = $conn->real_escape_string($transactionId);
    
    $sql = "SELECT * FROM project_offerings WHERE transaction_id = '$transactionId' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        throw new Exception('Project offering not found');
    }
}

function getTitheDetails($conn, $transactionId) {
    // Escape the transaction ID to prevent SQL injection
    $transactionId = $conn->real_escape_string($transactionId);

    $sql = "SELECT t.*,
            CONCAT(m.first_name, ' ', m.last_name) as member_name,
            m.email as member_email,
            m.phone as member_phone,
            m.address as member_address,
            m.photo_path as member_photo
            FROM tithes t
            LEFT JOIN members m ON t.member_id = m.member_id
            WHERE t.transaction_id = '$transactionId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        throw new Exception('Tithe record not found');
    }
}

function getWelfareDetails($conn, $transactionId) {
    // Escape the transaction ID to prevent SQL injection
    $transactionId = $conn->real_escape_string($transactionId);

    $sql = "SELECT w.*,
            CONCAT(m.first_name, ' ', m.last_name) as member_name,
            m.email as member_email,
            m.phone as member_phone,
            m.address as member_address,
            m.photo_path as member_photo
            FROM welfare_contributions w
            LEFT JOIN members m ON w.member_id = m.member_id
            WHERE w.transaction_id = '$transactionId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        throw new Exception('Welfare record not found');
    }
}

function getExpenseDetails($conn, $transactionId) {
    // Escape the transaction ID to prevent SQL injection
    $transactionId = $conn->real_escape_string($transactionId);

    $sql = "SELECT * FROM expenses WHERE transaction_id = '$transactionId' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        throw new Exception('Expense record not found');
    }
}

function calculatePercentageChange($oldValue, $newValue) {
    if ($oldValue == 0) {
        return $newValue > 0 ? 100 : 0;
    }
    return round((($newValue - $oldValue) / $oldValue) * 100, 1);
}

function getFinancialTrendsData($conn, $months = 6) {
    $data = [];
    $currentDate = new DateTime();

    // Generate data for the last N months (default 6 months)
    for ($i = $months - 1; $i >= 0; $i--) {
        $monthDate = clone $currentDate;
        $monthDate->modify("-$i months");
        $monthStart = $monthDate->format('Y-m-01');
        $monthEnd = $monthDate->format('Y-m-t');
        $monthName = $monthDate->format('M'); // Jan, Feb, Mar, etc.

        // Calculate total income for the month (Offerings + Project Offerings + Tithes + Welfare)
        $incomeSql = "
            SELECT
                (SELECT COALESCE(SUM(amount_collected), 0) FROM offerings WHERE date BETWEEN '$monthStart' AND '$monthEnd') +
                (SELECT COALESCE(SUM(amount_collected), 0) FROM project_offerings WHERE date BETWEEN '$monthStart' AND '$monthEnd') +
                (SELECT COALESCE(SUM(amount), 0) FROM tithes WHERE date BETWEEN '$monthStart' AND '$monthEnd') +
                (SELECT COALESCE(SUM(amount), 0) FROM welfare WHERE date BETWEEN '$monthStart' AND '$monthEnd')
                AS total_income
        ";

        $incomeResult = $conn->query($incomeSql);
        $income = 0;
        if ($incomeResult && $incomeResult->num_rows > 0) {
            $row = $incomeResult->fetch_assoc();
            $income = floatval($row['total_income']);
        }

        // Calculate total expenses for the month
        $expensesSql = "SELECT COALESCE(SUM(amount), 0) as total_expenses FROM expenses WHERE date BETWEEN '$monthStart' AND '$monthEnd'";
        $expensesResult = $conn->query($expensesSql);
        $expenses = 0;
        if ($expensesResult && $expensesResult->num_rows > 0) {
            $row = $expensesResult->fetch_assoc();
            $expenses = floatval($row['total_expenses']);
        }

        $data[] = [
            'month' => $monthName,
            'income' => $income,
            'expenses' => $expenses
        ];
    }

    return $data;
}

function getBudgetData($conn, $startDate, $endDate) {
    $data = [];

    // Get total budget from budget_settings table
    $sql = "SELECT COALESCE(SUM(amount), 0) as total_budget FROM budget_settings WHERE YEAR(created_at) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    $data['summary'] = [
        'total_budget' => floatval($result->fetch_assoc()['total_budget'])
    ];

    // Get budget items breakdown
    $sql = "SELECT category, amount, description FROM budget_settings WHERE YEAR(created_at) = YEAR(CURDATE()) ORDER BY amount DESC";
    $result = $conn->query($sql);

    $data['budget_items'] = [];
    while ($row = $result->fetch_assoc()) {
        $data['budget_items'][] = $row;
    }

    return $data;
}

function getDonorRetentionData($conn, $months = 6) {
    $data = [];
    $currentDate = new DateTime();

    // Generate donor retention data for the last N months
    for ($i = $months - 1; $i >= 0; $i--) {
        $monthDate = clone $currentDate;
        $monthDate->modify("-$i months");
        $monthStart = $monthDate->format('Y-m-01');
        $monthEnd = $monthDate->format('Y-m-t');
        $monthName = $monthDate->format('M');

        // Get previous month dates for comparison
        $prevMonthDate = clone $monthDate;
        $prevMonthDate->modify('-1 month');
        $prevMonthStart = $prevMonthDate->format('Y-m-01');
        $prevMonthEnd = $prevMonthDate->format('Y-m-t');

        // New donors: members who gave this month but not in previous month
        $newDonorsSql = "
            SELECT COUNT(DISTINCT member_id) as count
            FROM tithes
            WHERE date BETWEEN '$monthStart' AND '$monthEnd'
            AND member_id NOT IN (
                SELECT DISTINCT member_id FROM tithes WHERE date BETWEEN '$prevMonthStart' AND '$prevMonthEnd'
            )
        ";
        $newDonorsResult = $conn->query($newDonorsSql);
        $newDonors = $newDonorsResult ? intval($newDonorsResult->fetch_assoc()['count']) : 0;

        // Returning donors: members who gave this month and also gave in previous month
        $returningDonorsSql = "
            SELECT COUNT(DISTINCT member_id) as count
            FROM tithes
            WHERE date BETWEEN '$monthStart' AND '$monthEnd'
            AND member_id IN (
                SELECT DISTINCT member_id FROM tithes WHERE date BETWEEN '$prevMonthStart' AND '$prevMonthEnd'
            )
        ";
        $returningDonorsResult = $conn->query($returningDonorsSql);
        $returningDonors = $returningDonorsResult ? intval($returningDonorsResult->fetch_assoc()['count']) : 0;

        // Lapsed donors: members who gave in previous month but not this month
        $lapsedDonorsSql = "
            SELECT COUNT(DISTINCT member_id) as count
            FROM tithes
            WHERE date BETWEEN '$prevMonthStart' AND '$prevMonthEnd'
            AND member_id NOT IN (
                SELECT DISTINCT member_id FROM tithes WHERE date BETWEEN '$monthStart' AND '$monthEnd'
            )
        ";
        $lapsedDonorsResult = $conn->query($lapsedDonorsSql);
        $lapsedDonors = $lapsedDonorsResult ? intval($lapsedDonorsResult->fetch_assoc()['count']) : 0;

        $data[] = [
            'month' => $monthName,
            'new_donors' => $newDonors,
            'returning_donors' => $returningDonors,
            'lapsed_donors' => $lapsedDonors
        ];
    }

    return $data;
}
?>
