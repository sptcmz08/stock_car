<?php
/**
 * Dashboard API
 * GET - Get dashboard statistics
 */
require_once __DIR__ . '/../config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// Total vehicles
$totalStmt = $db->query("SELECT COUNT(*) FROM vehicles");
$total = (int)$totalStmt->fetchColumn();

// By status
$statusStmt = $db->query("SELECT status, COUNT(*) as count FROM vehicles GROUP BY status");
$byStatus = [];
while ($row = $statusStmt->fetch()) {
    $byStatus[$row['status']] = (int)$row['count'];
}

// By branch
$branchStmt = $db->query("SELECT b.id, b.name, b.color, COUNT(v.id) as count,
                           COALESCE(SUM(CASE WHEN v.status = 'available' THEN 1 ELSE 0 END), 0) as available,
                           COALESCE(SUM(CASE WHEN v.status = 'reserved' THEN 1 ELSE 0 END), 0) as reserved,
                           COALESCE(SUM(CASE WHEN v.status = 'sold' THEN 1 ELSE 0 END), 0) as sold,
                           COALESCE(SUM(CASE WHEN v.status = 'maintenance' THEN 1 ELSE 0 END), 0) as maintenance
                           FROM branches b
                           LEFT JOIN vehicles v ON v.branch_id = b.id
                           GROUP BY b.id, b.name, b.color
                           ORDER BY b.sort_order ASC");
$byBranch = $branchStmt->fetchAll();

// No branch assigned
$noBranchStmt = $db->query("SELECT COUNT(*) FROM vehicles WHERE branch_id IS NULL");
$noBranch = (int)$noBranchStmt->fetchColumn();

// Total cost and selling values
$valueStmt = $db->query("SELECT 
    COALESCE(SUM(cost_price), 0) as total_cost,
    COALESCE(SUM(selling_price), 0) as total_selling,
    COALESCE(SUM(CASE WHEN status != 'sold' THEN cost_price ELSE 0 END), 0) as stock_cost,
    COALESCE(SUM(CASE WHEN status != 'sold' THEN selling_price ELSE 0 END), 0) as stock_selling
    FROM vehicles");
$values = $valueStmt->fetch();

// Recent vehicles (last 5 added)
$recentStmt = $db->prepare("SELECT v.*, b.name as branch_name, b.color as branch_color,
                             (SELECT filename FROM vehicle_images WHERE vehicle_id = v.id ORDER BY sort_order ASC LIMIT 1) as thumbnail
                             FROM vehicles v
                             LEFT JOIN branches b ON v.branch_id = b.id
                             ORDER BY v.created_at DESC LIMIT 5");
$recentStmt->execute();
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
    'by_branch' => $byBranch,
    'recent_vehicles' => $recentVehicles
]);
