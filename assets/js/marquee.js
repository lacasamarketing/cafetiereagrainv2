/**
 * Marquee infini : translate continu sur les .marquee-track.
 * data-speed = vitesse desktop (px/frame) ; data-speed-mobile = vitesse mobile.
 */
(function () {
  'use strict';

  function initMarquee(track) {
    let off = 0;
    const baseSpeed = parseFloat(track.dataset.speed || '0.6');
    const mobileSpeed = parseFloat(track.dataset.speedMobile || baseSpeed);
    const isMobile = () => window.matchMedia('(max-width: 720px)').matches;
    let speed = isMobile() ? mobileSpeed : baseSpeed;

    window.addEventListener('resize', () => {
      speed = isMobile() ? mobileSpeed : baseSpeed;
    });

    function tick() {
      off -= speed;
      const half = track.scrollWidth / 2;
      if (Math.abs(off) > half) off = 0;
      track.style.transform = 'translateX(' + off + 'px)';
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  function start() {
    document.querySelectorAll('.marquee-track').forEach(initMarquee);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
