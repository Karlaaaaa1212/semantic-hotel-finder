<?php
session_start();
require_once __DIR__ . '/../db/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        if (!$username || !$email || !$password) {
            jsonResponse(['status' => 'error', 'message' => '請填寫所有欄位']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['status' => 'error', 'message' => 'Email 格式不正確']);
        }
        if (strlen($password) < 8) {
            jsonResponse(['status' => 'error', 'message' => '密碼至少需要 8 個字元']);
        }
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['status' => 'error', 'message' => 'Email 已被使用']);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)')
            ->execute([$username, $email, $hash]);
        jsonResponse(['status' => 'ok', 'message' => '註冊成功，請登入']);

    case 'login':
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        if (!$email || !$password) {
            jsonResponse(['status' => 'error', 'message' => '請填寫帳號與密碼']);
        }
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM Users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['status' => 'error', 'message' => '帳號或密碼錯誤']);
        }
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        jsonResponse(['status' => 'ok', 'message' => '登入成功', 'role' => $user['role']]);

    case 'logout':
        session_destroy();
        jsonResponse(['status' => 'ok', 'message' => '已登出']);

    default:
        jsonResponse(['status' => 'error', 'message' => '未知操作'], 400);
}
