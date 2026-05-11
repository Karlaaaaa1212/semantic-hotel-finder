<?php
session_start();
require_once __DIR__ . '/../db/config.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit;
}

$pdo     = getPDO();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rebuild'])) {
    $hotelId = (int)($_POST['hotel_id'] ?? 0);
    $sql     = $hotelId
        ? 'SELECT hotel_id, description, facilities FROM Hotels WHERE hotel_id = ?'
        : 'SELECT hotel_id, description, facilities FROM Hotels';
    $stmt = $hotelId ? $pdo->prepare($sql) : $pdo->query($sql);
    if ($hotelId) $stmt->execute([$hotelId]);
    $hotels = $stmt->fetchAll();

    $count = 0;
    foreach ($hotels as $h) {
        $fac    = implode(' ', json_decode($h['facilities'] ?? '[]', true));
        $text   = $h['description'] . ' ' . $fac;
        $vector = callGeminiEmbedding($text);
        if (!empty($vector)) {
            $pdo->prepare('UPDATE Hotels SET embedding = ? WHERE hotel_id = ?')
                ->execute([json_encode($vector), $h['hotel_id']]);
            $count++;
        }
        usleep(250000); // 0.25s delay to avoid rate limiting (1500 req/day)
    }
    $message = "成功建立 {$count} 筆向量。";
}

$hotels = $pdo->query('SELECT hotel_id, name, region, CASE WHEN embedding IS NOT NULL AND embedding != "" THEN 1 ELSE 0 END as has_vec FROM Hotels ORDER BY hotel_id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>重建向量 — 管理後台</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h2 class="mb-4"><i class="fa-solid fa-brain me-2 text-primary"></i>飯店向量管理</h2>
  <?php if ($message): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">重建全部向量</h5>
      <p class="text-muted">將呼叫 Gemini text-embedding-004，依當前飯店描述 + 設施重新建立所有向量。</p>
      <form method="post">
        <input type="hidden" name="rebuild" value="1">
        <button class="btn btn-primary" onclick="return confirm('確定要重建所有飯店向量？這將消耗 API 額度。')">
          <i class="fa-solid fa-rotate me-1"></i>重建全部
        </button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">飯店向量狀態</div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>ID</th><th>飯店名稱</th><th>地區</th><th>向量狀態</th><th>操作</th></tr>
        </thead>
        <tbody>
          <?php foreach ($hotels as $h): ?>
          <tr>
            <td><?= $h['hotel_id'] ?></td>
            <td><?= e($h['name']) ?></td>
            <td><?= e($h['region']) ?></td>
            <td>
              <?php if ($h['has_vec']): ?>
                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>已建立</span>
              <?php else: ?>
                <span class="badge bg-warning text-dark"><i class="fa-solid fa-xmark me-1"></i>未建立</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" class="d-inline">
                <input type="hidden" name="rebuild" value="1">
                <input type="hidden" name="hotel_id" value="<?= $h['hotel_id'] ?>">
                <button class="btn btn-sm btn-outline-primary">單筆重建</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fa-solid fa-arrow-left me-1"></i>返回後台</a>
</div>
</body>
</html>
