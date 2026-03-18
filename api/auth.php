<?php
/**
 * Authentication API
 * POST   - Login
 * DELETE - Logout
 * GET    - Check session
 */
require_once __DIR__ . '/../config.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        login();
        break;
    case 'DELETE':
        logout();
        break;
    case 'GET':
        checkAuth();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function login() {
    $input = getJsonInput();
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'], 400);
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, b.name as branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE u.username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['branch_id'] = $user['branch_id'];
    $_SESSION['display_name'] = $user['display_name'] ?: $user['username'];
    $_SESSION['branch_name'] = $user['branch_name'] ?? null;
    
    jsonResponse([
        'success' => true,
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'branch_id' => $user['branch_id'],
            'display_name' => $user['display_name'] ?: $user['username'],
            'branch_name' => $user['branch_name'] ?? null
        ]
    ]);
}

function logout() {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'ออกจากระบบแล้ว']);
}

function checkAuth() {
    if (isset($_SESSION['user_id'])) {
        jsonResponse([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'] ?? 'admin',
                'branch_id' => $_SESSION['branch_id'] ?? null,
                'display_name' => $_SESSION['display_name'] ?? $_SESSION['username'],
                'branch_name' => $_SESSION['branch_name'] ?? null
            ]
        ]);
    } else {
        jsonResponse(['success' => true, 'logged_in' => false]);
    }
}
