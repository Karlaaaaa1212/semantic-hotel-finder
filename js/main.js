/* =========================================
   飯店推薦系統 — 共用互動 JS (LUMINA)
   ========================================= */

$(function () {

  /* ── 1. Sticky Navbar shadow on scroll ── */
  $(window).on('scroll.navbar', function () {
    $('#mainNavbar').toggleClass('scrolled', window.scrollY > 50);
  });

  /* ── 2. Scroll-to-top button ── */
  $(window).on('scroll.scrolltop', function () {
    if (window.scrollY > 300) {
      $('#scrollTop').css('display', 'flex');
    } else {
      $('#scrollTop').hide();
    }
  });
  $('#scrollTop').on('click', function () {
    $('html, body').animate({ scrollTop: 0 }, 400);
  });

  /* ── 3. Favorite button toggle (optimistic UI) ── */
  $(document).on('click', '.fav-btn', function () {
    const $btn    = $(this);
    const hotelId = $btn.data('hotel-id');
    const $icon   = $btn.find('i');
    const isFaved = $icon.hasClass('fa-solid');

    $icon.toggleClass('fa-solid fa-regular');
    $btn.toggleClass('active', !isFaved);

    $.ajax({
      url: (window.HOTEL_BASE || '') + '/api/favorite.php',
      type: 'POST',
      data: { hotel_id: hotelId },
    }).fail(function () {
      $icon.toggleClass('fa-solid fa-regular');
      $btn.toggleClass('active', isFaved);
      showToast('請先登入才能收藏', 'warning');
    }).done(function (res) {
      if (res.status !== 'ok') {
        $icon.toggleClass('fa-solid fa-regular');
        $btn.toggleClass('active', isFaved);
        showToast(res.message || '操作失敗', 'danger');
      } else {
        showToast(res.favorited ? '已加入收藏 ❤️' : '已取消收藏', 'info');
      }
    });
  });

  /* ── 4. Form real-time validation ── */
  $(document).on('blur', 'input[type="email"]', function () {
    const val   = $(this).val().trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    $(this).toggleClass('is-valid', valid).toggleClass('is-invalid', !valid && val !== '');
  });
  $(document).on('blur', 'input[name="password"]', function () {
    const valid = $(this).val().length >= 8;
    $(this).toggleClass('is-valid', valid).toggleClass('is-invalid', !valid && $(this).val() !== '');
  });

  /* ── 5. Login form submit ── */
  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $('#loginSubmit');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>登入中…');
    $.ajax({
      url: (window.HOTEL_BASE || '') + '/api/auth.php',
      type: 'POST',
      data: $(this).serialize() + '&action=login',
      dataType: 'json',
    }).done(function (res) {
      if (res.status === 'ok') {
        const dest = (window.HOTEL_BASE || '') + (res.role === 'admin' ? '/admin/dashboard.php' : '/index.php');
        window.location.href = dest;
      } else {
        showFormError('#loginError', res.message);
        $btn.prop('disabled', false).html('<i class="fa-solid fa-right-to-bracket me-1"></i>登入');
      }
    }).fail(function () {
      showFormError('#loginError', '連線失敗，請稍後再試');
      $btn.prop('disabled', false).html('<i class="fa-solid fa-right-to-bracket me-1"></i>登入');
    });
  });

  /* ── 6. Register form submit ── */
  $('#registerForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $('#registerSubmit');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>註冊中…');
    $.ajax({
      url: (window.HOTEL_BASE || '') + '/api/auth.php',
      type: 'POST',
      data: $(this).serialize() + '&action=register',
      dataType: 'json',
    }).done(function (res) {
      if (res.status === 'ok') {
        showFormError('#registerError', res.message, 'success');
        setTimeout(() => window.location.href = '/login.php', 1200);
      } else {
        showFormError('#registerError', res.message);
        $btn.prop('disabled', false).html('<i class="fa-solid fa-user-plus me-1"></i>免費註冊');
      }
    }).fail(function () {
      showFormError('#registerError', '連線失敗，請稍後再試');
      $btn.prop('disabled', false).html('<i class="fa-solid fa-user-plus me-1"></i>免費註冊');
    });
  });

  /* ── 7. Image lightbox ── */
  $(document).on('click', '.hotel-img-thumb', function () {
    const src   = $(this).data('full') || $(this).attr('src');
    const title = $(this).data('title') || '';
    $('#lightboxImg').attr('src', src);
    $('#lightboxLabel').text(title);
    new bootstrap.Modal('#lightboxModal').show();
  });

  /* ── 8. Star rating selector ── */
  $(document).on('click', '.star-select span', function () {
    const val = $(this).data('val');
    $(this).parent().find('span').each(function () {
      $(this).toggleClass('active', $(this).data('val') <= val);
    });
    $('#ratingInput').val(val);
  });

  /* ── Helpers ── */
  function showFormError(selector, msg, type = 'danger') {
    $(selector).removeClass('alert-danger alert-success')
               .addClass('alert alert-' + type)
               .text(msg).show();
  }
});

