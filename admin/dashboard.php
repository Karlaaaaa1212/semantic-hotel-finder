<?php
session_start();
require_once __DIR__ . '/../db/config.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit;
}
$pdo = getPDO();
$stats = [
    'hotels'  => $pdo->query('SELECT COUNT(*) FROM Hotels')->fetchColumn(),
    'users'   => $pdo->query('SELECT COUNT(*) FROM Users')->fetchColumn(),
    'reviews' => $pdo->query('SELECT COUNT(*) FROM Reviews')->fetchColumn(),
    'searches'=> $pdo->query('SELECT COUNT(*) FROM SearchLogs')->fetchColumn(),
    'vec'     => $pdo->query('SELECT COUNT(*) FROM Hotels WHERE embedding IS NOT NULL AND embedding != ""')->fetchColumn(),
    'dup'     => $pdo->query('SELECT COUNT(*) FROM Hotels WHERE hotel_id NOT IN (SELECT MIN(hotel_id) FROM Hotels GROUP BY name)')->fetchColumn(),
];
$hotels = $pdo->query('SELECT hotel_id, name, region, price_per_night, stars FROM Hotels ORDER BY hotel_id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>管理後台 — 飯店推薦系統</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
.stat-card { border-left: 4px solid; }
.stat-card.primary { border-color: #0d6efd; }
.stat-card.success { border-color: #198754; }
.stat-card.info    { border-color: #0dcaf0; }
.stat-card.warning { border-color: #ffc107; }
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-4">
  <span class="navbar-brand"><i class="fa-solid fa-hotel me-2"></i>管理後台</span>
  <div>
    <a href="../index.php" class="btn btn-sm btn-outline-light me-2">前台首頁</a>
    <a href="../api/auth.php?action=logout" class="btn btn-sm btn-danger">登出</a>
  </div>
</nav>
<div class="container py-4">
  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible">
      <?= $_GET['msg'] === 'deleted' ? '飯店已刪除。' : '操作成功。' ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card stat-card primary h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-hotel fa-2x text-primary"></i>
          <div><div class="fs-3 fw-bold"><?= $stats['hotels'] ?></div><div class="text-muted">飯店數量</div></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card stat-card success h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-users fa-2x text-success"></i>
          <div><div class="fs-3 fw-bold"><?= $stats['users'] ?></div><div class="text-muted">會員數量</div></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card stat-card info h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-comment fa-2x text-info"></i>
          <div><div class="fs-3 fw-bold"><?= $stats['reviews'] ?></div><div class="text-muted">評論數量</div></div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card stat-card warning h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-brain fa-2x text-warning"></i>
          <div><div class="fs-3 fw-bold"><?= $stats['vec'] ?>/<?= $stats['hotels'] ?></div><div class="text-muted">向量已建立</div></div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($stats['dup'] > 0): ?>
  <div class="alert alert-warning d-flex align-items-center justify-content-between mb-3">
    <span><i class="fa-solid fa-triangle-exclamation me-2"></i>偵測到 <strong><?= $stats['dup'] ?></strong> 筆重複飯店</span>
    <a href="dedup_hotels.php" class="btn btn-warning btn-sm">前往去重</a>
  </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">飯店列表</h5>
    <div>
      <a href="dedup_hotels.php" class="btn btn-outline-danger btn-sm me-2">
        <i class="fa-solid fa-copy me-1"></i>去重檢測
      </a>
      <a href="embed_hotels.php" class="btn btn-outline-primary btn-sm me-2">
        <i class="fa-solid fa-rotate me-1"></i>重建向量
      </a>
      <a href="fetch_hotel_images.php" class="btn btn-outline-success btn-sm me-2">
        <i class="fa-solid fa-image me-1"></i>抓取實景照片
      </a>
      <a href="hotel_edit.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i>新增飯店
      </a>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>ID</th><th>飯店名稱</th><th>地區</th><th>每晚</th><th>星級</th><th>操作</th></tr>
        </thead>
        <tbody>
          <?php foreach ($hotels as $h): ?>
          <tr>
            <td><?= $h['hotel_id'] ?></td>
            <td><?= e($h['name']) ?></td>
            <td><span class="badge bg-secondary"><?= e($h['region']) ?></span></td>
            <td>NT$ <?= number_format($h['price_per_night']) ?></td>
            <td><?= str_repeat('★', $h['stars']) ?></td>
            <td>
              <a href="hotel_edit.php?id=<?= $h['hotel_id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-pen-to-square"></i> 編輯
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
