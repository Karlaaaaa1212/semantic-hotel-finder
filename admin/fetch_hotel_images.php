<?php
session_start();
require_once __DIR__ . '/../db/config.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit;
}

$pdo = getPDO();

/* ── Google Places API helpers ── */

function placesRequest(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return $body ? json_decode($body, true) : null;
}

function fetchPlaceId(string $hotelName, string $apiKey): ?string {
    $query = urlencode($hotelName . ' 台灣');
    $url   = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&language=zh-TW&key={$apiKey}";
    $data  = placesRequest($url);
    return $data['results'][0]['place_id'] ?? null;
}

function fetchPhotoReference(string $placeId, string $apiKey): ?string {
    $url  = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=photos&language=zh-TW&key={$apiKey}";
    $data = placesRequest($url);
    return $data['result']['photos'][0]['photo_reference'] ?? null;
}

function fetchPhotoUrl(string $photoRef, string $apiKey): ?string {
    $url = "https://maps.googleapis.com/maps/api/place/photo?photoreference={$photoRef}&maxwidth=800&key={$apiKey}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY         => false,
    ]);
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return (strpos($finalUrl, 'maps.googleapis.com/maps/api/place/photo') !== false)
        ? null
        : $finalUrl;
}

/* ── Process form ── */
$results = [];
$apiKey  = trim($_POST['api_key'] ?? '');
$hotelIds = $_POST['hotel_ids'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $apiKey !== '') {
    if (empty($hotelIds)) {
        $hotels = $pdo->query('SELECT hotel_id, name FROM Hotels ORDER BY hotel_id')->fetchAll();
    } else {
        $in     = implode(',', array_map('intval', $hotelIds));
        $hotels = $pdo->query("SELECT hotel_id, name FROM Hotels WHERE hotel_id IN ({$in}) ORDER BY hotel_id")->fetchAll();
    }

    foreach ($hotels as $hotel) {
        $id   = $hotel['hotel_id'];
        $name = $hotel['name'];
        $row  = ['id' => $id, 'name' => $name, 'status' => '', 'url' => ''];

        $placeId = fetchPlaceId($name, $apiKey);
        if (!$placeId) {
            $row['status'] = 'error';
            $row['msg']    = '找不到地點';
            $results[] = $row;
            usleep(300000);
            continue;
        }

        $photoRef = fetchPhotoReference($placeId, $apiKey);
        if (!$photoRef) {
            $row['status'] = 'warn';
            $row['msg']    = '無照片資料';
            $results[] = $row;
            usleep(300000);
            continue;
        }

        $photoUrl = fetchPhotoUrl($photoRef, $apiKey);
        if (!$photoUrl) {
            $row['status'] = 'warn';
            $row['msg']    = '照片 URL 解析失敗';
            $results[] = $row;
            usleep(300000);
            continue;
        }

        $pdo->prepare('UPDATE Hotels SET img_url = ? WHERE hotel_id = ?')->execute([$photoUrl, $id]);
        $row['status'] = 'ok';
        $row['url']    = $photoUrl;
        $results[] = $row;
        usleep(300000);
    }
}

/* ── Load all hotels for checkbox list ── */
$allHotels = $pdo->query('SELECT hotel_id, name, region FROM Hotels ORDER BY hotel_id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>抓取飯店實景照片 — 管理後台</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="../css/style.css?v=2" rel="stylesheet">
<style>
.panel {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 28px 32px;
  max-width: 900px;
  margin: 0 auto 24px;
}
.panel h3 {
  font-family: var(--font-display);
  font-size: 18px; font-weight: 700;
  margin-bottom: 20px; letter-spacing: -.02em;
}

/* Info panel */
.info-panel {
  max-width: 900px; margin: 0 auto 24px;
  border: 1.5px solid var(--info);
  background: #eef4ff;
  border-radius: var(--radius-lg);
  padding: 20px 26px;
}
.info-panel-head {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 12px;
  font-family: var(--font-display); font-size: 15px; font-weight: 700;
  color: var(--info);
}
.info-panel p, .info-panel ol { font-size: 14px; color: var(--fg-2); margin: 0 0 10px; }
.info-panel ol { padding-left: 20px; }
.info-panel ol li { margin-bottom: 4px; }
.info-panel small { font-family: var(--font-mono); font-size: 11px; color: var(--muted); }

/* Form controls */
.lm-label {
  display: block;
  font-size: 13px; font-weight: 600; color: var(--fg-2);
  margin-bottom: 6px;
}
.lm-label .sub { font-weight: 400; color: var(--muted); }
.lm-input {
  width: 100%;
  background: var(--bg-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 16px;
  font-family: var(--font-mono);
  font-size: 13px; color: var(--fg);
  outline: none;
  transition: border-color .18s, box-shadow .18s;
}
.lm-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-glow);
}

