/**
 * Moustiques SVG flottants confinés au .hero — parallaxe 3D au mouvement souris.
 *
 * - 5 moustiques en position absolute dans le .hero (overflow:hidden donc jamais hors zone)
 * - Réagissent à la souris quand elle est dans le hero, wobble idle sinon
 * - Battement d'ailes CSS continu
 * - Désactivé sur mobile (touch) et avec prefers-reduced-motion
 */
(function () {
  'use strict';

  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (window.innerWidth < 900) return;

  const COUNT = 5;
  const SVG = `
<svg viewBox="0 0 60 32" xmlns="http://www.w3.org/2000/svg" width="42" height="22" overflow="visible">
  <g class="mq-wing mq-wing-l" style="transform-origin:30px 14px;">
    <ellipse cx="20" cy="8" rx="12" ry="5" fill="#1f5742" opacity="0.18"/>
    <ellipse cx="20" cy="8" rx="11" ry="4" fill="none" stroke="#1f5742" stroke-width="0.5" opacity="0.4"/>
  </g>
  <g class="mq-wing mq-wing-r" style="transform-origin:30px 14px;">
    <ellipse cx="40" cy="8" rx="12" ry="5" fill="#1f5742" opacity="0.18"/>
    <ellipse cx="40" cy="8" rx="11" ry="4" fill="none" stroke="#1f5742" stroke-width="0.5" opacity="0.4"/>
  </g>
  <ellipse cx="30" cy="14" rx="3" ry="1.6" fill="#0a1f17"/>
  <ellipse cx="30" cy="14" rx="9" ry="1.4" fill="#0a1f17"/>
  <line x1="38" y1="14" x2="46" y2="14" stroke="#0a1f17" stroke-width="1" stroke-linecap="round"/>
  <line x1="29" y1="15" x2="25" y2="22" stroke="#0a1f17" stroke-width="0.7" stroke-linecap="round"/>
  <line x1="31" y1="15" x2="35" y2="22" stroke="#0a1f17" stroke-width="0.7" stroke-linecap="round"/>
  <line x1="30" y1="15" x2="30" y2="24" stroke="#0a1f17" stroke-width="0.7" stroke-linecap="round"/>
</svg>`.trim();

  function ensureCss() {
    if (document.getElementById('mq-floating-style')) return;
    const css = document.createElement('style');
    css.id = 'mq-floating-style';
    css.textContent = `
      .mq-host { position: relative; overflow: hidden; }
      .mq-float {
        position: absolute; top: 0; left: 0;
        pointer-events: none;
        z-index: 2;
        will-change: transform;
        transition: opacity 0.6s ease;
        opacity: 0;
      }
      .mq-float.is-in { opacity: 1; }
      @keyframes mqWingBeat {
        0%,100% { transform: scaleY(1) rotate(0); }
        50% { transform: scaleY(0.4) rotate(-5deg); }
      }
      @keyframes mqWingBeatR {
        0%,100% { transform: scaleY(1) rotate(0); }
        50% { transform: scaleY(0.4) rotate(5deg); }
      }
      .mq-wing-l { animation: mqWingBeat 0.18s linear infinite; }
      .mq-wing-r { animation: mqWingBeatR 0.18s linear infinite; }
    `;
    document.head.appendChild(css);
  }

  function init() {
    const hero = document.querySelector('.hero');
    if (!hero) return;
    ensureCss();
    hero.classList.add('mq-host');

    const flies = [];
    let heroRect = hero.getBoundingClientRect();
    let mx = heroRect.width / 2, my = heroRect.height / 2;
    let inHero = false;

    function refreshRect() { heroRect = hero.getBoundingClientRect(); }
    window.addEventListener('resize', refreshRect, { passive: true });
    window.addEventListener('scroll', refreshRect, { passive: true });

    for (let i = 0; i < COUNT; i++) {
      const wrap = document.createElement('div');
      wrap.className = 'mq-float';
      wrap.innerHTML = SVG;
      const depth = 0.1 + Math.random() * 0.4;
      const scale = 0.65 + (1 - depth / 0.5) * 0.5;
      wrap.style.opacity = String(0.4 + (1 - depth / 0.5) * 0.4);
      const wingSpeed = 0.12 + Math.random() * 0.18;
      const wingL = wrap.querySelector('.mq-wing-l');
      const wingR = wrap.querySelector('.mq-wing-r');
      if (wingL) wingL.style.animationDuration = wingSpeed + 's';
      if (wingR) wingR.style.animationDuration = wingSpeed + 's';
      hero.appendChild(wrap);

      const ox = 80 + Math.random() * (hero.clientWidth - 160);
      const oy = 60 + Math.random() * (hero.clientHeight - 120);
      flies.push({
        el: wrap,
        depth,
        scale,
        x: ox, y: oy, ox, oy,
        wobblePhase: Math.random() * Math.PI * 2,
        wobbleSpeed: 0.4 + Math.random() * 0.7,
        wobbleAmpX: 30 + Math.random() * 50,
        wobbleAmpY: 20 + Math.random() * 35,
        rot: 0,
      });
    }

    setTimeout(() => flies.forEach(f => f.el.classList.add('is-in')), 100);

    function onMove(e) {
      mx = e.clientX - heroRect.left;
      my = e.clientY - heroRect.top;
      inHero = mx >= 0 && mx <= heroRect.width && my >= 0 && my <= heroRect.height;
    }
    document.addEventListener('mousemove', onMove, { passive: true });

    let lastT = performance.now();
    function loop(t) {
      const dt = Math.min(0.05, (t - lastT) / 1000);
      lastT = t;

      // Si la souris est hors hero, on prend le centre du hero comme cible
      const tx = inHero ? mx : heroRect.width / 2;
      const ty = inHero ? my : heroRect.height / 2;
      const cx = heroRect.width / 2;
      const cy = heroRect.height / 2;
      const dx = tx - cx;
      const dy = ty - cy;

      flies.forEach(f => {
        f.wobblePhase += dt * f.wobbleSpeed;
        const wx = Math.cos(f.wobblePhase) * f.wobbleAmpX;
        const wy = Math.sin(f.wobblePhase * 1.4) * f.wobbleAmpY;

        const targetX = f.ox + dx * f.depth + wx;
        const targetY = f.oy + dy * f.depth + wy;

        f.x += (targetX - f.x) * 0.1;
        f.y += (targetY - f.y) * 0.1;

        const tilt = ((mx - cx) / heroRect.width) * 14 * (1 - f.depth);

        f.el.style.transform =
          `translate3d(${f.x}px,${f.y}px,0) ` +
          `scale(${f.scale}) ` +
          `rotateY(${tilt}deg)`;
      });

      requestAnimationFrame(loop);
    }
    requestAnimationFrame(loop);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
