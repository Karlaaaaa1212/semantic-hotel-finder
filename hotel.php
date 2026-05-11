<?php
session_start();
require_once __DIR__ . '/db/config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$hotelId    = (int)($_GET['id'] ?? 0);
if (!$hotelId) { header('Location: index.php'); exit; }

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT * FROM Hotels WHERE hotel_id = ?');
$stmt->execute([$hotelId]);
$h = $stmt->fetch();
if (!$h) { header('Location: index.php'); exit; }

$facilities = json_decode($h['facilities'] ?? '[]', true);

$isFaved = false;
if ($isLoggedIn) {
    $stmt = $pdo->prepare('SELECT 1 FROM Favorites WHERE user_id = ? AND hotel_id = ?');
    $stmt->execute([$_SESSION['user_id'], $hotelId]);
    $isFaved = (bool)$stmt->fetch();
}

$avg = $pdo->prepare('SELECT AVG(rating), COUNT(*) FROM Reviews WHERE hotel_id = ?');
$avg->execute([$hotelId]);
[$avgRating, $reviewCount] = $avg->fetch(PDO::FETCH_NUM);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($h['name']) ?> — 飯店推薦系統</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
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
      <?php if ($isLoggedIn): ?>
        <a class="user-chip" href="profile.php">
          <span class="user-avatar"><?= mb_substr(e($_SESSION['username']), 0, 1) ?></span>
          <?= e($_SESSION['username']) ?>
        </a>
        <a class="btn-nav-ghost" href="api/auth.php?action=logout">登出</a>
      <?php else: ?>
        <a class="btn-nav-ghost" href="login.php">登入</a>
        <a class="btn-nav-primary" href="register.php">免費註冊</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">

  <!-- Breadcrumb -->
  <div class="crumb">
    <a href="index.php">首頁</a>
    <span class="sep">›</span>
    <?= e($h['name']) ?>
  </div>

  <!-- Hero image -->
  <img src="<?= e($h['img_url'] ?: 'https://picsum.photos/seed/hotel'.$h['hotel_id'].'/1200/500') ?>"
       class="hotel-hero-img hotel-img-thumb"
       alt="<?= e($h['name']) ?>"
       data-full="<?= e($h['img_url'] ?: 'https://picsum.photos/seed/hotel'.$h['hotel_id'].'/1200/500') ?>"
       data-title="<?= e($h['name']) ?>">

  <div class="row g-5">
    <!-- Left: details -->
    <div class="col-lg-7">
      <div class="detail-region">
        <i class="fa-solid fa-location-dot me-1"></i><?= e($h['region']) ?>
      </div>
      <h1 class="detail-title"><?= e($h['name']) ?></h1>

      <div class="detail-meta">
        <span class="stars"><?= str_repeat('★', $h['stars']) . str_repeat('☆', 5 - $h['stars']) ?></span>
        <?php if ($reviewCount > 0): ?>
          <span class="rating-text"><?= round($avgRating, 1) ?> 分 · <?= $reviewCount ?> 則評論</span>
        <?php endif; ?>
        <?php if ($isLoggedIn): ?>
          <button class="fav-btn ms-2" data-hotel-id="<?= $h['hotel_id'] ?>" aria-label="收藏此飯店" style="position:static;box-shadow:none;border:1.5px solid var(--border);background:var(--surface);">
            <i class="<?= $isFaved ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
          </button>
        <?php endif; ?>
      </div>

      <p class="text-muted mb-4" style="font-size:15px;line-height:1.75;"><?= e($h['description'] ?? '') ?></p>

      <!-- Facilities -->
      <?php if ($facilities): ?>
      <div class="mb-4">
        <?php foreach ($facilities as $f): ?>
          <span class="facility-tag"><i class="fa-solid fa-check me-1" style="color:var(--accent);font-size:10px;"></i><?= e($f) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Price -->
      <div class="detail-price-block">
        <div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:4px;">每晚價格</div>
        <div class="detail-price">NT$ <?= number_format($h['price_per_night']) ?><small>/晚</small></div>
        <?php if ($isLoggedIn): ?>
          <button class="btn btn-primary mt-3 px-4">
            <i class="fa-solid fa-calendar-check me-2"></i>查看訂房
          </button>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary mt-3 px-4">
            <i class="fa-solid fa-right-to-bracket me-2"></i>登入後查看訂房
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: Reviews -->
    <div class="col-lg-5">
      <div class="review-card">
        <h5 style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:20px;">
          旅客評論
          <?php if ($reviewCount > 0): ?>
            <span style="font-family:var(--font-mono);font-size:12px;color:var(--muted);font-weight:500;margin-left:8px;"><?= $reviewCount ?> 則</span>
          <?php endif; ?>
        </h5>

        <div id="reviewList">
          <div class="text-center py-3"><span class="spinner-border spinner-border-sm" style="color:var(--accent)"></span></div>
        </div>

        <?php if ($isLoggedIn): ?>
        <hr style="border-color:var(--border);margin:20px 0;">
        <div class="review-form-card">
          <h6>撰寫評論</h6>
          <form id="reviewForm">
            <div class="mb-3">
              <label class="form-label" style="font-size:12px;">評分</label>
              <div class="star-select d-flex gap-1" role="group" aria-label="評分選擇">
                <?php for ($s=1; $s<=5; $s++): ?>
                  <span data-val="<?= $s ?>" title="<?= $s ?> 星">★</span>
                <?php endfor; ?>
              </div>
              <input type="hidden" id="ratingInput" name="rating" value="0">
            </div>
            <div class="mb-3">
              <label class="form-label" for="reviewContent">評論內容 <span style="color:var(--danger)">*</span></label>
              <textarea id="reviewContent" name="content" class="form-control" rows="3" required
                        placeholder="分享你的住宿體驗…"></textarea>
            </div>
            <div id="reviewError" class="mb-2" style="display:none"></div>
            <button type="submit" class="btn btn-primary w-100" id="reviewBtn">
              <i class="fa-solid fa-paper-plane me-2"></i>送出評論
            </button>
          </form>
        </div>
        <?php else: ?>
          <div class="login-prompt-card">
            <h5><i class="fa-solid fa-pen-to-square me-2"></i>留下你的評論</h5>
            <p>登入後即可分享你的住宿體驗，幫助其他旅客找到理想旅宿。</p>
            <a href="login.php" class="btn btn-primary px-4">前往登入</a>
          </div>
        <?php endif; ?>
      </div>
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
        <div class="footer-brand">
          <span class="logo-mark">H</span>飯店推薦
        </div>
        <p>AI 驅動的台灣飯店推薦系統，用自然語言找到最適合你的旅宿。</p>
      </div>
      <div class="col-6 col-lg-3">
        <span class="footer-heading">探索</span>
        <ul class="footer-links">
          <li><a href="index.php">所有飯店</a></li>
          <?php if ($isLoggedIn): ?><li><a href="profile.php">我的收藏</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-3">
        <span class="footer-heading">帳號</span>
        <ul class="footer-links">
          <?php if ($isLoggedIn): ?>
            <li><a href="api/auth.php?action=logout">登出</a></li>
          <?php else: ?>
            <li><a href="login.php">登入</a></li>
            <li><a href="register.php">免費註冊</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> 飯店推薦系統 · AI Powered by Gemini</span>
    </div>
  </div>
