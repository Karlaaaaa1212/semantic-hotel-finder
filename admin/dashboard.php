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
$vecPct = $stats['hotels'] > 0 ? round($stats['vec'] / $stats['hotels'] * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>管理後台 — 飯店推薦系統</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../css/style.css?v=2" rel="stylesheet">
<style>
/* Admin-only extras */
.admin-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 20px; margin: 36px 0; }
@media(max-width:900px){ .admin-grid { grid-template-columns: repeat(2,1fr); } }

.dash-row { display: grid; grid-template-columns: 1.4fr 1fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:900px){ .dash-row { grid-template-columns: 1fr; } }

.panel {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
}
.panel h3 {
  font-family: var(--font-display);
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 4px;
  letter-spacing: -.02em;
}
.panel-sub { color: var(--muted); font-size: 13px; margin-bottom: 20px; font-weight: 500; }

/* Embed progress */
.embed-bar {
  height: 10px;
  background: var(--bg-2);
  border-radius: 999px;
  overflow: hidden;
  margin: 16px 0 8px;
}
.embed-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--accent), var(--accent-2));
  border-radius: 999px;
  transition: width 1.4s cubic-bezier(.2,.8,.3,1);
  position: relative;
}
.embed-fill::after {
  content:"";
  position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),transparent);
  animation:adminShine 2s infinite;
}
@keyframes adminShine{ from{transform:translateX(-100%)} to{transform:translateX(100%)} }
.embed-nums { display:flex; justify-content:space-between; align-items:center; }
.embed-nums .big {
  font-family: var(--font-display);
  font-size: 40px; font-weight: 800;
  color: var(--accent);
  letter-spacing: -.03em;
}
.embed-nums .pct {
  font-family: var(--font-mono);
  font-size: 12px; color: var(--muted);
}

/* Warning panel */
.warn-panel {
  border: 1.5px solid var(--warning);
  background: #fff8ec;
  border-radius: var(--radius-lg);
  padding: 24px 28px;
}
.warn-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
.warn-dot { width:10px; height:10px; border-radius:50%; background:var(--warning); flex-shrink:0; }
.warn-head h4 {
  font-size: 13px; font-weight: 700;
  color: var(--warning); margin: 0;
  text-transform: uppercase; letter-spacing:.06em;
}

/* Hotel table */
.admin-table { width:100%; border-collapse:collapse; font-size:14px; }
.admin-table th {
  text-align:left; font-family:var(--font-mono);
  font-size:11px; letter-spacing:.1em;
  text-transform:uppercase; color:var(--muted);
  padding:14px 16px;
  border-bottom:1.5px solid var(--border);
  font-weight:600;
}
.admin-table td { padding:16px; border-bottom:1px solid var(--border); color:var(--fg-2); font-weight:500; }
.admin-table tr:hover td { background:var(--bg-2); }
.admin-table .name-cell { font-family:var(--font-display); font-size:16px; font-weight:700; color:var(--fg); }
.admin-table .price-cell { font-family:var(--font-mono); color:var(--accent); font-weight:700; }
.admin-table .stars-cell { color:var(--accent); }

