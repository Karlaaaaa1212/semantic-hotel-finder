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

$dupCount    = count(array_filter($dupRows, fn($r) => !$r['is_keeper']));
$totalHotels = $pdo->query('SELECT COUNT(*) FROM Hotels')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>重複飯店檢測 — 管理後台</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../css/style.css?v=2" rel="stylesheet">
<style>
.dedup-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin: 0 0 28px;
}
@media(max-width:700px){ .dedup-grid { grid-template-columns: 1fr; } }

.panel {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
}
.panel h3 {
  font-family: var(--font-display);
  font-size: 20px; font-weight: 700;
  margin-bottom: 4px; letter-spacing: -.02em;
}

/* Stat cards */
.stat-card-lm {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px 26px;
  display: flex; align-items: center; gap: 18px;
}
.stat-card-lm.danger { border-color: var(--danger); background: #fff5f5; }
.stat-card-lm.warning { border-color: var(--warning); background: #fff9ee; }
.stat-card-lm .stat-icon {
  width: 48px; height: 48px;
  border-radius: var(--radius);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.stat-card-lm.danger .stat-icon  { background: #ffd9d2; color: var(--danger); }
.stat-card-lm.warning .stat-icon { background: #ffecc6; color: var(--warning); }
.stat-card-lm.neutral .stat-icon { background: var(--bg-2); color: var(--navy); }
.stat-card-lm .stat-num {
  font-family: var(--font-display);
  font-size: 36px; font-weight: 800;
  letter-spacing: -.03em; line-height: 1;
}
.stat-card-lm.danger .stat-num  { color: var(--danger); }
.stat-card-lm.warning .stat-num { color: var(--warning); }
.stat-card-lm.neutral .stat-num { color: var(--navy); }
.stat-card-lm .stat-label {
  font-size: 12px; font-weight: 600;
  color: var(--muted); margin-top: 4px;
  text-transform: uppercase; letter-spacing: .05em;
  font-family: var(--font-mono);
}

/* Warning panel */
.warn-panel {
  border: 1.5px solid var(--warning);
  background: #fff8ec;
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  margin-bottom: 28px;
}
.warn-head { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
.warn-dot { width:10px; height:10px; border-radius:50%; background:var(--warning); flex-shrink:0; }
.warn-head h4 {
  font-size: 13px; font-weight: 700;
  color: var(--warning); margin: 0;
  text-transform: uppercase; letter-spacing:.06em;
}

/* Success panel */
.ok-panel {
  border: 1.5px solid var(--success);
  background: #e8f8ef;
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  display: flex; align-items: center; gap: 14px;
  font-weight: 600; color: #137a4a;
  margin-bottom: 28px;
}

/* Success message */
.success-msg {
  display: flex; align-items: center; gap: 10px;
  background: #e8f8ef;
  border: 1.5px solid var(--success);
  border-radius: var(--radius);
  padding: 13px 18px;
  margin-bottom: 24px;
  font-weight: 600; font-size: 14px; color: #137a4a;
}

/* Buttons */
.btn-danger-lm {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--danger); color: #fff;
  font-family: var(--font-body); font-size: 14px; font-weight: 700;
  border: none; border-radius: 999px; padding: 12px 28px;
  cursor: pointer; box-shadow: 0 3px 0 #a82e2e;
  transition: transform .18s, box-shadow .18s;
}
.btn-danger-lm:hover { transform: translateY(-1px); box-shadow: 0 5px 0 #a82e2e; }
.btn-danger-lm:active { transform: translateY(1px); box-shadow: none; }

/* Group cards */
.group-card {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: 16px;
}
.group-header {
  display: flex; align-items: center; gap: 10px;
  padding: 16px 20px;
  border-bottom: 1.5px solid var(--border);
  background: var(--bg-2);
}
.group-name {
  font-family: var(--font-display);
  font-size: 15px; font-weight: 700; color: var(--fg);
}
.dup-count {
  display: inline-flex; align-items: center;
  background: #ffd9d2; color: #b53a2c;
  font-family: var(--font-mono); font-size: 11px; font-weight: 700;
  padding: 3px 10px; border-radius: 999px;
  text-transform: uppercase; letter-spacing: .04em;
}

/* Admin table */
.admin-table { width:100%; border-collapse:collapse; font-size:14px; }
.admin-table th {
  text-align:left; font-family:var(--font-mono);
  font-size:11px; letter-spacing:.1em;
  text-transform:uppercase; color:var(--muted);
  padding:14px 20px;
  border-bottom:1.5px solid var(--border);
  font-weight:600;
}
.admin-table td { padding:14px 20px; border-bottom:1px solid var(--border); color:var(--fg-2); font-weight:500; }
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr.keeper td { background: #f0fbf5; }
.admin-table tr.dupe   td { background: #fff5f5; }
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

  <!-- Hero -->
  <div class="admin-hero">
    <span class="eyebrow">Data Integrity · 資料維護</span>
    <h1>重複飯店檢測</h1>
    <p class="lead-text">掃描資料庫中同名飯店，選擇一鍵清除重複項目，保留 hotel_id 最小的紀錄。</p>
  </div>

  <?php if ($message): ?>
    <div class="success-msg">
      <i class="fa-solid fa-circle-check fa-lg"></i><?= e($message) ?>
    </div>
  <?php endif; ?>

  <!-- Stat cards -->
  <div class="dedup-grid">
    <div class="stat-card-lm <?= $dupCount > 0 ? 'danger' : 'neutral' ?>">
      <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div>
        <div class="stat-num"><?= $dupCount ?></div>
        <div class="stat-label">待刪除重複筆數</div>
      </div>
    </div>
    <div class="stat-card-lm warning">
      <div class="stat-icon"><i class="fa-solid fa-object-group"></i></div>
      <div>
        <div class="stat-num"><?= count($groups) ?></div>
        <div class="stat-label">重複名稱組數</div>
      </div>
    </div>
    <div class="stat-card-lm neutral">
      <div class="stat-icon"><i class="fa-solid fa-hotel"></i></div>
      <div>
        <div class="stat-num"><?= $totalHotels ?></div>
        <div class="stat-label">目前飯店總數</div>
      </div>
    </div>
  </div>

  <?php if (empty($groups)): ?>
    <div class="ok-panel">
      <i class="fa-solid fa-circle-check fa-2x" style="color:var(--success)"></i>
      <div>
        <div style="font-size:16px;font-weight:700;">資料庫乾淨</div>
        <div style="font-size:13px;font-weight:500;color:#2a7a50;margin-top:3px;">目前沒有重複飯店紀錄。</div>
      </div>
    </div>
  <?php else: ?>

    <!-- One-click dedup -->
    <div class="warn-panel">
      <div class="warn-head">
        <div class="warn-dot"></div>
        <h4>偵測到重複資料</h4>
      </div>
      <p style="font-size:14px;color:var(--fg-2);margin-bottom:20px;">
        發現 <strong><?= count($groups) ?></strong> 組重複飯店，共 <strong><?= $dupCount ?></strong> 筆待刪除。
        每組將保留 hotel_id 最小的一筆，其餘連同收藏與評論一起移除，<strong>此操作不可還原</strong>。
      </p>
      <form method="post">
        <input type="hidden" name="dedup" value="1">
        <button class="btn-danger-lm"
                onclick="return confirm('確定刪除 <?= $dupCount ?> 筆重複飯店？\n每組保留 hotel_id 最小的一筆，此操作不可還原。')">
          <i class="fa-solid fa-trash"></i>一鍵去重（刪除 <?= $dupCount ?> 筆）
        </button>
      </form>
    </div>

    <!-- Group details -->
    <?php foreach ($groups as $name => $rows): ?>
    <div class="group-card">
      <div class="group-header">
        <i class="fa-solid fa-layer-group" style="color:var(--warning)"></i>
        <span class="group-name"><?= e($name) ?></span>
        <span class="dup-count"><?= count($rows) ?> 筆</span>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>hotel_id</th>
              <th>地區</th>
              <th>每晚</th>
              <th>星級</th>
              <th>狀態</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
            <tr class="<?= $r['is_keeper'] ? 'keeper' : 'dupe' ?>">
              <td style="font-family:var(--font-mono);font-size:13px;"><?= $r['hotel_id'] ?></td>
              <td><?= e($r['region']) ?></td>
              <td class="price-cell">NT$ <?= number_format($r['price_per_night']) ?></td>
              <td class="stars-cell"><?= str_repeat('★', $r['stars']) ?></td>
              <td>
                <?php if ($r['is_keeper']): ?>
                  <span class="status-pill ok"><i class="fa-solid fa-check"></i>保留</span>
                <?php else: ?>
                  <span class="status-pill dup"><i class="fa-solid fa-trash"></i>刪除</span>
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

  <a href="dashboard.php" class="btn-nav-ghost mt-2">
    <i class="fa-solid fa-arrow-left me-1"></i>返回後台
  </a>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
