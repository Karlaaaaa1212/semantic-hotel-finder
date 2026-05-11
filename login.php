<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>登入 — 飯店推薦系統</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="card shadow-sm border-0 rounded-3 p-4" style="width:100%;max-width:420px">
  <div class="text-center mb-4">
    <a href="index.php" class="text-decoration-none">
      <i class="fa-solid fa-hotel fa-2x text-primary mb-2 d-block"></i>
      <span class="fw-bold fs-5 text-dark">飯店推薦系統</span>
    </a>
  </div>
  <h5 class="mb-3 fw-bold">登入帳號</h5>
  <div id="loginError" style="display:none"></div>
  <form id="loginForm" novalidate>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="loginEmail">Email <span class="text-danger">*</span></label>
      <input id="loginEmail" type="email" name="email" class="form-control" required
             autocomplete="email" placeholder="your@email.com">
      <div class="invalid-feedback">請輸入有效的 Email</div>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold" for="loginPassword">密碼 <span class="text-danger">*</span></label>
      <div class="input-group">
        <input id="loginPassword" type="password" name="password" class="form-control" required
               autocomplete="current-password" placeholder="至少 8 個字元">
        <button type="button" class="btn btn-outline-secondary" id="togglePwd" aria-label="顯示/隱藏密碼">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>
      <div class="invalid-feedback">請輸入密碼</div>
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-2" id="loginSubmit">
      <i class="fa-solid fa-right-to-bracket me-1"></i>登入
    </button>
  </form>
  <p class="text-center mt-3 mb-0 text-muted small">
    還沒帳號？ <a href="register.php" class="text-primary fw-semibold">立即註冊</a>
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.HOTEL_BASE = '/final';</script>
<script src="js/main.js"></script>
<script>
// Password toggle
$('#togglePwd').on('click', function () {
  const $inp = $('#loginPassword');
  const isText = $inp.attr('type') === 'text';
  $inp.attr('type', isText ? 'password' : 'text');
  $(this).find('i').toggleClass('fa-eye fa-eye-slash');
});
</script>
</body>
</html>
