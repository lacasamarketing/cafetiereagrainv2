/**
 * Grains de cafe SVG flottants dans le hero.
 * - 6 grains en position absolute dans le .hero (overflow:hidden donc jamais hors zone)
 * - Animation float douce + parallaxe souris desktop
 */
(function () {
  'use strict';
  function init() {
    var hero = document.querySelector('.hero');
    if (!hero) return;
    if (hero.querySelector('.bean-svg')) return; // deja init

    var positions = [
      { top: '8%',  left: '6%',  size: 28, delay: 0,    rot: -15 },
      { top: '22%', left: '88%', size: 22, delay: 1.2,  rot: 25 },
      { top: '55%', left: '4%',  size: 18, delay: 0.6,  rot: 45 },
      { top: '72%', left: '92%', size: 30, delay: 2.0,  rot: -30 },
      { top: '35%', left: '50%', size: 16, delay: 1.6,  rot: 60 },
      { top: '85%', left: '32%', size: 24, delay: 0.9,  rot: -50 },
    ];

    positions.forEach(function (p, i) {
      var bean = document.createElement('div');
      bean.className = 'bean-svg bean-svg--' + i;
      bean.style.cssText =
        'position:absolute;top:' + p.top + ';left:' + p.left +
        ';width:' + p.size + 'px;height:' + p.size + 'px;' +
        'transform:rotate(' + p.rot + 'deg);' +
        'animation:beanFloat 6s ease-in-out infinite;' +
        'animation-delay:' + p.delay + 's;' +
        'pointer-events:none;z-index:1;opacity:0.55;will-change:transform;';
      bean.innerHTML =
        '<svg viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">' +
        '<ellipse cx="16" cy="20" rx="12" ry="18" fill="#3d2418" stroke="#1a0d07" stroke-width="0.8"/>' +
        '<path d="M 16 4 Q 12 12 16 20 Q 20 28 16 36" fill="none" stroke="#c89968" stroke-width="1.6" stroke-linecap="round"/>' +
        '</svg>';
      hero.appendChild(bean);
    });

    // Inject keyframes once
    if (!document.getElementById('bean-keyframes')) {
      var style = document.createElement('style');
      style.id = 'bean-keyframes';
      style.textContent =
        '@keyframes beanFloat {' +
        '  0%,100% { transform: translate(0,0) rotate(var(--r,0deg)); }' +
        '  50% { transform: translate(8px,-12px) rotate(calc(var(--r,0deg) + 8deg)); }' +
        '}';
      document.head.appendChild(style);
    }

    // Parallaxe souris
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    var beans = hero.querySelectorAll('.bean-svg');
    var ticking = false;
    hero.addEventListener('mousemove', function (e) {
      if (ticking) return;
      window.requestAnimationFrame(function () {
        var rect = hero.getBoundingClientRect();
        var nx = (e.clientX - rect.left) / rect.width - 0.5;
        var ny = (e.clientY - rect.top) / rect.height - 0.5;
        beans.forEach(function (b, i) {
          var depth = (i + 1) * 4;
          b.style.transform = 'translate(' + (nx * depth) + 'px,' + (ny * depth) + 'px)';
        });
        ticking = false;
      });
      ticking = true;
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
