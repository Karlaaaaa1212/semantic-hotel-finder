<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>註冊 — 飯店推薦系統</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="card shadow-sm border-0 rounded-3 p-4" style="width:100%;max-width:440px">
  <div class="text-center mb-4">
    <a href="index.php" class="text-decoration-none">
      <i class="fa-solid fa-hotel fa-2x text-primary mb-2 d-block"></i>
      <span class="fw-bold fs-5 text-dark">飯店推薦系統</span>
    </a>
  </div>
  <h5 class="mb-3 fw-bold">建立帳號</h5>
  <div id="registerError" style="display:none"></div>
  <form id="registerForm" novalidate>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="regUsername">使用者名稱 <span class="text-danger">*</span></label>
      <input id="regUsername" type="text" name="username" class="form-control" required
             autocomplete="username" placeholder="你的暱稱">
      <div class="invalid-feedback">請輸入使用者名稱</div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="regEmail">Email <span class="text-danger">*</span></label>
      <input id="regEmail" type="email" name="email" class="form-control" required
             autocomplete="email" placeholder="your@email.com">
      <div class="invalid-feedback">請輸入有效的 Email</div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="regPassword">密碼 <span class="text-danger">*</span></label>
      <div class="input-group">
        <input id="regPassword" type="password" name="password" class="form-control" required
               autocomplete="new-password" placeholder="至少 8 個字元">
        <button type="button" class="btn btn-outline-secondary" id="toggleRegPwd" aria-label="顯示/隱藏密碼">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>
      <div class="form-text">密碼至少 8 個字元</div>
      <div class="invalid-feedback">密碼至少 8 個字元</div>
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-2" id="registerSubmit">
      <i class="fa-solid fa-user-plus me-1"></i>註冊
    </button>
  </form>
  <p class="text-center mt-3 mb-0 text-muted small">
    已有帳號？ <a href="login.php" class="text-primary fw-semibold">立即登入</a>
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.HOTEL_BASE = '/final';</script>
<script src="js/main.js"></script>
<script>
$('#toggleRegPwd').on('click', function () {
  const $inp = $('#regPassword');
  const isText = $inp.attr('type') === 'text';
  $inp.attr('type', isText ? 'password' : 'text');
  $(this).find('i').toggleClass('fa-eye fa-eye-slash');
});
</script>
</body>
</html>
