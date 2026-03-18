<?php
/**
 * Dashboard API
 * GET - Get dashboard statistics
 * Supports branch scoping: branch users see only their branch data
 */
require_once __DIR__ . '/../config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$role = $_SESSION['role'] ?? 'admin';
$userBranchId = $_SESSION['branch_id'] ?? null;
$isBranch = ($role === 'branch' && $userBranchId);

// Build branch filter
$branchWhere = '';
$branchParams = [];
if ($isBranch) {
    $branchWhere = ' WHERE v.branch_id = ?';
    $branchParams = [$userBranchId];
}

// Total vehicles
$totalSql = "SELECT COUNT(*) FROM vehicles v" . ($isBranch ? " WHERE v.branch_id = ?" : "");
$totalStmt = $db->prepare($totalSql);
$totalStmt->execute($branchParams);
$total = (int)$totalStmt->fetchColumn();

// By status
$statusSql = "SELECT v.status, COUNT(*) as count FROM vehicles v" . ($isBranch ? " WHERE v.branch_id = ?" : "") . " GROUP BY v.status";
$statusStmt = $db->prepare($statusSql);
$statusStmt->execute($branchParams);
$byStatus = [];
while ($row = $statusStmt->fetch()) {
    $byStatus[$row['status']] = (int)$row['count'];
}

// By branch
if ($isBranch) {
    $branchStmt = $db->prepare("SELECT b.id, b.name, b.color, COUNT(v.id) as count,
                           COALESCE(SUM(CASE WHEN v.status = 'available' THEN 1 ELSE 0 END), 0) as available,
                           COALESCE(SUM(CASE WHEN v.status = 'reserved' THEN 1 ELSE 0 END), 0) as reserved,
                           COALESCE(SUM(CASE WHEN v.status = 'sold' THEN 1 ELSE 0 END), 0) as sold,
                           COALESCE(SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END), 0) as maintenance
                           FROM branches b
                           LEFT JOIN vehicles v ON v.branch_id = b.id
                           WHERE b.id = ?
                           GROUP BY b.id, b.name, b.color");
    $branchStmt->execute([$userBranchId]);
} else {
    $branchStmt = $db->query("SELECT b.id, b.name, b.color, COUNT(v.id) as count,
                           COALESCE(SUM(CASE WHEN v.status = 'available' THEN 1 ELSE 0 END), 0) as available,
                           COALESCE(SUM(CASE WHEN v.status = 'reserved' THEN 1 ELSE 0 END), 0) as reserved,
                           COALESCE(SUM(CASE WHEN v.status = 'sold' THEN 1 ELSE 0 END), 0) as sold,
                           COALESCE(SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END), 0) as maintenance
                           FROM branches b
                           LEFT JOIN vehicles v ON v.branch_id = b.id
                           GROUP BY b.id, b.name, b.color
                           ORDER BY b.sort_order ASC");
}
$byBranch = $branchStmt->fetchAll();

// No branch assigned
$noBranch = 0;
if (!$isBranch) {
    $noBranchStmt = $db->query("SELECT COUNT(*) FROM vehicles WHERE branch_id IS NULL");
    $noBranch = (int)$noBranchStmt->fetchColumn();
}

// Financial: stock cost only (simplified)
$valueSql = "SELECT 
    COALESCE(SUM(CASE WHEN status != 'sold' THEN cost_price ELSE 0 END), 0) as stock_cost,
    COALESCE(SUM(CASE WHEN status != 'sold' THEN selling_price ELSE 0 END), 0) as stock_selling
    FROM vehicles" . ($isBranch ? " WHERE branch_id = ?" : "");
$valueStmt = $db->prepare($valueSql);
$valueStmt->execute($branchParams);
$values = $valueStmt->fetch();

