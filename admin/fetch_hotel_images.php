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
        ? null   // didn't redirect → no image found
        : $finalUrl;
}

/* ── Process form ── */
$results = [];
$apiKey  = trim($_POST['api_key'] ?? '');
$hotelIds = $_POST['hotel_ids'] ?? [];   // empty = all hotels

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
        usleep(300000); // 0.3s delay between requests
    }
}

/* ── Load all hotels for checkbox list ── */
$allHotels = $pdo->query('SELECT hotel_id, name, region FROM Hotels ORDER BY hotel_id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<title>抓取飯店實景照片 - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body { background:#f5f7fa; }
.page-header { background:#fff; border-bottom:1px solid #dee2e6; padding:1rem 2rem; }
.card { border-radius:12px; }
.thumb { width:80px; height:54px; object-fit:cover; border-radius:6px; }
.status-ok   { color:#198754; font-weight:600; }
.status-warn { color:#fd7e14; font-weight:600; }
.status-error{ color:#dc3545; font-weight:600; }
.info-box { background:#e7f3ff; border-left:4px solid #0d6efd; padding:1rem 1.25rem; border-radius:6px; }
code { background:#f0f0f0; padding:2px 6px; border-radius:4px; font-size:.85em; }
.hotel-cb-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:.4rem; }
</style>
</head>
<body>
<div class="page-header d-flex align-items-center gap-3">
  <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← 返回管理後台</a>
  <h5 class="mb-0 fw-bold">🗺️ 抓取飯店實景照片（Google Places API）</h5>
</div>

<div class="container py-4" style="max-width:900px">

  <!-- 使用說明 -->
  <div class="info-box mb-4">
    <strong>使用說明</strong><br>
    此工具透過 <strong>Google Places API</strong> 自動搜尋每間飯店在 Google Maps 上的照片，
    並將照片 URL 寫入資料庫的 <code>img_url</code> 欄位，取代原本的佔位圖。<br><br>
    <strong>取得 API Key 步驟：</strong>
    <ol class="mb-1 mt-1">
      <li>前往 <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
      <li>建立或選擇專案 → 啟用 <strong>Places API</strong></li>
      <li>前往「憑證」→「建立憑證」→「API 金鑰」</li>
      <li>複製金鑰貼到下方表單</li>
    </ol>
    <small class="text-muted">免費額度：每月 $200 美元，40 間飯店約消耗 Text Search × 40 + Details × 40 + Photo × 40，費用遠低於 $1。</small>
  </div>

  <!-- 表單 -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold">Google Maps API Key</label>
          <input type="text" name="api_key" class="form-control font-monospace"
                 placeholder="AIzaSy..." value="<?= e($apiKey) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">
            選擇飯店
            <small class="text-muted fw-normal">（不勾選 = 更新全部飯店）</small>
          </label>
          <div class="hotel-cb-grid border rounded p-3 bg-light" style="max-height:260px;overflow-y:auto">
            <?php foreach ($allHotels as $h): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="hotel_ids[]"
                     value="<?= $h['hotel_id'] ?>" id="hcb<?= $h['hotel_id'] ?>">
              <label class="form-check-label small" for="hcb<?= $h['hotel_id'] ?>">
                <?= e($h['name']) ?>
                <span class="text-muted">(<?= e($h['region']) ?>)</span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-1">
            <button type="button" class="btn btn-link btn-sm p-0" onclick="toggleAll(true)">全選</button>
            <span class="text-muted mx-1">|</span>
            <button type="button" class="btn btn-link btn-sm p-0" onclick="toggleAll(false)">全不選</button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary px-4">
          🚀 開始抓取照片
        </button>
        <small class="text-muted ms-2">執行時間約 30–90 秒（每間飯店間隔 0.3 秒）</small>
      </form>
    </div>
  </div>

  <!-- 執行結果 -->
  <?php if (!empty($results)): ?>
  <div class="card shadow-sm">
    <div class="card-header fw-semibold">執行結果（共 <?= count($results) ?> 間）</div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:40px">#</th>
            <th>飯店名稱</th>
            <th>狀態</th>
            <th>圖片</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r): ?>
          <tr>
            <td class="text-muted small"><?= $r['id'] ?></td>
            <td><?= e($r['name']) ?></td>
            <td>
              <?php if ($r['status'] === 'ok'): ?>
                <span class="status-ok">✓ 更新成功</span>
              <?php elseif ($r['status'] === 'warn'): ?>
                <span class="status-warn">⚠ <?= e($r['msg']) ?></span>
              <?php else: ?>
                <span class="status-error">✗ <?= e($r['msg']) ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['status'] === 'ok'): ?>
                <img src="<?= e($r['url']) ?>" class="thumb" alt="preview"
                     onerror="this.style.display='none'">
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer text-muted small">
      成功：<?= count(array_filter($results, fn($r) => $r['status'] === 'ok')) ?> 間 ／
      失敗：<?= count(array_filter($results, fn($r) => $r['status'] !== 'ok')) ?> 間
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
function toggleAll(checked) {
  document.querySelectorAll('input[name="hotel_ids[]"]').forEach(cb => cb.checked = checked);
}
</script>
</body>
</html>
