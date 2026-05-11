<?php
set_time_limit(120);
session_start();
require_once __DIR__ . '/../db/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST only'], 405);
}

$query = trim($_POST['query'] ?? '');
if (!$query) {
    jsonResponse(['status' => 'error', 'message' => '請輸入搜尋內容']);
}

// Log search
$pdo    = getPDO();
$userId = $_SESSION['user_id'] ?? null;
$pdo->prepare('INSERT INTO SearchLogs (user_id, query_text) VALUES (?, ?)')
    ->execute([$userId, $query]);

// Step 1: Get query vector
$queryVec = callGeminiEmbedding($query);
if (empty($queryVec)) {
    jsonResponse(['status' => 'error', 'message' => 'Embedding API 連線失敗，請確認 API Key']);
}

// Step 2: Fetch all hotels with embeddings
$stmt   = $pdo->query('SELECT * FROM Hotels WHERE embedding IS NOT NULL AND embedding != ""');
$hotels = $stmt->fetchAll();

if (empty($hotels)) {
    jsonResponse(['status' => 'error', 'message' => '尚未建立飯店向量，請管理員執行「重建向量」']);
}

// Step 3: Cosine similarity → sort → Top-5
$scores = [];
$dimMismatch = 0;
foreach ($hotels as $h) {
    $hVec = json_decode($h['embedding'], true);
    if (!is_array($hVec)) continue;
    if (count($hVec) !== count($queryVec)) { $dimMismatch++; continue; }
    $scores[] = ['hotel' => $h, 'score' => cosineSim($queryVec, $hVec)];
}

if (empty($scores)) {
    $msg = $dimMismatch > 0
        ? "向量維度不符（飯店:{$dimMismatch} 筆），請在後台重新執行「重建向量」"
        : '所有飯店向量解析失敗，請在後台重新執行「重建向量」';
    jsonResponse(['status' => 'error', 'message' => $msg]);
}

usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
$top5 = array_slice($scores, 0, 5);

// Step 4: One Gemini Flash call for all 5 summaries
$batchItems = [];
foreach ($top5 as $item) {
    $h            = $item['hotel'];
    $batchItems[] = [
        'query' => $query,
        'hotel' => $h,
        'fac'   => implode('、', json_decode($h['facilities'] ?? '[]', true)),
    ];
}
$summaries = callGeminiFlashBatch($batchItems);

$results = [];
foreach ($top5 as $i => $item) {
    $h         = $item['hotel'];
    $results[] = [
        'hotel_id'       => (int)$h['hotel_id'],
        'name'           => $h['name'],
        'region'         => $h['region'],
        'price_per_night'=> (int)$h['price_per_night'],
        'stars'          => (int)$h['stars'],
        'description'    => $h['description'],
        'facilities'     => json_decode($h['facilities'] ?? '[]', true),
        'img_url'        => $h['img_url'],
        'score'          => round($item['score'], 4),
        'summary'        => $summaries[$i],
    ];
}

jsonResponse(['status' => 'ok', 'data' => $results]);
