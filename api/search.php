<?php
require_once __DIR__ . '/../db/config.php';

$region    = trim($_GET['region']    ?? '');
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 99999;
$stars     = (int)($_GET['stars']     ?? 0);
$keyword   = trim($_GET['keyword']   ?? '');

$pdo    = getPDO();
$where  = ['1=1'];
$params = [];

if ($region) {
    $where[]  = 'region = ?';
    $params[] = $region;
}
if ($min_price > 0) {
    $where[]  = 'price_per_night >= ?';
    $params[] = $min_price;
}
if ($max_price < 99999) {
    $where[]  = 'price_per_night <= ?';
    $params[] = $max_price;
}
if ($stars > 0) {
    $where[]  = 'stars >= ?';
    $params[] = $stars;
}
if ($keyword) {
    $where[]  = '(name LIKE ? OR description LIKE ? OR facilities LIKE ?)';
    $like     = '%' . $keyword . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

$sql  = 'SELECT hotel_id, name, region, price_per_night, stars, description, facilities, img_url '
      . 'FROM Hotels WHERE ' . implode(' AND ', $where)
      . ' ORDER BY stars DESC, price_per_night ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

// Decode facilities JSON for each hotel
foreach ($hotels as &$h) {
    $h['facilities'] = json_decode($h['facilities'] ?? '[]', true);
}
unset($h);

jsonResponse(['status' => 'ok', 'data' => $hotels]);
