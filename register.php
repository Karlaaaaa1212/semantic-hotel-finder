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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-wrap">

  <!-- Art side -->
  <div class="auth-art">
    <div class="auth-art-stamp">加入<br>我們</div>
    <div class="auth-art-content">
      <span class="eyebrow" style="color:rgba(255,255,255,.8)">Join Us Today</span>
      <h2 style="margin-top:14px;">開啟你的<br>精選旅宿體驗。</h2>
      <p>免費建立帳號，使用 AI 推薦找到最適合你的飯店，並收藏你心儀的旅宿。</p>
    </div>
  </div>

  <!-- Form side -->
  <div class="auth-form-side">
    <div class="auth-form-box">
      <a href="index.php" style="display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:13px;font-weight:600;margin-bottom:32px;text-decoration:none;">
        <i class="fa-solid fa-arrow-left"></i> 回首頁
      </a>

      <h1>建立帳號</h1>
      <p class="auth-lead">免費加入，立即開始使用 AI 選房。</p>

      <div id="registerError" style="display:none"></div>

      <form id="registerForm" novalidate>
        <div class="form-field">
          <label class="form-label" for="regUsername">使用者名稱 <span style="color:var(--danger)">*</span></label>
          <input id="regUsername" type="text" name="username" class="form-control" required
                 autocomplete="username" placeholder="你的暱稱">
          <div class="invalid-feedback">請輸入使用者名稱</div>
        </div>
        <div class="form-field">
          <label class="form-label" for="regEmail">Email <span style="color:var(--danger)">*</span></label>
          <input id="regEmail" type="email" name="email" class="form-control" required
                 autocomplete="email" placeholder="your@email.com">
          <div class="invalid-feedback">請輸入有效的 Email</div>
        </div>
        <div class="form-field">
          <label class="form-label" for="regPassword">密碼 <span style="color:var(--danger)">*</span></label>
          <div class="input-group">
            <input id="regPassword" type="password" name="password" class="form-control" required
                   autocomplete="new-password" placeholder="至少 8 個字元">
            <button type="button" class="btn btn-outline-secondary" id="toggleRegPwd" aria-label="顯示/隱藏密碼">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <!-- Password strength meter -->
          <div class="pwd-meter" id="pwdMeter">
            <span></span><span></span><span></span><span></span>
          </div>
          <div class="form-text" id="pwdHint">密碼至少 8 個字元</div>
          <div class="invalid-feedback">密碼至少 8 個字元</div>
        </div>

        <button type="submit" class="btn-submit mt-1" id="registerSubmit">
          <i class="fa-solid fa-user-plus"></i>免費註冊
        </button>
      </form>

      <div class="auth-foot">
        已有帳號？<a href="login.php">立即登入 →</a>
      </div>
    </div>
  </div>
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

/* Password strength meter */
$('#regPassword').on('input', function () {
  const val = $(this).val();
  const len = val.length;
  let score = 0;
  if (len >= 8)  score++;
  if (len >= 12) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9!@#$%^&*]/.test(val)) score++;

  const $meter = $('#pwdMeter');
  $meter.removeClass('pwd-s1 pwd-s2 pwd-s3 pwd-s4');
  if (len === 0) return;
  if (score <= 1) { $meter.addClass('pwd-s1'); $('#pwdHint').text('密碼強度：弱').css('color','var(--danger)'); }
  else if (score === 2) { $meter.addClass('pwd-s2'); $('#pwdHint').text('密碼強度：普通').css('color','var(--warning)'); }
  else if (score === 3) { $meter.addClass('pwd-s3'); $('#pwdHint').text('密碼強度：良好').css('color','var(--success)'); }
  else { $meter.addClass('pwd-s4'); $('#pwdHint').text('密碼強度：強').css('color','var(--accent)'); }
});
</script>
</body>
</html>
