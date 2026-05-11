<?php
session_start();
require_once __DIR__ . '/db/config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$pdo        = getPDO();
$regions    = $pdo->query('SELECT DISTINCT region FROM Hotels ORDER BY region')->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>飯店推薦系統 — 首頁</title>
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
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto gap-1">
        <?php if ($isLoggedIn): ?>
          <li class="nav-item">
            <a class="nav-link text-white" href="profile.php">
              <i class="fa-solid fa-user me-1"></i><?= e($_SESSION['username']) ?>
            </a>
          </li>
          <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link text-white" href="admin/dashboard.php">
              <i class="fa-solid fa-gear me-1"></i>後台
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="btn btn-sm btn-outline-light ms-2" href="api/auth.php?action=logout">登出</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-sm btn-outline-light ms-2" href="login.php">登入</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm btn-light ms-2 text-primary fw-semibold" href="register.php">註冊</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Search -->
<section class="hero-search">
  <div class="container text-center">
    <h1 class="fw-bold mb-2" style="font-size:2rem">找到最適合你的飯店</h1>
    <p class="mb-4 opacity-75">輸入自然語言，AI 理解你的需求</p>
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <form id="semanticForm" class="d-flex" role="search" aria-label="語義搜尋">
          <input id="semanticInput" type="search" class="form-control search-input"
                 placeholder="例如：想找台北適合帶小孩的溫泉飯店…"
                 aria-label="語義搜尋輸入">
          <button id="semanticBtn" class="btn btn-warning fw-bold search-btn" type="submit">
            <i class="fa-solid fa-magnifying-glass me-1"></i>AI 搜尋
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Filter Bar -->
<div class="container my-4">
  <div class="filter-bar p-3">
    <form id="filterForm" class="row g-2 align-items-end">
      <div class="col-sm-3 col-md-2">
        <label class="form-label small fw-semibold" for="filterRegion">地區</label>
        <select id="filterRegion" name="region" class="form-select form-select-sm">
          <option value="">全部地區</option>
          <?php foreach ($regions as $r): ?>
            <option value="<?= e($r) ?>"><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-3 col-md-2">
        <label class="form-label small fw-semibold" for="filterMinPrice">最低價</label>
        <input id="filterMinPrice" name="min_price" type="number" class="form-control form-control-sm" placeholder="0">
      </div>
      <div class="col-sm-3 col-md-2">
        <label class="form-label small fw-semibold" for="filterMaxPrice">最高價</label>
        <input id="filterMaxPrice" name="max_price" type="number" class="form-control form-control-sm" placeholder="99999">
      </div>
      <div class="col-sm-3 col-md-2">
        <label class="form-label small fw-semibold" for="filterStars">最低星級</label>
        <select id="filterStars" name="stars" class="form-select form-select-sm">
          <option value="0">不限</option>
          <?php for ($s=3; $s<=5; $s++): ?>
            <option value="<?= $s ?>"><?= $s ?> 星以上</option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-sm-4 col-md-3">
        <label class="form-label small fw-semibold" for="filterKeyword">關鍵字</label>
        <input id="filterKeyword" name="keyword" type="text" class="form-control form-control-sm" placeholder="溫泉、親子…">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-filter me-1"></i>篩選
        </button>
        <button type="reset" class="btn btn-outline-secondary btn-sm ms-1">清除</button>
      </div>
    </form>
  </div>
</div>

<!-- Results -->
<div class="container mb-5">
  <div class="d-flex align-items-center mb-3 gap-2">
    <h5 id="resultsTitle" class="mb-0">所有飯店</h5>
    <span id="resultsCount" class="text-muted small"></span>
  </div>
  <div id="hotelGrid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <!-- Filled by JS -->
  </div>
  <div id="emptyState" class="text-center py-5 d-none">
    <i class="fa-solid fa-hotel fa-3x text-muted mb-3"></i>
    <p class="text-muted">找不到符合條件的飯店，請調整搜尋條件。</p>
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
const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
const API_BASE     = window.HOTEL_BASE || '';

/* Load all hotels on page load */
loadHotels({});

/* Filter form submit */
$('#filterForm').on('submit', function (e) {
  e.preventDefault();
  loadHotels(Object.fromEntries(new URLSearchParams($(this).serialize())));
});
$('#filterForm').on('reset', function () {
  setTimeout(() => loadHotels({}), 50);
});

/* Semantic search */
$('#semanticForm').on('submit', function (e) {
  e.preventDefault();
  const q = $('#semanticInput').val().trim();
  if (!q) return;
  showSkeletons(5);
  $('#resultsTitle').text('AI 語義搜尋結果');
  $('#semanticBtn').prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm"></span>');

  $.ajax({
    url: API_BASE + '/api/semantic_search.php',
    type: 'POST',
    data: { query: q },
    dataType: 'json',
  }).done(function (res) {
    if (res.status === 'ok') {
      renderCards(res.data);
      $('#resultsCount').text(`找到 ${res.data.length} 間飯店`);
    } else if (res.message && res.message.includes('向量')) {
      showSearchError(res.message + '（已改用關鍵字搜尋）');
      loadHotels({ keyword: q });
    } else {
      showSearchError(res.message || '搜尋失敗');
    }
  }).fail(function () {
    showSearchError('連線失敗，請確認伺服器是否正常運作');
  }).always(function () {
    $('#semanticBtn').prop('disabled', false)
      .html('<i class="fa-solid fa-magnifying-glass me-1"></i>AI 搜尋');
  });
});

function showSearchError(msg) {
  $('#hotelGrid').empty();
  $('#emptyState').addClass('d-none');
  $('#hotelGrid').html(
    `<div class="col-12"><div class="alert alert-danger d-flex align-items-center gap-2">
      <i class="fa-solid fa-circle-exclamation"></i>
      <span>AI 搜尋錯誤：${msg}</span>
    </div></div>`
  );
}

function loadHotels(params) {
  showSkeletons(6);
  $('#resultsTitle').text('所有飯店');
  $.get(API_BASE + '/api/search.php', params, function (res) {
    if (res.status === 'ok') {
      renderCards(res.data);
      $('#resultsCount').text(`共 ${res.data.length} 間`);
    }
  }, 'json').fail(function () {
    showToast('載入失敗', 'danger');
  });
}

function showSkeletons(n) {
  const $grid = $('#hotelGrid').empty();
  for (let i = 0; i < n; i++) $grid.append(skeletonCard());
  $('#emptyState').addClass('d-none');
}

function renderCards(hotels) {
  const $grid = $('#hotelGrid').empty();
  if (!hotels.length) {
    $('#emptyState').removeClass('d-none');
    return;
  }
  $('#emptyState').addClass('d-none');
  hotels.forEach(h => $grid.append(renderHotelCard(h, IS_LOGGED_IN)));
}
</script>
</body>
</html>
