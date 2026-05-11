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
  padding: 32px 36px;
  max-width: 760px;
  margin: 0 auto;
}
.panel h3 {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 24px;
  letter-spacing: -.02em;
  color: var(--fg);
}

.lm-label {
  display: block;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  color: var(--fg-2);
  margin-bottom: 6px;
}
.lm-label .req { color: var(--danger); margin-left: 2px; }
.lm-hint { font-family: var(--font-mono); font-size: 11px; color: var(--muted); margin-top: 5px; }

.lm-input, .lm-select, .lm-textarea {
  width: 100%;
  background: var(--bg-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 16px;
  font-family: var(--font-body);
  font-size: 14px;
  color: var(--fg);
  transition: border-color .18s, box-shadow .18s;
  outline: none;
  appearance: none;
}
.lm-input:focus, .lm-select:focus, .lm-textarea:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-glow);
}
.lm-textarea { resize: vertical; min-height: 110px; }
.lm-file {
  width: 100%;
  background: var(--bg-2);
  border: 1.5px dashed var(--border-strong);
  border-radius: var(--radius);
  padding: 10px 14px;
  font-family: var(--font-body);
  font-size: 13px;
  color: var(--muted);
  cursor: pointer;
}
.lm-file:hover { border-color: var(--accent); }

.img-preview {
  display: inline-block;
  border-radius: var(--radius);
  border: 1.5px solid var(--border);
  overflow: hidden;
  margin-bottom: 10px;
}
.img-preview img { display: block; height: 88px; width: auto; }

.btn-primary-lm {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--accent);
  color: #fff;
  font-family: var(--font-body);
  font-size: 14px; font-weight: 700;
  border: none; border-radius: 999px;
  padding: 11px 26px;
  cursor: pointer;
  box-shadow: 0 3px 0 var(--accent-2);
  transition: transform .18s, box-shadow .18s;
  text-decoration: none;
}
.btn-primary-lm:hover { transform: translateY(-1px); box-shadow: 0 5px 0 var(--accent-2); color:#fff; }
.btn-primary-lm:active { transform: translateY(1px); box-shadow: none; }

.btn-danger-lm {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--danger);
  color: #fff;
  font-family: var(--font-body);
  font-size: 14px; font-weight: 700;
  border: none; border-radius: 999px;
  padding: 11px 22px;
  cursor: pointer;
  box-shadow: 0 3px 0 #a82e2e;
  transition: transform .18s, box-shadow .18s;
}
.btn-danger-lm:hover { transform: translateY(-1px); box-shadow: 0 5px 0 #a82e2e; }
.btn-danger-lm:active { transform: translateY(1px); box-shadow: none; }

.success-msg {
  display: flex; align-items: center; gap: 10px;
  background: #e8f8ef;
  border: 1.5px solid var(--success);
  border-radius: var(--radius);
  padding: 13px 18px;
  margin-bottom: 24px;
  font-weight: 600; font-size: 14px;
  color: #137a4a;
}
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:600px){ .form-grid { grid-template-columns: 1fr; } }
.col-full { grid-column: 1 / -1; }
.form-actions {
  display: flex; align-items: center; gap: 12px;
  padding-top: 28px;
  border-top: 1.5px solid var(--border);
  margin-top: 8px;
}
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
    <span class="eyebrow">Hotel Management · 飯店管理</span>
    <h1><?= $id ? '編輯飯店' : '新增飯店' ?></h1>
    <p class="lead-text"><?= $id ? '修改飯店基本資料、圖片與設施標籤。' : '填寫基本資料以新增一間飯店到資料庫。' ?></p>
  </div>

  <?php if ($message): ?>
    <div class="success-msg" style="max-width:760px;margin:0 auto 24px;">
      <i class="fa-solid fa-circle-check fa-lg"></i>
      <?= e($message) ?>
    </div>
  <?php endif; ?>

  <div class="panel">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="save" value="1">
      <div class="form-grid">

        <div style="grid-column:1/span 2">
          <label class="lm-label">飯店名稱 <span class="req">*</span></label>
          <input type="text" name="name" class="lm-input" required
                 value="<?= e($hotel['name'] ?? '') ?>">
        </div>

        <div>
          <label class="lm-label">地區 <span class="req">*</span></label>
          <select name="region" class="lm-select">
            <?php foreach ($regions as $r): ?>
              <option value="<?= $r ?>" <?= ($hotel['region'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="lm-label">星級 <span class="req">*</span></label>
          <select name="stars" class="lm-select">
            <?php for ($s=1; $s<=5; $s++): ?>
              <option value="<?= $s ?>" <?= ($hotel['stars'] ?? 3) == $s ? 'selected' : '' ?>><?= $s ?> 星</option>
            <?php endfor; ?>
          </select>
        </div>

        <div style="grid-column:1/span 2">
          <label class="lm-label">每晚價格（TWD）<span class="req">*</span></label>
          <input type="number" name="price_per_night" class="lm-input" min="0" required
                 value="<?= (int)($hotel['price_per_night'] ?? 2000) ?>">
        </div>

        <div style="grid-column:1/span 2">
          <label class="lm-label">描述 <span style="font-weight:400;color:var(--muted);">（語義搜尋主要依據）</span></label>
          <textarea name="description" class="lm-textarea"><?= e($hotel['description'] ?? '') ?></textarea>
        </div>

        <div style="grid-column:1/span 2">
          <label class="lm-label">設施標籤 <span style="font-weight:400;color:var(--muted);">（JSON 陣列）</span></label>
          <input type="text" name="facilities" class="lm-input"
                 value="<?= e($hotel['facilities'] ?? '[]') ?>"
                 placeholder='["溫泉","親子","游泳池"]'>
          <div class="lm-hint">格式：["設施1","設施2"]</div>
        </div>

        <div style="grid-column:1/span 2">
          <label class="lm-label">封面圖片</label>
          <?php if (!empty($hotel['img_url'])): ?>
            <div class="img-preview">
              <img src="../<?= e($hotel['img_url']) ?>" alt="現有封面">
            </div>
          <?php endif; ?>
          <input type="file" name="img" class="lm-file" accept="image/jpeg,image/png,image/webp">
          <div class="lm-hint">支援 JPG / PNG / WebP，上限 5 MB</div>
        </div>

      </div><!-- /form-grid -->

      <div class="form-actions">
        <button type="submit" class="btn-primary-lm">
          <i class="fa-solid fa-floppy-disk"></i><?= $id ? '儲存變更' : '新增飯店' ?>
        </button>
        <a href="dashboard.php" class="btn-nav-ghost">取消</a>
        <?php if ($id): ?>
          <div class="ms-auto">
            <form method="post" style="display:inline">
              <input type="hidden" name="delete_id" value="<?= $id ?>">
              <button class="btn-danger-lm"
                onclick="return confirm('確定刪除此飯店？此操作不可還原。')">
                <i class="fa-solid fa-trash"></i>刪除飯店
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </form>
  </div><!-- /panel -->

  <?php if ($id): ?>
  <div style="max-width:760px;margin:16px auto 0;display:flex;gap:10px;">
    <a href="embed_hotels.php" class="btn-nav-ghost">
      <i class="fa-solid fa-brain me-1"></i>前往重建向量
    </a>
    <a href="dashboard.php" class="btn-nav-ghost">
      <i class="fa-solid fa-arrow-left me-1"></i>返回後台
    </a>
  </div>
  <?php else: ?>
  <div style="max-width:760px;margin:16px auto 0;">
    <a href="dashboard.php" class="btn-nav-ghost">
      <i class="fa-solid fa-arrow-left me-1"></i>返回後台
    </a>
  </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
