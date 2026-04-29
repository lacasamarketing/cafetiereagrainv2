/**
 * Animations frontend :
 *  - IntersectionObserver pour fade-in / slide-in scroll
 *  - Tilt 3D leger sur .product-card
 *  - Fleches gauche/droite sur .product-hero__thumbs
 *  - Scroll progress bar en haut
 */
(function () {
  'use strict';
  function init() {
    // 1) Scroll fade-in
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            e.target.classList.add('is-in');
            io.unobserve(e.target);
          }
        });
      }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });
      document.querySelectorAll('.section, .product-card, .article-card, .category-block').forEach(function (el) {
        el.classList.add('reveal');
        io.observe(el);
      });
    }

    // 2) Tilt 3D sur les cards
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      document.querySelectorAll('[data-tilt]').forEach(function (card) {
        card.addEventListener('mousemove', function (e) {
          var rect = card.getBoundingClientRect();
          var x = (e.clientX - rect.left) / rect.width - 0.5;
          var y = (e.clientY - rect.top) / rect.height - 0.5;
          card.style.transform = 'perspective(800px) rotateY(' + (x * 6) + 'deg) rotateX(' + (-y * 6) + 'deg) translateZ(0)';
        });
        card.addEventListener('mouseleave', function () { card.style.transform = ''; });
      });
    }

    // 3) Galerie fiche produit : fleches scroll horizontal
    document.querySelectorAll('.product-hero__thumbs-wrapper').forEach(function (w) {
      var thumbs = w.querySelector('.product-hero__thumbs');
      var prev = w.querySelector('.thumbs-arrow--left');
      var next = w.querySelector('.thumbs-arrow--right');
      if (!thumbs) return;
      function update() {
        if (prev) prev.setAttribute('aria-disabled', thumbs.scrollLeft <= 4 ? 'true' : 'false');
        if (next) next.setAttribute('aria-disabled', thumbs.scrollLeft + thumbs.clientWidth >= thumbs.scrollWidth - 4 ? 'true' : 'false');
      }
      if (prev) prev.addEventListener('click', function () { thumbs.scrollBy({ left: -200, behavior: 'smooth' }); });
      if (next) next.addEventListener('click', function () { thumbs.scrollBy({ left: 200, behavior: 'smooth' }); });
      thumbs.addEventListener('scroll', update);
      update();
    });

    // 4) Galerie main image swap au clic miniature
    document.querySelectorAll('.product-hero__thumb').forEach(function (t) {
      t.addEventListener('click', function () {
        var img = document.getElementById('main-product-img');
        var src = t.getAttribute('data-img');
        if (img && src) {
          img.src = src;
          document.querySelectorAll('.product-hero__thumb').forEach(function (x) { x.classList.remove('is-active'); });
          t.classList.add('is-active');
        }
      });
    });

    // 5) Scroll progress bar
    var bar = document.createElement('div');
    bar.id = 'scroll-progress';
    bar.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,#c89968,#3d2418);width:0;z-index:99999;transition:width 80ms linear;';
    document.body.appendChild(bar);
    window.addEventListener('scroll', function () {
      var h = document.documentElement;
      var pct = (h.scrollTop / (h.scrollHeight - h.clientHeight)) * 100;
      bar.style.width = pct + '%';
    }, { passive: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