</footer>

<button id="scrollTop" aria-label="回到頂端">
  <i class="fa-solid fa-chevron-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.HOTEL_BASE = '/final';</script>
<script src="js/main.js"></script>
<script>
const HOTEL_ID     = <?= $h['hotel_id'] ?>;
const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;

loadReviews();

function loadReviews() {
  $.get('/api/review.php', { hotel_id: HOTEL_ID }, function (res) {
    const $list = $('#reviewList').empty();
    if (!res.data.length) {
      $list.html('<p class="text-muted text-center" style="font-size:14px;padding:20px 0;">尚無評論，成為第一個留言的人！</p>');
      return;
    }
    res.data.forEach(r => $list.append(buildReviewHTML(r)));
  }, 'json');
}

function buildReviewHTML(r) {
  const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
  const initial = r.username ? r.username.charAt(0).toUpperCase() : '?';
  return `<div class="review-item">
    <div class="review-author-row">
      <div class="review-avatar">${initial}</div>
      <div>
        <div class="review-author-name">${r.username}</div>
        <div class="review-stars" style="font-size:13px;">${stars}</div>
      </div>
      <span class="review-time">${r.created_at}</span>
    </div>
    <p class="review-text mb-0">${r.content}</p>
  </div>`;
}

$('#reviewForm').on('submit', function (e) {
  e.preventDefault();
  const rating  = parseInt($('#ratingInput').val());
  const content = $('#reviewContent').val().trim();
  if (rating < 1) { showFormError('#reviewError', '請選擇評分'); return; }
  if (!content)   { showFormError('#reviewError', '請填寫評論內容'); return; }

  const $btn = $('#reviewBtn').prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm me-2"></span>送出中…');

  $.ajax({
    url: '/api/review.php',
    type: 'POST',
    data: { hotel_id: HOTEL_ID, rating, content },
    dataType: 'json',
  }).done(function (res) {
    if (res.status === 'ok') {
      $('#reviewList').prepend(buildReviewHTML(res));
      $('#reviewContent').val('');
      $('#ratingInput').val(0);
      $('.star-select span').removeClass('active');
      $('#reviewError').hide();
      showToast('評論已送出！', 'success');
    } else {
      showFormError('#reviewError', res.message);
    }
  }).fail(function () {
    showFormError('#reviewError', '連線失敗，請稍後再試');
  }).always(function () {
    $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-2"></i>送出評論');
  });
});

function showFormError(sel, msg) {
  $(sel).addClass('alert alert-danger').text(msg).show();
}
</script>
</body>
</html>
