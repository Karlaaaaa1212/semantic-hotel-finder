<?php
session_start();
require_once __DIR__ . '/../db/config.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit;
}

$pdo = getPDO();

/* ── AJAX: embed a single hotel ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $hotelId = (int)($_POST['hotel_id'] ?? 0);
    if (!$hotelId) jsonResponse(['status' => 'error', 'message' => '缺少 hotel_id'], 400);

    $stmt = $pdo->prepare('SELECT hotel_id, description, facilities FROM Hotels WHERE hotel_id = ?');
    $stmt->execute([$hotelId]);
    $h = $stmt->fetch();
    if (!$h) jsonResponse(['status' => 'error', 'message' => '飯店不存在'], 404);

    $fac    = implode(' ', json_decode($h['facilities'] ?? '[]', true));
    $vector = callGeminiEmbedding($h['description'] . ' ' . $fac);
    if (empty($vector)) jsonResponse(['status' => 'error', 'message' => 'Gemini API 回傳空向量'], 500);

    $pdo->prepare('UPDATE Hotels SET embedding = ? WHERE hotel_id = ?')
        ->execute([json_encode($vector), $hotelId]);

    jsonResponse(['status' => 'ok', 'hotel_id' => $hotelId]);
}

/* ── Page data ── */
$hotels  = $pdo->query(
    'SELECT hotel_id, name, region,
            CASE WHEN embedding IS NOT NULL AND embedding != "" THEN 1 ELSE 0 END AS has_vec
     FROM Hotels ORDER BY hotel_id'
)->fetchAll();
$total   = count($hotels);
$vecDone = array_sum(array_column($hotels, 'has_vec'));
$vecPct  = $total > 0 ? round($vecDone / $total * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>向量管理 — 管理後台</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../css/style.css?v=2" rel="stylesheet">
<style>
.embed-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin: 0 0 28px;
}
@media(max-width:700px){ .embed-grid { grid-template-columns: 1fr; } }

.panel {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
}

.stat-card-lm {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px 26px;
  display: flex; align-items: center; gap: 18px;
}
.stat-card-lm.accent  { border-color: var(--accent);  background: #fff4f0; }
.stat-card-lm.success { border-color: var(--success);  background: #e8f8ef; }
.stat-card-lm.neutral { border-color: var(--border); }
.stat-card-lm .stat-icon {
  width: 48px; height: 48px;
  border-radius: var(--radius);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.stat-card-lm.accent  .stat-icon { background: #ffd9d2; color: var(--accent); }
.stat-card-lm.success .stat-icon { background: #d6f3e3; color: var(--success); }
.stat-card-lm.neutral .stat-icon { background: var(--bg-2); color: var(--navy); }
.stat-card-lm .stat-num {
  font-family: var(--font-display);
  font-size: 36px; font-weight: 800;
  letter-spacing: -.03em; line-height: 1;
}
.stat-card-lm.accent  .stat-num { color: var(--accent); }
.stat-card-lm.success .stat-num { color: var(--success); }
.stat-card-lm.neutral .stat-num { color: var(--navy); }
.stat-card-lm .stat-label {
  font-size: 12px; font-weight: 600;
  color: var(--muted); margin-top: 4px;
  text-transform: uppercase; letter-spacing: .05em;
  font-family: var(--font-mono);
}

/* Progress bar */
.vec-bar {
  height: 10px;
  background: var(--bg-2);
  border-radius: 999px;
  overflow: hidden;
  margin: 16px 0 8px;
}
.vec-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--accent), var(--accent-2));
  border-radius: 999px;
  transition: width .6s cubic-bezier(.2,.8,.3,1);
  position: relative;
}
.vec-fill::after {
  content:"";
  position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),transparent);
  animation:shine 2s infinite;
}
@keyframes shine{ from{transform:translateX(-100%)} to{transform:translateX(100%)} }

/* Rebuild panel */
.rebuild-panel {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
  margin-bottom: 28px;
}

/* progress log */
#rebuildLog {
  font-family: var(--font-mono);
  font-size: 12px;
  background: var(--navy);
  color: #c9d1e0;
  border-radius: var(--radius);
  padding: 16px 18px;
  max-height: 200px;
  overflow-y: auto;
  margin-top: 16px;
  display: none;
}
#rebuildLog .log-ok   { color: #6ee7b7; }
#rebuildLog .log-err  { color: #fca5a5; }
#rebuildLog .log-info { color: #93c5fd; }

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
.admin-table tr:hover td { background: var(--bg-2); }
.admin-table .name-cell { font-family:var(--font-display); font-size:15px; font-weight:700; color:var(--fg); }

