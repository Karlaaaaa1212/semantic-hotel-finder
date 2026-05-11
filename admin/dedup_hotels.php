<?php
session_start();
require_once __DIR__ . '/../db/config.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit;
}

$pdo     = getPDO();
$message = '';
$deleted = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dedup'])) {
    $deleted = $pdo->exec(
        'DELETE FROM Hotels WHERE hotel_id NOT IN (
            SELECT keep_id FROM (
                SELECT MIN(hotel_id) AS keep_id FROM Hotels GROUP BY name
            ) AS t
        )'
    );
    $message = "已刪除 {$deleted} 筆重複飯店，保留各名稱中 hotel_id 最小的一筆。";
}

// Fetch all hotels whose name appears more than once, grouped
$dupRows = $pdo->query(
    'SELECT h.hotel_id, h.name, h.region, h.price_per_night, h.stars,
            m.min_id,
            IF(h.hotel_id = m.min_id, 1, 0) AS is_keeper
     FROM Hotels h
     JOIN (
         SELECT name, MIN(hotel_id) AS min_id
         FROM Hotels
         GROUP BY name
         HAVING COUNT(*) > 1
     ) m ON h.name = m.name
     ORDER BY h.name, h.hotel_id'
)->fetchAll();

// Group by name for display
$groups = [];
foreach ($dupRows as $r) {
    $groups[$r['name']][] = $r;
}

$dupCount = count(array_filter($dupRows, fn($r) => !$r['is_keeper']));
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>重複飯店檢測 — 管理後台</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h2 class="mb-4"><i class="fa-solid fa-copy me-2 text-danger"></i>重複飯店檢測</h2>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
  <?php endif; ?>

  <!-- 統計卡 -->
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card border-<?= $dupCount > 0 ? 'danger' : 'success' ?> h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-triangle-exclamation fa-2x text-<?= $dupCount > 0 ? 'danger' : 'success' ?>"></i>
          <div>
            <div class="fs-3 fw-bold"><?= $dupCount ?></div>
            <div class="text-muted">待刪除重複筆數</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-object-group fa-2x text-warning"></i>
          <div>
            <div class="fs-3 fw-bold"><?= count($groups) ?></div>
            <div class="text-muted">重複名稱組數</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-hotel fa-2x text-primary"></i>
          <div>
            <div class="fs-3 fw-bold"><?= $pdo->query('SELECT COUNT(*) FROM Hotels')->fetchColumn() ?></div>
            <div class="text-muted">目前飯店總數</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (empty($groups)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
      <i class="fa-solid fa-circle-check fa-lg"></i>
      <span>目前沒有重複飯店，資料庫乾淨。</span>
    </div>
  <?php else: ?>
    <!-- 一鍵去重按鈕 -->
    <form method="post" class="mb-4">
      <input type="hidden" name="dedup" value="1">
      <button class="btn btn-danger"
              onclick="return confirm('確定刪除 <?= $dupCount ?> 筆重複飯店？\n每組保留 hotel_id 最小的一筆，其餘連同收藏與評論一起刪除，此操作不可還原。')">
        <i class="fa-solid fa-trash me-1"></i>一鍵去重（刪除 <?= $dupCount ?> 筆）
      </button>
    </form>

    <!-- 重複詳情 -->
    <?php foreach ($groups as $name => $rows): ?>
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="fa-solid fa-layer-group text-warning"></i>
        <strong><?= e($name) ?></strong>
        <span class="badge bg-danger ms-1"><?= count($rows) ?> 筆</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>hotel_id</th><th>地區</th><th>每晚</th><th>星級</th><th>狀態</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
            <tr class="<?= $r['is_keeper'] ? 'table-success' : 'table-danger' ?>">
              <td><?= $r['hotel_id'] ?></td>
              <td><?= e($r['region']) ?></td>
              <td>NT$ <?= number_format($r['price_per_night']) ?></td>
              <td><?= str_repeat('★', $r['stars']) ?></td>
              <td>
                <?php if ($r['is_keeper']): ?>
                  <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>保留</span>
                <?php else: ?>
                  <span class="badge bg-danger"><i class="fa-solid fa-trash me-1"></i>刪除</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <a href="dashboard.php" class="btn btn-secondary mt-2">
    <i class="fa-solid fa-arrow-left me-1"></i>返回後台
  </a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
