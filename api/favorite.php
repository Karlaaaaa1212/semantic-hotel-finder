<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['status' => 'error', 'message' => '請先登入'], 401);
}

$hotelId = (int)($_POST['hotel_id'] ?? 0);
if (!$hotelId) jsonResponse(['status' => 'error', 'message' => '缺少 hotel_id'], 400);

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$stmt   = $pdo->prepare('SELECT fav_id FROM Favorites WHERE user_id = ? AND hotel_id = ?');
$stmt->execute([$userId, $hotelId]);

if ($stmt->fetch()) {
    $pdo->prepare('DELETE FROM Favorites WHERE user_id = ? AND hotel_id = ?')
        ->execute([$userId, $hotelId]);
    jsonResponse(['status' => 'ok', 'favorited' => false]);
} else {
    $pdo->prepare('INSERT INTO Favorites (user_id, hotel_id) VALUES (?, ?)')
        ->execute([$userId, $hotelId]);
    jsonResponse(['status' => 'ok', 'favorited' => true]);
}
