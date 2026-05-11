<?php
session_start();
require_once __DIR__ . '/../db/config.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit;
}

$pdo     = getPDO();
$message = '';
$hotel   = null;
$id      = (int)($_GET['id'] ?? 0);

// Handle delete
if (isset($_POST['delete_id'])) {
    $pdo->prepare('DELETE FROM Hotels WHERE hotel_id = ?')->execute([(int)$_POST['delete_id']]);
    header('Location: dashboard.php?msg=deleted'); exit;
}

// Load existing hotel for edit
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM Hotels WHERE hotel_id = ?');
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name    = trim($_POST['name']            ?? '');
    $region  = trim($_POST['region']          ?? '');
    $price   = (int)($_POST['price_per_night'] ?? 0);
    $stars   = (int)($_POST['stars']           ?? 3);
    $desc    = trim($_POST['description']      ?? '');
    $fac     = trim($_POST['facilities']       ?? '[]');
    $imgUrl  = $hotel['img_url'] ?? 'uploads/default.jpg';

    // Image upload
    if (!empty($_FILES['img']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (in_array($_FILES['img']['type'], $allowed) && $_FILES['img']['size'] < 5 * 1024 * 1024) {
            $ext    = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
            $fname  = uniqid('hotel_') . '.' . strtolower($ext);
            $target = __DIR__ . '/../uploads/' . $fname;
            if (move_uploaded_file($_FILES['img']['tmp_name'], $target)) {
                $imgUrl = 'uploads/' . $fname;
            }
        }
    }

    if ($id) {
        $pdo->prepare('UPDATE Hotels SET name=?, region=?, price_per_night=?, stars=?, description=?, facilities=?, img_url=? WHERE hotel_id=?')
            ->execute([$name, $region, $price, $stars, $desc, $fac, $imgUrl, $id]);
        $message = '飯店資料已更新。';
        $stmt = $pdo->prepare('SELECT * FROM Hotels WHERE hotel_id = ?');
        $stmt->execute([$id]);
        $hotel = $stmt->fetch();
    } else {
        $pdo->prepare('INSERT INTO Hotels (name, region, price_per_night, stars, description, facilities, img_url) VALUES (?,?,?,?,?,?,?)')
            ->execute([$name, $region, $price, $stars, $desc, $fac, $imgUrl]);
        $newId = $pdo->lastInsertId();
        header("Location: hotel_edit.php?id={$newId}&msg=created"); exit;
    }
}

$regions = ['台北','新北','宜蘭','桃園','新竹','苗栗','台中','彰化','南投','雲林','嘉義','台南','高雄','屏東','台東','花蓮','澎湖','金門'];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id ? '編輯飯店' : '新增飯店' ?> — 管理後台</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:720px">
  <h2 class="mb-4">
    <i class="fa-solid fa-hotel me-2 text-primary"></i>
    <?= $id ? '編輯飯店' : '新增飯店' ?>
  </h2>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="save" value="1">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">飯店名稱 <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="<?= e($hotel['name'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">星級 <span class="text-danger">*</span></label>
            <select name="stars" class="form-select">
              <?php for ($s=1; $s<=5; $s++): ?>
                <option value="<?= $s ?>" <?= ($hotel['stars'] ?? 3) == $s ? 'selected' : '' ?>><?= $s ?> 星</option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">地區 <span class="text-danger">*</span></label>
            <select name="region" class="form-select">
              <?php foreach ($regions as $r): ?>
                <option value="<?= $r ?>" <?= ($hotel['region'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">每晚價格（TWD）<span class="text-danger">*</span></label>
            <input type="number" name="price_per_night" class="form-control" min="0" required
                   value="<?= (int)($hotel['price_per_night'] ?? 2000) ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">描述（語義搜尋主要依據）</label>
            <textarea name="description" class="form-control" rows="4"><?= e($hotel['description'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">設施標籤（JSON 陣列）</label>
            <input type="text" name="facilities" class="form-control"
                   value="<?= e($hotel['facilities'] ?? '[]') ?>"
                   placeholder='["溫泉","親子","游泳池"]'>
            <div class="form-text">格式：["設施1","設施2"]</div>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">封面圖片</label>
            <?php if (!empty($hotel['img_url'])): ?>
              <div class="mb-2"><img src="../<?= e($hotel['img_url']) ?>" height="80" class="rounded"></div>
            <?php endif; ?>
            <input type="file" name="img" class="form-control" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">支援 JPG / PNG / WebP，上限 5MB</div>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i><?= $id ? '儲存變更' : '新增飯店' ?>
          </button>
          <a href="dashboard.php" class="btn btn-secondary">取消</a>
          <?php if ($id): ?>
            <form method="post" class="ms-auto">
              <input type="hidden" name="delete_id" value="<?= $id ?>">
              <button class="btn btn-danger"
                onclick="return confirm('確定刪除此飯店？')">
                <i class="fa-solid fa-trash me-1"></i>刪除
              </button>
            </form>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <?php if ($id): ?>
  <div class="mt-3">
    <a href="embed_hotels.php" class="btn btn-outline-primary btn-sm">
      <i class="fa-solid fa-brain me-1"></i>前往重建向量
    </a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