/* ── Toast helper (global) ── */
function showToast(msg, type = 'info') {
  const id   = 'toast-' + Date.now();
  const html = `<div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2"
    role="alert" aria-live="polite" aria-atomic="true">
    <div class="d-flex"><div class="toast-body fw-500">${msg}</div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div></div>`;
  let $container = $('#toastContainer');
  if (!$container.length) {
    $container = $('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1100"></div>');
    $('body').append($container);
  }
  const $toast = $(html).appendTo($container);
  const toast = new bootstrap.Toast($toast[0], { delay: 3500 });
  toast.show();
  $toast[0].addEventListener('hidden.bs.toast', () => $toast.remove());
}

/* ── Render hotel card HTML (LUMINA style) ── */
function renderHotelCard(h, isLoggedIn = false) {
  const stars = '★'.repeat(h.stars) + '☆'.repeat(5 - h.stars);
  const imgSrc = h.img_url || `https://picsum.photos/seed/hotel${h.hotel_id}/600/400`;

  const favBtn = isLoggedIn
    ? `<button class="fav-btn" data-hotel-id="${h.hotel_id}" aria-label="收藏">
         <i class="fa-regular fa-heart"></i>
       </button>`
    : '';

  const aiBadge = h.score != null
    ? `<div class="ai-badge"><span class="pulse-dot"></span>${(h.score * 100).toFixed(1)}% 相符</div>`
    : '';

  const summary = h.summary
    ? `<div class="summary-box">${h.summary}</div>` : '';

  return `
  <div class="col fade-in-card">
    <div class="card-hotel">
      <div class="img-slot">
        <img src="${imgSrc}"
             class="hotel-img-thumb"
             alt="${h.name}"
             data-full="${imgSrc}"
             data-title="${h.name}"
             loading="lazy"
             onerror="this.onerror=null;this.src='https://picsum.photos/seed/hotel${h.hotel_id}/600/400';">
        ${favBtn}
        ${aiBadge}
      </div>
      <div class="card-body">
        <div class="hotel-card-name">${h.name}</div>
        <div class="hotel-card-region">
          <i class="fa-solid fa-location-dot"></i>${h.region}
          <span class="ms-2 hotel-card-stars">${h.stars}★</span>
        </div>
        <p class="text-muted mb-3" style="font-size:13px;line-height:1.6;">${(h.description || '').slice(0, 72)}…</p>
        ${summary}
        <div class="d-flex align-items-center justify-content-between mt-auto">
          <span class="hotel-card-price">NT$ ${Number(h.price_per_night).toLocaleString()}<small>/晚</small></span>
          <a href="hotel.php?id=${h.hotel_id}" class="btn-card-detail">查看詳情</a>
        </div>
      </div>
    </div>
  </div>`;
}

/* ── Skeleton card HTML (LUMINA style) ── */
function skeletonCard() {
  return `
  <div class="col">
    <div class="card-hotel skeleton-card" style="pointer-events:none;">
      <div class="img-slot"></div>
      <div class="card-body">
        <div class="skel-line mb-2" style="width:65%;height:18px;"></div>
        <div class="skel-line mb-3" style="width:40%;height:12px;"></div>
        <div class="skel-line mb-1" style="width:90%;height:11px;"></div>
        <div class="skel-line mb-4" style="width:75%;height:11px;"></div>
        <div class="d-flex justify-content-between align-items-center">
          <div class="skel-line" style="width:38%;height:20px;"></div>
          <div class="skel-line" style="width:26%;height:32px;border-radius:999px;"></div>
        </div>
      </div>
    </div>
  </div>`;
}
