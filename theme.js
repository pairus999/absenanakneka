/**
 * theme.js — toggle dark/light mode, tersimpan di localStorage browser
 * masing-masing pengunjung. Satu file ini dipakai di semua halaman
 * (admin & publik) — taruh di folder root, admin memanggilnya lewat
 * "../theme.js".
 */
(function () {
  var KEY = 'absensi-theme';

  var ICON_SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2.2"/><path d="M12 19.3v2.2"/><path d="M4.2 4.2l1.6 1.6"/><path d="M18.2 18.2l1.6 1.6"/><path d="M2.5 12h2.2"/><path d="M19.3 12h2.2"/><path d="M4.2 19.8l1.6-1.6"/><path d="M18.2 5.8l1.6-1.6"/></svg>';
  var ICON_MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M20 14.5a8.5 8.5 0 1 1-9-11.9 7 7 0 0 0 9 11.9Z"/></svg>';

  function getSaved() {
    try { return localStorage.getItem(KEY) || 'light'; } catch (e) { return 'light'; }
  }

  function apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
  }

  // Terapkan tema tersimpan sesegera mungkin (sebelum DOM selesai) supaya
  // tidak ada "kedipan" warna terang sesaat sebelum berganti gelap.
  apply(getSaved());

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'theme-toggle-btn';
    btn.setAttribute('aria-label', 'Ganti tema gelap/terang');
    btn.style.cssText = [
      'position:fixed', 'right:20px', 'bottom:20px', 'z-index:9999',
      'width:44px', 'height:44px', 'border-radius:50%', 'border:1px solid var(--border-strong, #c9d0da)',
      'background:var(--surface, #fff)', 'color:var(--ink, #12151c)',
      'display:flex', 'align-items:center', 'justify-content:center',
      'cursor:pointer', 'box-shadow:0 8px 20px rgba(0,0,0,.18)', 'transition:transform .15s ease'
    ].join(';');

    function refreshIcon() {
      var current = document.documentElement.getAttribute('data-theme') || 'light';
      btn.innerHTML = current === 'dark' ? ICON_SUN : ICON_MOON;
    }

    btn.addEventListener('click', function () {
      var current = document.documentElement.getAttribute('data-theme') || 'light';
      var next = current === 'dark' ? 'light' : 'dark';
      apply(next);
      try { localStorage.setItem(KEY, next); } catch (e) {}
      refreshIcon();
    });
    btn.addEventListener('mouseenter', function(){ btn.style.transform = 'scale(1.08)'; });
    btn.addEventListener('mouseleave', function(){ btn.style.transform = 'scale(1)'; });

    refreshIcon();
    document.body.appendChild(btn);
  });
})();