.status-pill {
  display:inline-flex; align-items:center; gap:5px;
  padding:4px 12px; border-radius:999px;
  font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:.04em;
}
.status-pill.ok      { background:#d6f3e3; color:#137a4a; }
.status-pill.missing { background:#ffd9d2; color:#b53a2c; }

.tbl-action {
  background: var(--surface); border: 1.5px solid var(--border);
  color: var(--fg-2); padding: 5px 12px;
  border-radius: 8px; cursor: pointer;
  font-family: inherit; font-size: 12px; font-weight: 600;
  transition: all .18s;
}
.tbl-action:hover { color:var(--accent); border-color:var(--accent); }
.tbl-action:disabled { opacity:.45; cursor:not-allowed; }
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
    <span class="eyebrow">Vector Database · 向量管理</span>
    <h1>飯店向量管理</h1>
    <p class="lead-text">為飯店描述建立 Gemini 語義向量，驅動 AI 智慧搜尋功能。</p>
  </div>

  <!-- Stat cards -->
  <div class="embed-grid">
    <div class="stat-card-lm neutral">
      <div class="stat-icon"><i class="fa-solid fa-hotel"></i></div>
      <div>
        <div class="stat-num"><?= $total ?></div>
        <div class="stat-label">飯店總數</div>
      </div>
    </div>
    <div class="stat-card-lm success">
      <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
      <div>
        <div class="stat-num"><?= $vecDone ?></div>
        <div class="stat-label">已建立向量</div>
      </div>
    </div>
    <div class="stat-card-lm <?= ($total - $vecDone) > 0 ? 'accent' : 'neutral' ?>">
      <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
      <div>
        <div class="stat-num"><?= $total - $vecDone ?></div>
        <div class="stat-label">尚未建立</div>
      </div>
    </div>
  </div>

  <!-- Rebuild panel -->
  <div class="rebuild-panel mb-4">
    <span class="eyebrow">Rebuild Vectors</span>
    <h3 style="font-family:var(--font-display);font-size:22px;font-weight:700;margin:8px 0 4px;">重建向量嵌入</h3>
    <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">
      依飯店描述與設施呼叫 Gemini gemini-embedding-001，逐筆建立向量。重建期間頁面不會卡住，可即時看到進度。
    </p>

    <!-- progress bar -->
    <div class="vec-bar">
      <div class="vec-fill" id="vecFill" style="width:<?= $vecPct ?>%"></div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <span style="font-family:var(--font-mono);font-size:11px;color:var(--muted);" id="vecLabel">
        <?= $vecDone ?> / <?= $total ?> 已完成 (<?= $vecPct ?>%)
      </span>
      <span style="font-family:var(--font-mono);font-size:11px;color:var(--muted);">Gemini gemini-embedding-001</span>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <button class="btn btn-primary px-4 rounded-pill" id="btnRebuildAll">
        <i class="fa-solid fa-rotate me-2"></i>重建全部
      </button>
      <button class="btn btn-outline-secondary px-4 rounded-pill" id="btnRebuildMissing" <?= ($total - $vecDone) === 0 ? 'disabled' : '' ?>>
        <i class="fa-solid fa-plus me-2"></i>僅建立缺少的 (<?= $total - $vecDone ?>)
      </button>
    </div>

    <div id="rebuildLog"></div>
  </div>

  <!-- Hotel table -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <div>
      <span class="eyebrow">Hotel Vectors</span>
      <h2 style="font-size:28px;margin-top:4px;">各飯店向量狀態</h2>
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
            <th>向量狀態</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody id="hotelTableBody">
          <?php foreach ($hotels as $h): ?>
          <tr id="row-<?= $h['hotel_id'] ?>">
            <td style="font-family:var(--font-mono);color:var(--muted);font-size:13px;"><?= $h['hotel_id'] ?></td>
            <td class="name-cell"><?= e($h['name']) ?></td>
            <td><?= e($h['region']) ?></td>
            <td id="status-<?= $h['hotel_id'] ?>">
              <?php if ($h['has_vec']): ?>
                <span class="status-pill ok"><i class="fa-solid fa-check"></i>已建立</span>
              <?php else: ?>
                <span class="status-pill missing"><i class="fa-solid fa-xmark"></i>未建立</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="tbl-action btn-rebuild-one"
                      data-id="<?= $h['hotel_id'] ?>"
                      data-name="<?= e($h['name']) ?>">
                <i class="fa-solid fa-rotate me-1"></i>單筆重建
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <a href="dashboard.php" class="btn-nav-ghost mt-4 d-inline-flex align-items-center gap-2">
    <i class="fa-solid fa-arrow-left"></i>返回後台
  </a>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.HOTEL_BASE = '/final';

const ENDPOINT   = (window.HOTEL_BASE || '') + '/admin/embed_hotels.php';
const TOTAL      = <?= $total ?>;
let   vecDone    = <?= $vecDone ?>;
let   isRunning  = false;

/* All hotel IDs in page order */
const ALL_IDS     = <?= json_encode(array_column($hotels, 'hotel_id')) ?>;
const MISSING_IDS = <?= json_encode(array_values(array_column(array_filter($hotels, fn($h) => !$h['has_vec']), 'hotel_id'))) ?>;

/* ── Animate bar on load ── */
window.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => updateBar(vecDone), 200);
});

