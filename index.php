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
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <div class="ms-auto d-flex align-items-center gap-2">
        <?php if ($isLoggedIn): ?>
          <a class="user-chip" href="profile.php">
            <span class="user-avatar"><?= mb_substr(e($_SESSION['username']), 0, 1) ?></span>
            <?= e($_SESSION['username']) ?>
          </a>
          <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <a class="btn-nav-ghost" href="admin/dashboard.php">
            <i class="fa-solid fa-gear me-1"></i>後台
          </a>
          <?php endif; ?>
          <a class="btn-nav-ghost" href="api/auth.php?action=logout">登出</a>
        <?php else: ?>
          <a class="btn-nav-ghost" href="login.php">登入</a>
          <a class="btn-nav-primary" href="register.php">免費註冊</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="lumina-hero">
  <div class="container">
    <span class="eyebrow">AI 飯店推薦系統</span>
    <h1>找到<em>最適合你</em>的<br>台灣旅宿</h1>
    <p class="hero-sub">輸入任何需求，讓 AI 理解你的旅遊心情，精準推薦最匹配的飯店。</p>

    <!-- AI Search -->
    <form id="semanticForm" aria-label="AI 語義搜尋">
      <div class="ai-search">
        <span class="ai-search-icon">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
        </span>
        <input id="semanticInput" type="search"
               placeholder="例如：想找台北有溫泉、適合帶小孩的飯店…"
               aria-label="AI 搜尋輸入" autocomplete="off">
        <button id="semanticBtn" class="btn-ai-search" type="submit">
          <i class="fa-solid fa-magnifying-glass"></i>AI 搜尋
        </button>
      </div>
    </form>

    <!-- Chips -->
    <div class="chips">
      <span class="chip-label">試試看</span>
      <button class="chip" type="button" data-query="適合蜜月的海景飯店">🌊 蜜月海景</button>
      <button class="chip" type="button" data-query="台北市中心高檔商務飯店">🏙 台北商務</button>
      <button class="chip" type="button" data-query="花蓮山景親子民宿">🏔 花蓮親子</button>
      <button class="chip" type="button" data-query="墾丁衝浪度假風格飯店">🏄 墾丁衝浪</button>
    </div>
  </div>
</section>

<!-- Filter Bar -->
<div class="container mt-4 mb-3">
  <form id="filterForm">
    <div class="filter-bar">
      <div class="filter-group">
        <span class="filter-label">地區</span>
        <select id="filterRegion" name="region" class="form-select" style="width:110px">
          <option value="">全部</option>
          <?php foreach ($regions as $r): ?>
            <option value="<?= e($r) ?>"><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-divider"></div>
      <div class="filter-group">
        <span class="filter-label">最低價</span>
        <input id="filterMinPrice" name="min_price" type="number" class="form-control" placeholder="0" style="width:88px">
      </div>
      <div class="filter-group">
        <span class="filter-label">最高價</span>
        <input id="filterMaxPrice" name="max_price" type="number" class="form-control" placeholder="不限" style="width:88px">
      </div>
      <div class="filter-divider"></div>
      <div class="filter-group">
        <span class="filter-label">星級</span>
        <select id="filterStars" name="stars" class="form-select" style="width:100px">
          <option value="0">不限</option>
          <?php for ($s=3; $s<=5; $s++): ?>
            <option value="<?= $s ?>"><?= $s ?>★ 以上</option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="filter-divider"></div>
      <div class="filter-group">
        <span class="filter-label">關鍵字</span>
        <input id="filterKeyword" name="keyword" type="text" class="form-control" placeholder="溫泉、親子…" style="width:140px">
      </div>
      <span class="filter-spacer"></span>
      <button type="submit" class="btn-filter">
        <i class="fa-solid fa-sliders"></i>篩選
      </button>
      <button type="reset" class="btn-clear">清除</button>
    </div>
  </form>
</div>

<!-- Results -->
<div class="container mb-5">
  <div class="d-flex align-items-center mb-4 gap-3 results-header">
    <h5 id="resultsTitle" class="mb-0">所有飯店</h5>
    <span id="resultsCount" class="count-badge"></span>
  </div>
  <div id="hotelGrid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <!-- Filled by JS -->
  </div>
  <div id="emptyState" class="d-none">
    <i class="fa-solid fa-hotel fa-3x"></i>
    <p>找不到符合條件的飯店，請調整搜尋條件。</p>
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
    <div class="row g-5">
      <div class="col-lg-4">
        <div class="footer-brand">
          <span class="logo-mark">H</span>飯店推薦
        </div>
        <p>AI 驅動的台灣飯店推薦系統，用自然語言找到最適合你的旅宿。</p>
      </div>
      <div class="col-6 col-lg-2">
        <span class="footer-heading">探索</span>
        <ul class="footer-links">
          <li><a href="index.php">首頁</a></li>
          <li><a href="index.php">所有飯店</a></li>
          <?php if ($isLoggedIn): ?><li><a href="profile.php">我的收藏</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <span class="footer-heading">帳號</span>
        <ul class="footer-links">
          <?php if ($isLoggedIn): ?>
            <li><a href="profile.php">個人頁面</a></li>
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
      <span>PHP + MySQL + Bootstrap 5</span>
    </div>
  </div>
</footer>

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

/* Chip clicks */
$('.chip[data-query]').on('click', function () {
  const q = $(this).data('query');
  $('#semanticInput').val(q);
  $('#semanticForm').trigger('submit');
  $('html,body').animate({ scrollTop: $('#semanticForm').offset().top - 100 }, 300);
});

/* Filter form */
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
    .html('<span class="spinner-border spinner-border-sm me-1"></span>搜尋中…');

  $.ajax({
    url: API_BASE + '/api/semantic_search.php',
    type: 'POST',
    data: { query: q },
    dataType: 'json',
  }).done(function (res) {
    if (res.status === 'ok') {
      renderCards(res.data);
      $('#resultsCount').text('找到 ' + res.data.length + ' 間');
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
      .html('<i class="fa-solid fa-magnifying-glass"></i>AI 搜尋');
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
      $('#resultsCount').text('共 ' + res.data.length + ' 間');
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
