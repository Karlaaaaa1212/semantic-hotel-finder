<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/db/config.php';

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT h.hotel_id, h.name, h.region, h.price_per_night, h.stars, h.img_url, h.description
     FROM Favorites f JOIN Hotels h ON f.hotel_id = h.hotel_id
     WHERE f.user_id = ? ORDER BY f.created_at DESC'
);
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll();

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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="css/style.css?v=2" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav id="mainNavbar" class="navbar navbar-expand-lg sticky-top" aria-label="主導覽">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <span class="logo-mark">H</span>
      飯店推薦
      <span class="logo-zh">Hotel AI</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a class="user-chip" href="profile.php">
        <span class="user-avatar"><?= mb_substr(e($_SESSION['username']), 0, 1) ?></span>
        <?= e($_SESSION['username']) ?>
      </a>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a class="btn-nav-ghost" href="admin/dashboard.php"><i class="fa-solid fa-gear me-1"></i>後台</a>
      <?php endif; ?>
      <a class="btn-nav-ghost" href="api/auth.php?action=logout">登出</a>
    </div>
  </div>
</nav>

<div class="container">

  <!-- Profile header -->
  <div class="profile-hero">
    <span class="eyebrow">Member · 會員</span>
    <h1><?= e($_SESSION['username']) ?></h1>
    <div class="d-flex align-items-end justify-content-between flex-wrap gap-4 mt-3">
      <p class="text-muted mb-0" style="font-size:14px;"><?= e($_SESSION['username']) ?> 的個人頁面</p>
      <div class="profile-stats">
        <div class="profile-stat"><b><?= count($favorites) ?></b>收藏</div>
        <div class="profile-stat"><b><?= count($myReviews) ?></b>評論</div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="lumina-tabs" id="profileTabBtns">
    <button class="lumina-tab active" data-bs-toggle="tab" data-bs-target="#favTab">
      我的收藏 <span class="tab-count"><?= count($favorites) ?></span>
    </button>
    <button class="lumina-tab" data-bs-toggle="tab" data-bs-target="#reviewTab">
      我的評論 <span class="tab-count"><?= count($myReviews) ?></span>
    </button>
  </div>

  <div class="tab-content pb-5">

    <!-- Favorites Tab -->
    <div class="tab-pane fade show active" id="favTab">
      <?php if (!$favorites): ?>
        <div class="text-center py-5" style="color:var(--muted);">
          <i class="fa-regular fa-heart fa-3x mb-3 d-block" style="color:var(--muted-2);"></i>
          <p>還沒有收藏的飯店，<a href="index.php" style="color:var(--accent);">探索飯店</a>並點擊 ♥ 加入收藏！</p>
        </div>
      <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <?php foreach ($favorites as $hf): ?>
          <div class="col" id="fav-<?= $hf['hotel_id'] ?>">
            <div class="card-hotel">
              <div class="img-slot">
                <img src="<?= e($hf['img_url'] ?: 'https://picsum.photos/seed/hotel'.$hf['hotel_id'].'/600/400') ?>"
                     alt="<?= e($hf['name']) ?>"
                     class="hotel-img-thumb"
                     data-full="<?= e($hf['img_url'] ?: 'https://picsum.photos/seed/hotel'.$hf['hotel_id'].'/600/400') ?>"
                     data-title="<?= e($hf['name']) ?>"
                     loading="lazy">
                <button class="fav-btn active" data-hotel-id="<?= $hf['hotel_id'] ?>" aria-label="取消收藏">
                  <i class="fa-solid fa-heart"></i>
                </button>
              </div>
              <div class="card-body">
                <div class="hotel-card-name"><?= e($hf['name']) ?></div>
                <div class="hotel-card-region">
                  <i class="fa-solid fa-location-dot me-1"></i><?= e($hf['region']) ?>
                  <span class="ms-2 hotel-card-stars"><?= str_repeat('★', $hf['stars']) ?></span>
                </div>
                <p class="text-muted mb-3" style="font-size:13px;line-height:1.6;"><?= e(mb_substr($hf['description'] ?? '', 0, 60)) ?>…</p>
                <div class="d-flex align-items-center justify-content-between">
                  <span class="hotel-card-price">NT$ <?= number_format($hf['price_per_night']) ?><small>/晚</small></span>
                  <a href="hotel.php?id=<?= $hf['hotel_id'] ?>" class="btn-card-detail">查看詳情</a>
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
        <div class="text-center py-5" style="color:var(--muted);">
          <i class="fa-regular fa-comment fa-3x mb-3 d-block" style="color:var(--muted-2);"></i>
          <p>還沒有評論，<a href="index.php" style="color:var(--accent);">探索飯店</a>後留下你的心得！</p>
        </div>
      <?php else: ?>
        <div>
          <?php foreach ($myReviews as $r): ?>
          <div class="profile-review-item">
            <div class="d-flex justify-content-between align-items-start gap-4">
              <div>
                <div class="profile-review-hotel">
                  <a href="hotel.php?id=<?= $r['hotel_id'] ?>"><?= e($r['hotel_name']) ?></a>
                </div>
                <div class="profile-review-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
                <p class="profile-review-text mb-0 mt-2"><?= e($r['content']) ?></p>
              </div>
              <div class="profile-review-time text-end" style="white-space:nowrap;">
                <?= date('Y/m/d', strtotime($r['created_at'])) ?>
              </div>
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

<!-- Footer -->
<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <div class="footer-brand"><span class="logo-mark">H</span>飯店推薦</div>
        <p>AI 驅動的台灣飯店推薦系統。</p>
      </div>
      <div class="col-6 col-lg-3">
        <span class="footer-heading">快速連結</span>
        <ul class="footer-links">
          <li><a href="index.php">首頁</a></li>
          <li><a href="api/auth.php?action=logout">登出</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> 飯店推薦系統 · AI Powered by Gemini</span>
    </div>
  </div>
</footer>

<button id="scrollTop" aria-label="回到頂端"><i class="fa-solid fa-chevron-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.HOTEL_BASE = '/final';</script>
<script src="js/main.js"></script>
<script>
/* Sync lumina-tab active class with Bootstrap tab events */
$('#profileTabBtns .lumina-tab').on('shown.bs.tab', function () {
  $('#profileTabBtns .lumina-tab').removeClass('active');
  $(this).addClass('active');
});

/* Remove card from DOM when unfavorited on profile page */
$(document).on('click', '.fav-btn', function () {
  const $card = $(this).closest('[id^="fav-"]');
  if ($card.length) {
    const $icon = $(this).find('i');
    if ($icon.hasClass('fa-solid')) {
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
