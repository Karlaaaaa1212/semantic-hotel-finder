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

// Check if favorited
$isFaved = false;
if ($isLoggedIn) {
    $stmt = $pdo->prepare('SELECT 1 FROM Favorites WHERE user_id = ? AND hotel_id = ?');
    $stmt->execute([$_SESSION['user_id'], $hotelId]);
    $isFaved = (bool)$stmt->fetch();
}

// Average rating
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
      <?php if ($isLoggedIn): ?>
        <a class="btn btn-sm btn-outline-light" href="profile.php"><i class="fa-solid fa-user me-1"></i><?= e($_SESSION['username']) ?></a>
        <a class="btn btn-sm btn-danger" href="api/auth.php?action=logout">登出</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-light" href="login.php">登入</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">首頁</a></li>
      <li class="breadcrumb-item active"><?= e($h['name']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <!-- Left: image + details -->
    <div class="col-lg-7">
      <img src="<?= e($h['img_url'] ?: 'uploads/default.jpg') ?>"
           class="img-fluid rounded-3 w-100 hotel-img-thumb mb-3"
           style="max-height:420px;object-fit:cover;cursor:zoom-in"
           alt="<?= e($h['name']) ?>"
           data-full="<?= e($h['img_url'] ?: 'uploads/default.jpg') ?>"
           data-title="<?= e($h['name']) ?>">

      <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
          <h1 class="h3 fw-bold mb-1"><?= e($h['name']) ?></h1>
          <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-secondary"><i class="fa-solid fa-location-dot me-1"></i><?= e($h['region']) ?></span>
            <span class="stars fs-5"><?= str_repeat('★', $h['stars']) . str_repeat('☆', 5 - $h['stars']) ?></span>
            <?php if ($reviewCount > 0): ?>
              <span class="text-muted small"><?= round($avgRating, 1) ?> 分 (<?= $reviewCount ?> 則評論)</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($isLoggedIn): ?>
          <button class="btn-heart btn btn-link p-1" data-hotel-id="<?= $h['hotel_id'] ?>" aria-label="收藏此飯店">
            <i class="<?= $isFaved ? 'fa-solid' : 'fa-regular' ?> fa-heart fa-2x"></i>
          </button>
        <?php endif; ?>
      </div>

      <p class="text-muted"><?= e($h['description'] ?? '') ?></p>

      <div class="mb-3">
        <?php foreach ($facilities as $f): ?>
          <span class="badge bg-light text-secondary border me-1 mb-1"><?= e($f) ?></span>
        <?php endforeach; ?>
      </div>

      <div class="card border-0 bg-primary bg-opacity-10 p-3 rounded-3 mb-4">
        <span class="text-muted small">每晚價格</span>
        <span class="price fs-3">NT$ <?= number_format($h['price_per_night']) ?></span>
      </div>
    </div>

    <!-- Right: Reviews -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 p-4">
        <h5 class="mb-3"><i class="fa-solid fa-comment me-2 text-primary"></i>旅客評論</h5>
        <div id="reviewList">
          <!-- Loaded by AJAX -->
          <div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>
        </div>

        <?php if ($isLoggedIn): ?>
        <hr>
        <h6 class="mb-3">撰寫評論</h6>
        <form id="reviewForm">
          <div class="mb-2">
            <label class="form-label small fw-semibold">評分</label>
            <div class="star-select d-flex gap-1" role="group" aria-label="評分選擇">
              <?php for ($s=1; $s<=5; $s++): ?>
                <span data-val="<?= $s ?>" title="<?= $s ?> 星" aria-label="<?= $s ?> 星">★</span>
              <?php endfor; ?>
            </div>
            <input type="hidden" id="ratingInput" name="rating" value="0">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold" for="reviewContent">評論內容 <span class="text-danger">*</span></label>
            <textarea id="reviewContent" name="content" class="form-control" rows="3" required
                      placeholder="分享你的住宿體驗…"></textarea>
          </div>
          <div id="reviewError" class="mb-2" style="display:none"></div>
          <button type="submit" class="btn btn-primary btn-sm w-100" id="reviewBtn">
            <i class="fa-solid fa-paper-plane me-1"></i>送出評論
          </button>
        </form>
        <?php else: ?>
          <div class="alert alert-info mt-3 mb-0">
            <a href="login.php">登入</a>後即可撰寫評論
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

<!-- Scroll to top -->
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

// Load reviews
loadReviews();

function loadReviews() {
  $.get('/api/review.php', { hotel_id: HOTEL_ID }, function (res) {
    const $list = $('#reviewList').empty();
    if (!res.data.length) {
      $list.html('<p class="text-muted text-center">尚無評論，成為第一個留言的人！</p>');
      return;
    }
    res.data.forEach(r => $list.append(buildReviewHTML(r)));
  }, 'json');
}

function buildReviewHTML(r) {
  const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
  return `<div class="review-item border-bottom pb-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="fw-semibold small">${r.username}</span>
      <span class="text-warning small">${stars}</span>
    </div>
    <p class="mb-1 small">${r.content}</p>
    <span class="text-muted" style="font-size:.7rem">${r.created_at}</span>
  </div>`;
}

// Review submit
$('#reviewForm').on('submit', function (e) {
  e.preventDefault();
  const rating  = parseInt($('#ratingInput').val());
  const content = $('#reviewContent').val().trim();
  if (rating < 1) { showFormError('#reviewError', '請選擇評分'); return; }
  if (!content)   { showFormError('#reviewError', '請填寫評論內容'); return; }

  const $btn = $('#reviewBtn').prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm"></span>');

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
    $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i>送出評論');
  });
});

function showFormError(sel, msg) {
  $(sel).addClass('alert alert-danger').text(msg).show();
}
</script>
</body>
</html>
