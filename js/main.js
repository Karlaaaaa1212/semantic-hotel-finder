/* =========================================
   飯店推薦系統 — 共用互動 JS
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

  /* ── 3. Hotel Card hover is handled purely via CSS .card-hotel:hover ── */

  /* ── 4. Favorite heart toggle (optimistic UI) ── */
  $(document).on('click', '.btn-heart', function () {
    const $btn     = $(this);
    const hotelId  = $btn.data('hotel-id');
    const $icon    = $btn.find('i');
    const isFaved  = $icon.hasClass('fa-solid');

    // Optimistic toggle
    $icon.toggleClass('fa-solid fa-regular');

    $.ajax({
      url: (window.HOTEL_BASE || '') + '/api/favorite.php',
      type: 'POST',
      data: { hotel_id: hotelId },
    }).fail(function () {
      // Revert on error
      $icon.toggleClass('fa-solid fa-regular');
      showToast('請先登入才能收藏', 'warning');
    }).done(function (res) {
      if (res.status !== 'ok') {
        $icon.toggleClass('fa-solid fa-regular');
        showToast(res.message || '操作失敗', 'danger');
      } else {
        showToast(res.favorited ? '已加入收藏 ❤️' : '已取消收藏', 'info');
      }
    });
  });

  /* ── 5. Form real-time validation ── */
  $(document).on('blur', 'input[type="email"]', function () {
    const val   = $(this).val().trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    $(this).toggleClass('is-valid', valid).toggleClass('is-invalid', !valid && val !== '');
  });
  $(document).on('blur', 'input[name="password"]', function () {
    const valid = $(this).val().length >= 8;
    $(this).toggleClass('is-valid', valid).toggleClass('is-invalid', !valid && $(this).val() !== '');
  });

  /* ── 6. Login / Register form submit ── */
  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $(this).find('[type=submit]');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 登入中…');
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
        $btn.prop('disabled', false).text('登入');
      }
    }).fail(function () {
      showFormError('#loginError', '連線失敗，請稍後再試');
      $btn.prop('disabled', false).text('登入');
    });
  });

  $('#registerForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $(this).find('[type=submit]');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 註冊中…');
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
        $btn.prop('disabled', false).text('註冊');
      }
    }).fail(function () {
      showFormError('#registerError', '連線失敗，請稍後再試');
      $btn.prop('disabled', false).text('註冊');
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

  function getApiBase() {
    return window.location.origin;
  }
});

/* ── Toast helper (global) ── */
function showToast(msg, type = 'info') {
  const id   = 'toast-' + Date.now();
  const html = `<div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2"
    role="alert" aria-live="polite" aria-atomic="true">
    <div class="d-flex"><div class="toast-body">${msg}</div>
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

/* ── Render hotel card HTML ── */
function renderHotelCard(h, isLoggedIn = false) {
  const stars   = '★'.repeat(h.stars) + '☆'.repeat(5 - h.stars);
  const fac     = (h.facilities || []).slice(0, 4).map(f =>
    `<span class="badge bg-light text-secondary border me-1">${f}</span>`).join('');
  const heart   = isLoggedIn
    ? `<button class="btn-heart ms-auto" data-hotel-id="${h.hotel_id}" aria-label="收藏">
         <i class="fa-regular fa-heart"></i></button>`
    : '';
  const score   = h.score != null
    ? `<span class="score-badge ms-auto">相似度 ${(h.score * 100).toFixed(1)}%</span>` : '';
  const summary = h.summary
    ? `<div class="summary-box mt-2">${h.summary}</div>` : '';

  return `
  <div class="col">
    <div class="card card-hotel h-100 fade-in-card">
      <img src="${h.img_url || `https://picsum.photos/seed/hotel${h.hotel_id}/600/400`}"
           class="card-img-top hotel-img-thumb"
           alt="${h.name}"
           data-full="${h.img_url || `https://picsum.photos/seed/hotel${h.hotel_id}/600/400`}"
           data-title="${h.name}"
           loading="lazy"
           onerror="this.onerror=null;this.src='https://picsum.photos/seed/hotel${h.hotel_id}/600/400';this.dataset.full='https://picsum.photos/seed/hotel${h.hotel_id}/600/400';">
      <div class="card-body d-flex flex-column">
        <div class="d-flex align-items-start gap-2 mb-1">
          <h6 class="card-title mb-0 fw-bold">${h.name}</h6>
          ${score}
        </div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge bg-secondary region-badge">${h.region}</span>
          <span class="stars">${stars}</span>
          ${heart}
        </div>
        <div class="mb-2">${fac}</div>
        <p class="card-text text-muted small flex-grow-1">${(h.description || '').slice(0, 80)}…</p>
        ${summary}
        <div class="d-flex align-items-center justify-content-between mt-3">
          <span class="price">NT$ ${Number(h.price_per_night).toLocaleString()}<small class="fw-normal text-muted"> /晚</small></span>
          <a href="hotel.php?id=${h.hotel_id}" class="btn btn-sm btn-outline-primary">查看詳情</a>
        </div>
      </div>
    </div>
  </div>`;
}

/* ── Skeleton card HTML ── */
function skeletonCard() {
  return `
  <div class="col">
    <div class="card skeleton-card placeholder-glow h-100">
      <div class="card-img-top placeholder"></div>
      <div class="card-body">
        <h6 class="placeholder col-8 mb-2"></h6>
        <p class="placeholder col-5 mb-1"></p>
        <p class="placeholder col-10 mb-1"></p>
        <p class="placeholder col-7 mb-3"></p>
        <div class="placeholder col-4 rounded" style="height:1.5rem"></div>
      </div>
    </div>
  </div>`;
}
