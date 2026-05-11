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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="css/style.css?v=2" rel="stylesheet">
</head>
<body>

<div class="auth-wrap">

  <!-- Art side -->
  <div class="auth-art">
    <div class="auth-art-stamp">L<br>2026</div>
    <div class="auth-art-content">
      <span class="eyebrow" style="color:rgba(255,255,255,.8)">Welcome Back</span>
      <h2 style="margin-top:14px;">回到您<br>收藏的旅宿清單。</h2>
      <p>登入後即可使用 AI 私人選房、收藏您鍾愛的旅宿，並留下您的旅程感受。</p>
    </div>
  </div>

  <!-- Form side -->
  <div class="auth-form-side">
    <div class="auth-form-box">
      <a href="index.php" style="display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;font-weight:600;margin-bottom:32px;text-decoration:none;">
        <i class="fa-solid fa-arrow-left"></i> 回首頁
      </a>

      <h1>登入</h1>
      <p class="auth-lead">使用您的會員帳號繼續。</p>

      <div id="loginError" style="display:none"></div>

      <form id="loginForm" novalidate>
        <div class="form-field">
          <label class="form-label" for="loginEmail">Email <span style="color:var(--danger)">*</span></label>
          <input id="loginEmail" type="email" name="email" class="form-control" required
                 autocomplete="email" placeholder="your@email.com">
          <div class="invalid-feedback">請輸入有效的 Email</div>
        </div>
        <div class="form-field">
          <label class="form-label" for="loginPassword">密碼 <span style="color:var(--danger)">*</span></label>
          <div class="input-group">
            <input id="loginPassword" type="password" name="password" class="form-control" required
                   autocomplete="current-password" placeholder="至少 8 個字元">
            <button type="button" class="btn btn-outline-secondary" id="togglePwd" aria-label="顯示/隱藏密碼">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <div class="invalid-feedback">請輸入密碼</div>
        </div>

        <button type="submit" class="btn-submit mt-1" id="loginSubmit">
          <i class="fa-solid fa-right-to-bracket"></i>登入
        </button>
      </form>

      <div class="auth-foot">
        還沒帳號？<a href="register.php">立即免費註冊 →</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.HOTEL_BASE = '/final';</script>
<script src="js/main.js"></script>
<script>
$('#togglePwd').on('click', function () {
  const $inp = $('#loginPassword');
  const isText = $inp.attr('type') === 'text';
  $inp.attr('type', isText ? 'password' : 'text');
  $(this).find('i').toggleClass('fa-eye fa-eye-slash');
});
</script>
</body>
</html>
