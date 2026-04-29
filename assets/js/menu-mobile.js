/**
 * Menu mobile : hamburger button + drawer slide-in + overlay.
 * En desktop la nav reste dans le header (flex horizontal).
 */
(function () {
  'use strict';
  function isMobile() { return window.matchMedia('(max-width: 767px)').matches; }
  function init() {
    var btn = document.getElementById('hamburger-btn');
    var nav = document.getElementById('main-nav');
    var overlay = document.getElementById('nav-overlay');
    if (!btn || !nav) return;

    // Move nav vers body UNIQUEMENT en mobile
    function ensurePlacement() {
      if (isMobile()) {
        if (nav.parentNode !== document.body) document.body.appendChild(nav);
        if (overlay && overlay.parentNode !== document.body) document.body.appendChild(overlay);
      } else {
        // Si on repasse en desktop : remet la nav dans le header
        var header = document.querySelector('header.site-header .container');
        if (header && nav.parentNode === document.body) {
          header.insertBefore(nav, header.querySelector('a.btn') || null);
        }
      }
    }
    ensurePlacement();
    window.addEventListener('resize', ensurePlacement);

    function open() {
      btn.setAttribute('aria-expanded', 'true');
      nav.classList.add('is-open');
      if (overlay) overlay.classList.add('is-active');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      btn.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
      if (overlay) overlay.classList.remove('is-active');
      document.body.style.overflow = '';
    }
    btn.addEventListener('click', function () {
      btn.getAttribute('aria-expanded') === 'true' ? close() : open();
    });
    if (overlay) overlay.addEventListener('click', close);
    nav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
