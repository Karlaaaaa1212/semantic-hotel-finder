<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/db/config.php';

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Favorites with hotel info
$stmt = $pdo->prepare(
    'SELECT h.hotel_id, h.name, h.region, h.price_per_night, h.stars, h.img_url, h.description
     FROM Favorites f JOIN Hotels h ON f.hotel_id = h.hotel_id
     WHERE f.user_id = ? ORDER BY f.created_at DESC'
);
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();

// My reviews
$stmt = $pdo->prepare(
    'SELECT r.*, h.name AS hotel_name FROM Reviews r
     JOIN Hotels h ON r.hotel_id = h.hotel_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC'
);
$stmt->execute([$userId]);
$myReviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>我的帳號 — 飯店推薦系統</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav id="mainNavbar" class="navbar navbar-expand-lg sticky-top" aria-label="主導覽">
  <div class="container">
    <a class="navbar-brand text-white fw-bold" href="index.php">
      <i class="fa-solid fa-hotel me-2"></i>飯店推薦
    </a>
    <div class="ms-auto d-flex gap-2">
      <span class="navbar-text text-white small"><i class="fa-solid fa-user me-1"></i><?= e($_SESSION['username']) ?></span>
      <a class="btn btn-sm btn-danger" href="api/auth.php?action=logout">登出</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="mb-1 fw-bold"><i class="fa-solid fa-user-circle me-2 text-primary"></i><?= e($_SESSION['username']) ?></h2>
  <p class="text-muted mb-4"><?= e($_SESSION['username']) ?> 的個人頁面</p>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#favTab">
        <i class="fa-solid fa-heart me-1 text-danger"></i>我的收藏
        <span class="badge bg-danger ms-1"><?= count($favorites) ?></span>
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviewTab">
        <i class="fa-solid fa-comment me-1 text-primary"></i>我的評論
        <span class="badge bg-primary ms-1"><?= count($myReviews) ?></span>
      </button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- Favorites Tab -->
    <div class="tab-pane fade show active" id="favTab">
      <?php if (!$favorites): ?>
        <div class="text-center py-5">
          <i class="fa-regular fa-heart fa-3x text-muted mb-3"></i>
          <p class="text-muted">還沒有收藏的飯店，<a href="index.php">探索飯店</a>並點擊 ♥ 加入收藏！</p>
        </div>
      <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <?php foreach ($favorites as $h): ?>
          <div class="col" id="fav-<?= $h['hotel_id'] ?>">
            <div class="card card-hotel h-100">
              <img src="<?= e($h['img_url'] ?: 'uploads/default.jpg') ?>"
                   class="card-img-top hotel-img-thumb"
                   alt="<?= e($h['name']) ?>"
                   data-full="<?= e($h['img_url'] ?: 'uploads/default.jpg') ?>"
                   data-title="<?= e($h['name']) ?>"
                   loading="lazy">
              <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-1">
                  <h6 class="fw-bold mb-0"><?= e($h['name']) ?></h6>
                  <button class="btn-heart" data-hotel-id="<?= $h['hotel_id'] ?>" aria-label="取消收藏">
                    <i class="fa-solid fa-heart"></i>
                  </button>
                </div>
                <span class="badge bg-secondary mb-2 align-self-start"><?= e($h['region']) ?></span>
                <p class="text-muted small flex-grow-1"><?= e(mb_substr($h['description'] ?? '', 0, 60)) ?>…</p>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="price">NT$ <?= number_format($h['price_per_night']) ?></span>
                  <a href="hotel.php?id=<?= $h['hotel_id'] ?>" class="btn btn-sm btn-outline-primary">查看詳情</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Reviews Tab -->
    <div class="tab-pane fade" id="reviewTab">
      <?php if (!$myReviews): ?>
        <div class="text-center py-5">
          <i class="fa-regular fa-comment fa-3x text-muted mb-3"></i>
          <p class="text-muted">還沒有評論，<a href="index.php">探索飯店</a>後留下你的心得！</p>
        </div>
      <?php else: ?>
        <div class="list-group">
          <?php foreach ($myReviews as $r): ?>
          <div class="list-group-item border-0 border-bottom py-3">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1"><a href="hotel.php?id=<?= $r['hotel_id'] ?>" class="text-decoration-none"><?= e($r['hotel_name']) ?></a></h6>
                <span class="text-warning"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></span>
                <p class="mb-0 mt-1 small"><?= e($r['content']) ?></p>
              </div>
              <span class="text-muted" style="font-size:.75rem;white-space:nowrap"><?= date('Y/m/d', strtotime($r['created_at'])) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-label="飯店圖片">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0 text-white">
        <h6 class="modal-title" id="lightboxLabel"></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-1">
        <img id="lightboxImg" src="" class="img-fluid w-100 rounded" alt="飯店圖片">
      </div>
    </div>
  </div>
</div>

<button id="scrollTop" aria-label="回到頂端"><i class="fa-solid fa-chevron-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.HOTEL_BASE = '/final';</script>
<script src="js/main.js"></script>
<script>
// Remove card from DOM when unfavorited on profile page
$(document).on('click', '.btn-heart', function () {
  const $card = $(this).closest('[id^="fav-"]');
  if ($card.length) {
    const $icon = $(this).find('i');
    if ($icon.hasClass('fa-solid')) {
      // optimistic remove on profile page
      const hotelId = $(this).data('hotel-id');
      $.post('/api/favorite.php', { hotel_id: hotelId }).done(function (res) {
        if (res.status === 'ok' && !res.favorited) {
          $card.fadeOut(300, () => $card.remove());
        }
      });
    }
  }
});
</script>
</body>
</html>