/* ── Navbar scroll shadow ── */
window.addEventListener('scroll', () => {
  document.getElementById('mainNavbar').classList.toggle('scrolled', window.scrollY > 50);
});

function updateBar(done) {
  const pct = TOTAL > 0 ? (done / TOTAL * 100).toFixed(1) : 0;
  $('#vecFill').css('width', pct + '%');
  $('#vecLabel').text(done + ' / ' + TOTAL + ' 已完成 (' + pct + '%)');
}

function logLine(html) {
  const $log = $('#rebuildLog');
  $log.show().append('<div>' + html + '</div>');
  $log.scrollTop($log[0].scrollHeight);
}

function setStatusCell(hotelId, ok) {
  $('#status-' + hotelId).html(ok
    ? '<span class="status-pill ok"><i class="fa-solid fa-check"></i>已建立</span>'
    : '<span class="status-pill missing"><i class="fa-solid fa-xmark"></i>未建立</span>');
}

async function embedOne(hotelId, hotelName) {
  return new Promise(resolve => {
    $.ajax({
      url: ENDPOINT,
      type: 'POST',
      data: { ajax: 1, hotel_id: hotelId },
      dataType: 'json',
    }).done(res => {
      if (res.status === 'ok') {
        setStatusCell(hotelId, true);
        resolve(true);
      } else {
        logLine('<span class="log-err">✗ #' + hotelId + ' ' + (hotelName || '') + '：' + (res.message || '失敗') + '</span>');
        resolve(false);
      }
    }).fail(() => {
      logLine('<span class="log-err">✗ #' + hotelId + ' ' + (hotelName || '') + '：連線失敗</span>');
      resolve(false);
    });
  });
}

async function runRebuild(ids) {
  if (isRunning) return;
  isRunning = true;
  $('#btnRebuildAll, #btnRebuildMissing, .btn-rebuild-one').prop('disabled', true);
  $('#rebuildLog').empty().show();

  logLine('<span class="log-info">▶ 開始重建 ' + ids.length + ' 筆向量…</span>');

  let ok = 0, fail = 0;
  for (let i = 0; i < ids.length; i++) {
    const id   = ids[i];
    const name = $('#row-' + id + ' .name-cell').text();
    logLine('<span class="log-info">處理中 (' + (i + 1) + '/' + ids.length + ')：' + name + '</span>');

    const success = await embedOne(id, name);
    if (success) {
      ok++;
      vecDone = Math.min(TOTAL, vecDone + 1);
      updateBar(vecDone);
      logLine('<span class="log-ok">✓ ' + name + ' 完成</span>');
    } else {
      fail++;
    }

    /* 250ms rate-limit pause between calls (matches PHP original) */
    if (i < ids.length - 1) await new Promise(r => setTimeout(r, 300));
  }

  logLine('<span class="log-info">── 完成：成功 ' + ok + ' 筆，失敗 ' + fail + ' 筆 ──</span>');
  isRunning = false;
  $('#btnRebuildAll, .btn-rebuild-one').prop('disabled', false);
  if (MISSING_IDS.length > 0) $('#btnRebuildMissing').prop('disabled', false);
}

$('#btnRebuildAll').on('click', () => {
  if (!confirm('確定要重建所有 ' + TOTAL + ' 筆飯店向量？這將消耗 Gemini API 額度。')) return;
  runRebuild(ALL_IDS);
});

$('#btnRebuildMissing').on('click', () => {
  const missing = [];
  $('#hotelTableBody tr').each(function () {
    if ($(this).find('.status-pill.missing').length) {
      missing.push(parseInt($(this).find('.btn-rebuild-one').data('id')));
    }
  });
  if (!missing.length) { alert('目前沒有缺少向量的飯店。'); return; }
  if (!confirm('確定要為 ' + missing.length + ' 筆缺少向量的飯店建立嵌入？')) return;
  runRebuild(missing);
});

$(document).on('click', '.btn-rebuild-one', function () {
  if (isRunning) return;
  const id   = parseInt($(this).data('id'));
  const name = $(this).data('name');
  const $btn = $(this).prop('disabled', true)
                      .html('<span class="spinner-border spinner-border-sm"></span>');
  embedOne(id, name).then(ok => {
    $btn.prop('disabled', false).html('<i class="fa-solid fa-rotate me-1"></i>單筆重建');
    if (ok) {
      vecDone = Math.min(TOTAL, vecDone + 1);
      updateBar(vecDone);
    }
  });
});
</script>
</body>
</html>
