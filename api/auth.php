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
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    jsonResponse([
        'success' => true,
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'user' => ['id' => $user['id'], 'username' => $user['username']]
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
            'user' => ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username']]
        ]);
    } else {
        jsonResponse(['success' => true, 'logged_in' => false]);
    }
}