/* Checkbox scrollbox */
.cb-scroll-box {
  background: var(--bg-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 16px;
  max-height: 260px;
  overflow-y: auto;
}
.cb-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 6px;
}
.cb-item { display: flex; align-items: center; gap: 8px; }
.cb-item input[type=checkbox] {
  width: 16px; height: 16px;
  accent-color: var(--accent);
  cursor: pointer; flex-shrink: 0;
}
.cb-item label {
  font-size: 13px; color: var(--fg-2); cursor: pointer;
  line-height: 1.4;
}
.cb-item label .region { color: var(--muted); font-size: 12px; }
.cb-toggle-row { margin-top: 8px; display: flex; align-items: center; gap: 6px; }
.btn-text-sm {
  background: none; border: none;
  font-family: var(--font-body); font-size: 13px;
  font-weight: 600; color: var(--accent);
  cursor: pointer; padding: 0;
  text-decoration: underline; text-underline-offset: 3px;
}
.btn-text-sm:hover { color: var(--accent-2); }
.divider-text { color: var(--muted); font-size: 12px; }

/* Primary button */
.btn-primary-lm {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--accent); color: #fff;
  font-family: var(--font-body); font-size: 14px; font-weight: 700;
  border: none; border-radius: 999px; padding: 12px 28px;
  cursor: pointer; box-shadow: 0 3px 0 var(--accent-2);
  transition: transform .18s, box-shadow .18s;
}
.btn-primary-lm:hover { transform: translateY(-1px); box-shadow: 0 5px 0 var(--accent-2); }
.btn-primary-lm:active { transform: translateY(1px); box-shadow: none; }
.btn-hint { font-family: var(--font-mono); font-size: 12px; color: var(--muted); margin-left: 8px; }

