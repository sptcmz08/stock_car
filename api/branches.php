<?php
/**
 * Branches API
 * GET    - List all branches
 * POST   - Create branch
 * PUT    - Update branch
 * DELETE - Delete branch
 */
require_once __DIR__ . '/../config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getBranches();
        break;
    case 'POST':
        createBranch();
        break;
    case 'PUT':
        updateBranch();
        break;
    case 'DELETE':
        deleteBranch();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function getBranches() {
    $db = getDB();
    
    $stmt = $db->query("SELECT b.*, 
                        (SELECT COUNT(*) FROM vehicles WHERE branch_id = b.id) as vehicle_count,
                        (SELECT COUNT(*) FROM vehicles WHERE branch_id = b.id AND status = 'available') as available_count,
                        (SELECT COUNT(*) FROM vehicles WHERE branch_id = b.id AND status = 'sold') as sold_count
                        FROM branches b 
                        ORDER BY b.sort_order ASC, b.name ASC");
    $branches = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'branches' => $branches
    ]);
}

function createBranch() {
    $db = getDB();
    $input = getJsonInput();
    
    $name = sanitize($input['name'] ?? '');
    $color = $input['color'] ?? '#f97316';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#f97316';
    
    if (empty($name)) {
        jsonResponse(['error' => 'กรุณากรอกชื่อสาขา'], 400);
    }
    
    // Get next sort order
    $maxSort = $db->query("SELECT COALESCE(MAX(sort_order), 0) FROM branches")->fetchColumn();
    
    $stmt = $db->prepare("INSERT INTO branches (name, color, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$name, $color, $maxSort + 1]);
    
    jsonResponse([
        'success' => true,
        'message' => 'เพิ่มสาขาเรียบร้อยแล้ว',
        'branch_id' => $db->lastInsertId()
    ], 201);
}

function updateBranch() {
    $db = getDB();
    $input = getJsonInput();
    
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid branch ID'], 400);
    }
    
    $fields = [];
    $params = [];
    
    if (isset($input['name'])) {
        $fields[] = "name = ?";
        $params[] = sanitize($input['name']);
    }
    if (isset($input['color'])) {
        $c = $input['color'];
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
            $fields[] = "color = ?";
            $params[] = $c;
        }
    }
    if (isset($input['sort_order'])) {
        $fields[] = "sort_order = ?";
        $params[] = (int)$input['sort_order'];
    }
    
    if (empty($fields)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }
    
    $params[] = $id;
    $stmt = $db->prepare("UPDATE branches SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);
    
    jsonResponse([
        'success' => true,
        'message' => 'อัพเดทสาขาเรียบร้อยแล้ว'
    ]);
}

function deleteBranch() {
    $db = getDB();
    $input = getJsonInput();
    
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid branch ID'], 400);
    }
    
    // Set vehicles in this branch to null
    $db->prepare("UPDATE vehicles SET branch_id = NULL WHERE branch_id = ?")->execute([$id]);
    
    // Delete branch
    $db->prepare("DELETE FROM branches WHERE id = ?")->execute([$id]);
    
    jsonResponse([
        'success' => true,
        'message' => 'ลบสาขาเรียบร้อยแล้ว'
    ]);
}
