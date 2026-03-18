<?php
/**
 * Users API (Admin Only)
 * GET    - List all users
 * POST   - Create user
 * PUT    - Update user
 * DELETE - Delete user
 */
require_once __DIR__ . '/../config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

// Admin-only check
if (($_SESSION['role'] ?? '') !== 'admin') {
    jsonResponse(['error' => 'Forbidden: Admin only'], 403);
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getUsers();
        break;
    case 'POST':
        createUser();
        break;
    case 'PUT':
        updateUser();
        break;
    case 'DELETE':
        deleteUser();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function getUsers() {
    $db = getDB();
    $stmt = $db->query("SELECT u.id, u.username, u.role, u.branch_id, u.display_name, u.created_at, b.name as branch_name, b.color as branch_color
                         FROM users u
                         LEFT JOIN branches b ON u.branch_id = b.id
                         ORDER BY u.role ASC, u.created_at ASC");
    $users = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'users' => $users
    ]);
}

function createUser() {
    $db = getDB();
    $input = getJsonInput();
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'branch';
    $branch_id = !empty($input['branch_id']) ? (int)$input['branch_id'] : null;
    $display_name = sanitize($input['display_name'] ?? '');
    
    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'], 400);
    }
    
    if (strlen($password) < 4) {
        jsonResponse(['error' => 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร'], 400);
    }
    
    if (!in_array($role, ['admin', 'branch'])) $role = 'branch';
    
    if ($role === 'branch' && empty($branch_id)) {
        jsonResponse(['error' => 'กรุณาเลือกสาขาสำหรับผู้ใช้สาขา'], 400);
    }
    
    // Check duplicate username
    $check = $db->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        jsonResponse(['error' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว'], 400);
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, role, branch_id, display_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $role, $role === 'admin' ? null : $branch_id, $display_name ?: $username]);
    
    jsonResponse([
        'success' => true,
        'message' => 'เพิ่มผู้ใช้เรียบร้อยแล้ว',
        'user_id' => $db->lastInsertId()
    ], 201);
}

function updateUser() {
    $db = getDB();
    $input = getJsonInput();
    
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid user ID'], 400);
    }
    
    $fields = [];
    $params = [];
    
    if (isset($input['display_name'])) {
        $fields[] = "display_name = ?";
        $params[] = sanitize($input['display_name']);
    }
    
    if (isset($input['role']) && in_array($input['role'], ['admin', 'branch'])) {
        $fields[] = "role = ?";
        $params[] = $input['role'];
    }
    
    if (isset($input['branch_id'])) {
        $fields[] = "branch_id = ?";
        $params[] = $input['branch_id'] === '' ? null : (int)$input['branch_id'];
    }
    
    if (!empty($input['password'])) {
        if (strlen($input['password']) < 4) {
            jsonResponse(['error' => 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร'], 400);
        }
        $fields[] = "password = ?";
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($fields)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }
    
    $params[] = $id;
    $stmt = $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);
    
    jsonResponse([
        'success' => true,
        'message' => 'อัพเดทผู้ใช้เรียบร้อยแล้ว'
    ]);
}

function deleteUser() {
    $db = getDB();
    $input = getJsonInput();
    
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid user ID'], 400);
    }
    
    // Don't allow deleting yourself
    if ($id == $_SESSION['user_id']) {
        jsonResponse(['error' => 'ไม่สามารถลบบัญชีตัวเองได้'], 400);
    }
    
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    
    jsonResponse([
        'success' => true,
        'message' => 'ลบผู้ใช้เรียบร้อยแล้ว'
    ]);
}