.status-pill {
  display:inline-flex; align-items:center; gap:5px;
  padding:4px 12px; border-radius:999px;
  font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:.04em;
}
.status-pill.ok  { background:#d6f3e3; color:#137a4a; }
.status-pill.dup { background:#ffd9d2; color:#b53a2c; }

.tbl-action {
  background: var(--surface); border: 1.5px solid var(--border);
  color: var(--fg-2); padding: 5px 12px;
  border-radius: 8px; cursor: pointer;
  font-family: inherit; font-size: 12px; font-weight: 600;
  transition: all .18s; text-decoration:none; display:inline-block;
}
.tbl-action:hover { color:var(--accent); border-color:var(--accent); }
</style>
</head>
<body>

<!-- Navbar -->
<nav id="mainNavbar" class="navbar navbar-expand-lg sticky-top" aria-label="後台導覽">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="../index.php">
      <span class="logo-mark">H</span>
      飯店推薦
      <span class="logo-zh" style="color:var(--accent);border-color:var(--accent-soft);">後台管理</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a class="btn-nav-ghost" href="../index.php">
        <i class="fa-solid fa-arrow-left me-1"></i>前台首頁
      </a>
      <a class="user-chip" href="#">
        <span class="user-avatar"><?= mb_substr(e($_SESSION['username']), 0, 1) ?></span>
        <?= e($_SESSION['username']) ?>
        <span style="font-family:var(--font-mono);font-size:9px;background:var(--navy);color:#fff;padding:2px 7px;border-radius:4px;margin-left:4px;font-weight:700;">ADMIN</span>
      </a>
      <a class="btn-nav-ghost" href="../api/auth.php?action=logout">登出</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 pb-5">

  <!-- Header -->
  <div class="admin-hero">
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success alert-dismissible mt-3">
        <?= $_GET['msg'] === 'deleted' ? '飯店已刪除。' : '操作成功。' ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <span class="eyebrow">Operations Console · 營運儀表板</span>
    <h1>儀表板總覽</h1>
    <p class="lead-text">即時掌握平台資料狀態、向量嵌入進度與飯店列表。</p>
  </div>

  <!-- Stat cards -->
  <div class="admin-grid">
    <div class="stat-card">
      <div class="stat-label"><i class="fa-solid fa-hotel me-1"></i>飯店總數</div>
      <div class="stat-num"><?= $stats['hotels'] ?></div>
      <div class="stat-sub">已上架飯店</div>
    </div>
    <div class="stat-card">
      <div class="stat-label"><i class="fa-solid fa-users me-1"></i>會員總數</div>
      <div class="stat-num"><?= number_format($stats['users']) ?></div>
      <div class="stat-sub">註冊會員</div>
    </div>
    <div class="stat-card">
      <div class="stat-label"><i class="fa-solid fa-comment me-1"></i>評論累計</div>
      <div class="stat-num"><?= number_format($stats['reviews']) ?></div>
      <div class="stat-sub">旅客評論</div>
    </div>
    <div class="stat-card">
      <div class="stat-label"><i class="fa-solid fa-magnifying-glass me-1"></i>搜尋次數</div>
      <div class="stat-num"><?= number_format($stats['searches']) ?></div>
      <div class="stat-sub">AI 搜尋紀錄</div>
    </div>
  </div>

  <!-- Embed progress + Dup warning -->
  <div class="dash-row">
    <div class="panel">
      <span class="eyebrow">Vector Database</span>
      <h3 style="margin-top:8px;">向量嵌入完成度</h3>
      <p class="panel-sub">每筆飯店描述皆會生成向量供 AI 語義搜尋使用。</p>
      <div class="embed-nums">
        <span class="big"><?= $stats['vec'] ?> <span style="font-size:22px;opacity:.5;">/ <?= $stats['hotels'] ?></span></span>
        <span class="pct"><?= $vecPct ?>% 已完成</span>
      </div>
      <div class="embed-bar">
        <div class="embed-fill" id="embedFill" style="width:0%"></div>
      </div>
      <div class="d-flex justify-content-between mt-3">
        <span style="font-family:var(--font-mono);font-size:11px;color:var(--muted);">Gemini text-embedding-001</span>
        <a href="embed_hotels.php" class="tbl-action" style="font-size:12px;">
          <i class="fa-solid fa-rotate me-1"></i>重建向量
        </a>
      </div>
    </div>

    <div class="warn-panel">
      <div class="warn-head">
        <span class="warn-dot"></span>
        <h4>重複飯店警告 · <?= $stats['dup'] ?></h4>
      </div>
      <?php if ($stats['dup'] > 0): ?>
        <p style="font-size:13px;color:var(--fg-2);margin-bottom:16px;">偵測到 <strong><?= $stats['dup'] ?></strong> 筆名稱重複的飯店，建議人工確認並去重。</p>
        <a href="dedup_hotels.php" class="btn btn-warning btn-sm rounded-pill px-3">前往去重 →</a>
      <?php else: ?>
        <p style="font-size:13px;color:var(--success);font-weight:600;">
          <i class="fa-solid fa-circle-check me-1"></i>目前無重複飯店，資料品質良好。
        </p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Hotels table -->
  <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
    <div>
      <span class="eyebrow">Hotel Catalog</span>
      <h2 style="font-size:32px;margin-top:4px;">飯店管理</h2>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="dedup_hotels.php" class="tbl-action">
        <i class="fa-solid fa-copy me-1"></i>去重檢測
      </a>
      <a href="fetch_hotel_images.php" class="tbl-action">
        <i class="fa-solid fa-image me-1"></i>抓取圖片
      </a>
      <a href="hotel_edit.php" class="btn btn-primary btn-sm rounded-pill px-3">
        <i class="fa-solid fa-plus me-1"></i>新增飯店
      </a>
    </div>
  </div>

  <div class="panel" style="padding:0;overflow:hidden;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>飯店名稱</th>
            <th>地區</th>
            <th>星級</th>
            <th>每晚價格</th>
            <th>狀態</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($hotels as $h): ?>
          <tr>
            <td style="font-family:var(--font-mono);color:var(--muted);font-size:13px;"><?= $h['hotel_id'] ?></td>
            <td class="name-cell"><?= e($h['name']) ?></td>
            <td>
              <span class="region-pill">
                <i class="fa-solid fa-location-dot" style="color:var(--accent);font-size:10px;"></i>
                <?= e($h['region']) ?>
              </span>
            </td>
            <td class="stars-cell"><?= str_repeat('★', $h['stars']) ?></td>
            <td class="price-cell">NT$ <?= number_format($h['price_per_night']) ?></td>
            <td><span class="status-pill ok"><i class="fa-solid fa-circle" style="font-size:6px;"></i>公開</span></td>
            <td>
              <a href="../hotel.php?id=<?= $h['hotel_id'] ?>" class="tbl-action" style="margin-right:4px;">
                <i class="fa-solid fa-eye"></i>
              </a>
              <a href="hotel_edit.php?id=<?= $h['hotel_id'] ?>" class="tbl-action">
                <i class="fa-solid fa-pen-to-square"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* Animate embed progress bar on load */
window.addEventListener('DOMContentLoaded', function () {
  setTimeout(function () {
    document.getElementById('embedFill').style.width = '<?= $vecPct ?>%';
  }, 200);
});
/* Sticky navbar scroll shadow */
window.addEventListener('scroll', function () {
  document.getElementById('mainNavbar').classList.toggle('scrolled', window.scrollY > 50);
});
</script>
</body>
</html>
