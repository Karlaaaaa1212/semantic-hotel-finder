<?php
session_start();
require_once __DIR__ . '/../db/config.php';

$method   = $_SERVER['REQUEST_METHOD'];
$hotelId  = (int)($_GET['hotel_id'] ?? $_POST['hotel_id'] ?? 0);

if ($method === 'GET') {
    if (!$hotelId) jsonResponse(['status' => 'error', 'message' => '缺少 hotel_id'], 400);
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        'SELECT r.review_id, r.rating, r.content, r.created_at, u.username
         FROM Reviews r JOIN Users u ON r.user_id = u.user_id
         WHERE r.hotel_id = ? ORDER BY r.created_at DESC'
    );
    $stmt->execute([$hotelId]);
    jsonResponse(['status' => 'ok', 'data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['status' => 'error', 'message' => '請先登入'], 401);
    }
    $action  = $_POST['action'] ?? 'add';
    $pdo     = getPDO();

    if ($action === 'delete') {
        $reviewId = (int)($_POST['review_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM Reviews WHERE review_id = ? AND user_id = ?');
        $stmt->execute([$reviewId, $_SESSION['user_id']]);
        jsonResponse(['status' => 'ok']);
    }

    // add review
    $rating  = (int)($_POST['rating']  ?? 0);
    $content = trim($_POST['content']  ?? '');
    if (!$hotelId || $rating < 1 || $rating > 5 || !$content) {
        jsonResponse(['status' => 'error', 'message' => '請填寫評分與評論內容'], 400);
    }
    $pdo->prepare('INSERT INTO Reviews (user_id, hotel_id, rating, content) VALUES (?, ?, ?, ?)')
        ->execute([$_SESSION['user_id'], $hotelId, $rating, $content]);

    jsonResponse([
        'status'     => 'ok',
        'username'   => e($_SESSION['username']),
        'rating'     => $rating,
        'content'    => e($content),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

jsonResponse(['status' => 'error', 'message' => '不支援的請求方式'], 405);