/* Results */
.results-panel {
  max-width: 900px; margin: 0 auto 24px;
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.results-header {
  display: flex; align-items: center; gap: 10px;
  padding: 18px 24px;
  border-bottom: 1.5px solid var(--border);
  background: var(--bg-2);
  font-family: var(--font-display); font-size: 15px; font-weight: 700; color: var(--fg);
}

.admin-table { width:100%; border-collapse:collapse; font-size:14px; }
.admin-table th {
  text-align:left; font-family:var(--font-mono);
  font-size:11px; letter-spacing:.1em;
  text-transform:uppercase; color:var(--muted);
  padding:13px 20px;
  border-bottom:1.5px solid var(--border);
  font-weight:600;
}
.admin-table td { padding:13px 20px; border-bottom:1px solid var(--border); color:var(--fg-2); font-weight:500; vertical-align:middle; }
.admin-table tr:last-child td { border-bottom:none; }
.admin-table tr:hover td { background: var(--bg-2); }

.status-pill {
  display:inline-flex; align-items:center; gap:5px;
  padding:4px 12px; border-radius:999px;
  font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:.04em;
  font-family: var(--font-mono);
}
.status-pill.ok   { background:#d6f3e3; color:#137a4a; }
.status-pill.warn { background:#ffecc6; color:#a06800; }
.status-pill.error{ background:#ffd9d2; color:#b53a2c; }

.thumb { width:80px; height:54px; object-fit:cover; border-radius:8px; border:1.5px solid var(--border); }

.results-footer {
  padding: 14px 24px;
  border-top: 1.5px solid var(--border);
  background: var(--bg-2);
  font-family: var(--font-mono); font-size: 12px; color: var(--muted);
  display: flex; gap: 20px;
}
.results-footer .ok-count  { color: #137a4a; font-weight: 700; }
.results-footer .err-count { color: #b53a2c; font-weight: 700; }
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
    <span class="eyebrow">Image Tools · 圖片工具</span>
    <h1>抓取飯店實景照片</h1>
    <p class="lead-text">透過 Google Places API 自動搜尋每間飯店在 Google Maps 上的照片，並寫入資料庫。</p>
  </div>

  <!-- Info panel -->
  <div class="info-panel">
    <div class="info-panel-head">
      <i class="fa-solid fa-circle-info"></i>使用說明
    </div>
    <p>此工具透過 <strong>Google Places API</strong> 自動為每間飯店取得封面照片，並將照片 URL 寫入 <code style="background:var(--bg-2);padding:2px 7px;border-radius:5px;font-size:.85em;font-family:var(--font-mono);">img_url</code> 欄位。</p>
    <strong style="font-size:13px;color:var(--fg-2);">取得 API Key 步驟：</strong>
    <ol>
      <li>前往 <a href="https://console.cloud.google.com/" target="_blank" style="color:var(--info);font-weight:600;">Google Cloud Console</a></li>
      <li>建立或選擇專案 → 啟用 <strong>Places API</strong></li>
      <li>前往「憑證」→「建立憑證」→「API 金鑰」</li>
      <li>複製金鑰貼到下方表單</li>
    </ol>
    <small>免費額度：每月 $200 美元。40 間飯店約消耗 Text Search × 40 + Details × 40 + Photo × 40，費用遠低於 $1。</small>
  </div>

  <!-- Form panel -->
  <div class="panel">
    <h3><i class="fa-solid fa-map-location-dot me-2" style="color:var(--accent)"></i>設定與執行</h3>
    <form method="POST">

      <div class="mb-4">
        <label class="lm-label">Google Maps API Key</label>
        <input type="text" name="api_key" class="lm-input"
               placeholder="AIzaSy..." value="<?= e($apiKey) ?>" required>
      </div>

      <div class="mb-4">
        <label class="lm-label">
          選擇飯店
          <span class="sub">（不勾選 = 更新全部飯店）</span>
        </label>
        <div class="cb-scroll-box">
          <div class="cb-grid">
            <?php foreach ($allHotels as $h): ?>
            <div class="cb-item">
              <input type="checkbox" name="hotel_ids[]"
                     value="<?= $h['hotel_id'] ?>" id="hcb<?= $h['hotel_id'] ?>">
              <label for="hcb<?= $h['hotel_id'] ?>">
                <?= e($h['name']) ?>
                <span class="region">(<?= e($h['region']) ?>)</span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="cb-toggle-row">
          <button type="button" class="btn-text-sm" onclick="toggleAll(true)">全選</button>
          <span class="divider-text">|</span>
          <button type="button" class="btn-text-sm" onclick="toggleAll(false)">全不選</button>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn-primary-lm">
          <i class="fa-solid fa-rocket"></i>開始抓取照片
        </button>
        <span class="btn-hint">執行時間約 30–90 秒（每間飯店間隔 0.3 秒）</span>
      </div>

    </form>
  </div>

  <!-- Results -->
  <?php if (!empty($results)):
    $okCount  = count(array_filter($results, fn($r) => $r['status'] === 'ok'));
    $errCount = count($results) - $okCount;
  ?>
  <div class="results-panel">
    <div class="results-header">
      <i class="fa-solid fa-list-check" style="color:var(--accent)"></i>
      執行結果
      <span style="font-family:var(--font-mono);font-size:12px;color:var(--muted);font-weight:500;margin-left:4px;">（共 <?= count($results) ?> 間）</span>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:50px;">#</th>
            <th>飯店名稱</th>
            <th>狀態</th>
            <th>圖片預覽</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r): ?>
          <tr>
            <td style="font-family:var(--font-mono);font-size:12px;color:var(--muted);"><?= $r['id'] ?></td>
            <td style="font-family:var(--font-display);font-weight:700;color:var(--fg);"><?= e($r['name']) ?></td>
            <td>
              <?php if ($r['status'] === 'ok'): ?>
                <span class="status-pill ok"><i class="fa-solid fa-check"></i>更新成功</span>
              <?php elseif ($r['status'] === 'warn'): ?>
                <span class="status-pill warn"><i class="fa-solid fa-triangle-exclamation"></i><?= e($r['msg']) ?></span>
              <?php else: ?>
                <span class="status-pill error"><i class="fa-solid fa-xmark"></i><?= e($r['msg']) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['status'] === 'ok'): ?>
                <img src="<?= e($r['url']) ?>" class="thumb" alt="preview"
                     onerror="this.style.display='none'">
              <?php else: ?>
                <span style="color:var(--muted);font-size:13px;">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="results-footer">
      <span>成功：<span class="ok-count"><?= $okCount ?> 間</span></span>
      <span>失敗：<span class="err-count"><?= $errCount ?> 間</span></span>
    </div>
  </div>
  <?php endif; ?>

  <div style="max-width:900px;margin:0 auto;">
    <a href="dashboard.php" class="btn-nav-ghost">
      <i class="fa-solid fa-arrow-left me-1"></i>返回後台
    </a>
  </div>

</div>

<script>
function toggleAll(checked) {
  document.querySelectorAll('input[name="hotel_ids[]"]').forEach(cb => cb.checked = checked);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
