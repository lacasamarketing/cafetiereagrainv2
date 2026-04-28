/**
 * Curseur cible custom : point central + scope (cercles + tirets cardinaux)
 * - Désactivé sur mobile / touch (cf. media query CSS)
 * - Le scope suit avec un léger trail (lerp)
 * - Zoom + couleur lime au survol des liens / CTA / cards
 */
(function () {
  'use strict';

  // Skip mobile / touch / coarse pointer
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  if (window.innerWidth < 720) return;

  function init() {
    var dot = document.createElement('div');
    dot.className = 'cursor-dot';
    var scope = document.createElement('div');
    scope.className = 'cursor-scope';
    scope.innerHTML =
      '<svg viewBox="0 0 36 36" fill="none">' +
        '<circle cx="18" cy="18" r="16" stroke="#1f5742" stroke-width="1" opacity="0.55"/>' +
        '<circle cx="18" cy="18" r="9" stroke="#1f5742" stroke-width="1" opacity="0.8"/>' +
        '<line x1="0" y1="18" x2="6" y2="18" stroke="#1f5742" stroke-width="1.2" stroke-linecap="round"/>' +
        '<line x1="30" y1="18" x2="36" y2="18" stroke="#1f5742" stroke-width="1.2" stroke-linecap="round"/>' +
        '<line x1="18" y1="0" x2="18" y2="6" stroke="#1f5742" stroke-width="1.2" stroke-linecap="round"/>' +
        '<line x1="18" y1="30" x2="18" y2="36" stroke="#1f5742" stroke-width="1.2" stroke-linecap="round"/>' +
      '</svg>';
    document.body.appendChild(dot);
    document.body.appendChild(scope);

    var mx = window.innerWidth / 2, my = window.innerHeight / 2;
    var sx = mx, sy = my;
    var ready = false;

    function onMove(e) {
      mx = e.clientX;
      my = e.clientY;
      if (!ready) {
        ready = true;
        document.body.classList.add('cursor-ready');
      }
      dot.style.transform = 'translate3d(' + mx + 'px,' + my + 'px,0) translate(-50%,-50%)';
    }

    function loop() {
      // lerp pour le scope (effet trail)
      sx += (mx - sx) * 0.18;
      sy += (my - sy) * 0.18;
      scope.style.transform = 'translate3d(' + sx + 'px,' + sy + 'px,0) translate(-50%,-50%)';
      requestAnimationFrame(loop);
    }

    document.addEventListener('mousemove', onMove, { passive: true });
    document.addEventListener('mouseleave', function () { document.body.classList.remove('cursor-ready'); });
    document.addEventListener('mouseenter', function () { if (ready) document.body.classList.add('cursor-ready'); });

    // Hover effect
    var hoverSel = 'a, button, input[type="submit"], .product-card, .guide-card, .blog-item, .usage-tile, .process-step';
    document.querySelectorAll(hoverSel).forEach(function (el) {
      el.addEventListener('mouseenter', function () { document.body.classList.add('cursor-active'); });
      el.addEventListener('mouseleave', function () { document.body.classList.remove('cursor-active'); });
    });

    requestAnimationFrame(loop);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