// Profit summary: sold vehicles with sold_date
// Daily profit (last 30 days)
$dailySql = "SELECT 
    DATE_FORMAT(COALESCE(sold_date, updated_at), '%Y-%m-%d') as day,
    COUNT(*) as count,
    COALESCE(SUM(COALESCE(sold_price, selling_price)), 0) as revenue,
    COALESCE(SUM(cost_price), 0) as cost,
    COALESCE(SUM(COALESCE(sold_price, selling_price) - cost_price), 0) as profit
    FROM vehicles
    WHERE status = 'sold'" . ($isBranch ? " AND branch_id = ?" : "") . "
    GROUP BY day
    ORDER BY day DESC
    LIMIT 30";
$dailyStmt = $db->prepare($dailySql);
$dailyStmt->execute($branchParams);
$dailyProfit = $dailyStmt->fetchAll();

// Monthly profit (last 12 months)
$profitSql = "SELECT 
    DATE_FORMAT(COALESCE(sold_date, updated_at), '%Y-%m') as month,
    COUNT(*) as count,
    COALESCE(SUM(COALESCE(sold_price, selling_price)), 0) as revenue,
    COALESCE(SUM(cost_price), 0) as cost,
    COALESCE(SUM(COALESCE(sold_price, selling_price) - cost_price), 0) as profit
    FROM vehicles
    WHERE status = 'sold'" . ($isBranch ? " AND branch_id = ?" : "") . "
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12";
$profitStmt = $db->prepare($profitSql);
$profitStmt->execute($branchParams);
$monthlyProfit = $profitStmt->fetchAll();

// Yearly profit 
$yearProfitSql = "SELECT 
    DATE_FORMAT(COALESCE(sold_date, updated_at), '%Y') as year,
    COUNT(*) as count,
    COALESCE(SUM(COALESCE(sold_price, selling_price)), 0) as revenue,
    COALESCE(SUM(cost_price), 0) as cost,
    COALESCE(SUM(COALESCE(sold_price, selling_price) - cost_price), 0) as profit
    FROM vehicles
    WHERE status = 'sold'" . ($isBranch ? " AND branch_id = ?" : "") . "
    GROUP BY year
    ORDER BY year DESC
    LIMIT 5";
$yearProfitStmt = $db->prepare($yearProfitSql);
$yearProfitStmt->execute($branchParams);
$yearlyProfit = $yearProfitStmt->fetchAll();

// Total profit all time
$totalProfitSql = "SELECT 
    COUNT(*) as count,
    COALESCE(SUM(COALESCE(sold_price, selling_price)), 0) as revenue,
    COALESCE(SUM(cost_price), 0) as cost,
    COALESCE(SUM(COALESCE(sold_price, selling_price) - cost_price), 0) as profit
    FROM vehicles
    WHERE status = 'sold'" . ($isBranch ? " AND branch_id = ?" : "");
$totalProfitStmt = $db->prepare($totalProfitSql);
$totalProfitStmt->execute($branchParams);
$totalProfit = $totalProfitStmt->fetch();

// Recent vehicles (last 6 added)
$recentSql = "SELECT v.*, b.name as branch_name, b.color as branch_color,
                             (SELECT filename FROM vehicle_images WHERE vehicle_id = v.id ORDER BY sort_order ASC LIMIT 1) as thumbnail
                             FROM vehicles v
                             LEFT JOIN branches b ON v.branch_id = b.id"
                             . ($isBranch ? " WHERE v.branch_id = ?" : "")
                             . " ORDER BY v.created_at DESC LIMIT 6";
$recentStmt = $db->prepare($recentSql);
$recentStmt->execute($branchParams);
$recentVehicles = $recentStmt->fetchAll();

jsonResponse([
    'success' => true,
    'stats' => [
        'total' => $total,
        'available' => (int)($byStatus['available'] ?? 0),
        'reserved' => (int)($byStatus['reserved'] ?? 0),
        'sold' => (int)($byStatus['sold'] ?? 0),
        'maintenance' => (int)($byStatus['maintenance'] ?? 0),
        'no_branch' => $noBranch,
    ],
    'values' => $values,
    'profit' => [
        'total' => $totalProfit,
        'daily' => $dailyProfit,
        'monthly' => $monthlyProfit,
        'yearly' => $yearlyProfit
    ],
    'by_branch' => $byBranch,
    'recent_vehicles' => $recentVehicles
]);
