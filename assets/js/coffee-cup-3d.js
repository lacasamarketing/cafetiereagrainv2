/**
 * Cafetière à grains 3D - Three.js vanilla.
 * Conversion du composant React Three Fiber en JS pur.
 * Cible : <canvas id="trap-canvas">.
 *
 * Lazy : on charge Three depuis CDN, on init au DOMContentLoaded.
 */

(function () {
  'use strict';

  if (window.__trapInited) return;
  window.__trapInited = true;

  const CANVAS_ID = 'trap-canvas';
  const THREE_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r158/three.min.js';

  function loadThree() {
    return new Promise((resolve, reject) => {
      if (window.THREE) return resolve(window.THREE);
      const s = document.createElement('script');
      s.src = THREE_CDN;
      s.onload = () => resolve(window.THREE);
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function init(THREE) {
    const canvas = document.getElementById(CANVAS_ID);
    if (!canvas) return;

    const wrap = canvas.parentElement;
    const W = () => wrap.clientWidth;
    const H = () => wrap.clientHeight;

    const scene = new THREE.Scene();
    scene.fog = new THREE.Fog(0x050810, 4, 14);

    const camera = new THREE.PerspectiveCamera(40, W() / H(), 0.1, 100);
    camera.position.set(0, 0.6, 5.5);

    const renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(W(), H(), false);

    // Lights
    scene.add(new THREE.AmbientLight(0x1a2238, 0.45));
    const d1 = new THREE.DirectionalLight(0xff6b9d, 0.6); d1.position.set(-3, 2, -2); scene.add(d1);
    const d2 = new THREE.DirectionalLight(0xffffff, 0.25); d2.position.set(2, 3, 4); scene.add(d2);

    // ---- Trap group ----
    const trap = new THREE.Group();
    scene.add(trap);

    // Base
    const base = new THREE.Mesh(
      new THREE.CylinderGeometry(0.78, 0.92, 0.34, 48),
      new THREE.MeshStandardMaterial({ color: 0x0c1120, roughness: 0.55, metalness: 0.4 })
    );
    base.position.y = -1.05;
    trap.add(base);

    // Ring at the base (UV emissive)
    const baseRing = new THREE.Mesh(
      new THREE.TorusGeometry(0.82, 0.012, 16, 64),
      new THREE.MeshStandardMaterial({ color: 0x5cabff, emissive: 0x2a6bb8, emissiveIntensity: 0.6 })
    );
    baseRing.position.y = -0.88;
    baseRing.rotation.x = Math.PI / 2;
    trap.add(baseRing);

    // Vertical ribs around the cage
    const ribCount = 14;
    const ribMat = new THREE.MeshStandardMaterial({ color: 0x1a2238, metalness: 0.8, roughness: 0.3 });
    for (let i = 0; i < ribCount; i++) {
      const a = (i / ribCount) * Math.PI * 2;
      const m = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, 1.7, 8), ribMat);
      m.position.set(Math.cos(a) * 0.55, 0, Math.sin(a) * 0.55);
      trap.add(m);
    }

    // Mid rings (3) - thin glowing UV
    const midMat = new THREE.MeshBasicMaterial({ color: 0x5cabff, transparent: true, opacity: 0.55 });
    [-0.55, 0, 0.55].forEach((y) => {
      const r = new THREE.Mesh(new THREE.TorusGeometry(0.55, 0.008, 12, 48), midMat);
      r.position.y = y;
      r.rotation.x = Math.PI / 2;
      trap.add(r);
    });

    // Center UV tube (pulsing)
    const tube = new THREE.Mesh(
      new THREE.CylinderGeometry(0.075, 0.075, 1.55, 24),
      new THREE.MeshBasicMaterial({ color: 0x9ed1ff })
    );
    trap.add(tube);

    // Halo
    const halo = new THREE.Mesh(
      new THREE.SphereGeometry(0.22, 24, 24),
      new THREE.MeshBasicMaterial({
        color: 0x5cabff, transparent: true, opacity: 0.18,
        blending: THREE.AdditiveBlending, depthWrite: false
      })
    );
    trap.add(halo);

    // Top dome
    const top = new THREE.Mesh(
      new THREE.CylinderGeometry(0.62, 0.55, 0.22, 48),
      new THREE.MeshStandardMaterial({ color: 0x10172a, roughness: 0.45, metalness: 0.5 })
    );
    top.position.y = 0.95;
    trap.add(top);

    // Tiny LED dot (warm pink)
    const led = new THREE.Mesh(
      new THREE.SphereGeometry(0.045, 20, 20),
      new THREE.MeshBasicMaterial({ color: 0xff6b9d })
    );
    led.position.set(0.25, 1.07, 0);
    trap.add(led);

    // UV point light
    const pl = new THREE.PointLight(0x5cabff, 4, 9, 1.6);
    pl.position.set(0, 0.4, 0);
    trap.add(pl);

    // Scale-in animation
    trap.scale.set(0, 0, 0);

    // ---- Mosquitoes (point cloud orbiting and falling into the trap) ----
    const COUNT = 80;
    const data = [];
    for (let i = 0; i < COUNT; i++) {
      data.push({
        angle: Math.random() * Math.PI * 2,
        radius: 1.6 + Math.random() * 2.2,
        height: -0.6 + Math.random() * 1.4,
        speed: 0.4 + Math.random() * 0.8,
        wobble: Math.random() * 10,
      });
    }
    const positions = new Float32Array(COUNT * 3);
    const geom = new THREE.BufferGeometry();
    geom.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const mat = new THREE.PointsMaterial({
      color: 0xf4f1eb, size: 0.028, transparent: true, opacity: 0.85,
      blending: THREE.AdditiveBlending, depthWrite: false, sizeAttenuation: true
    });
    const points = new THREE.Points(geom, mat);
    scene.add(points);

    // ---- Loop ----
    const clock = new THREE.Clock();
    let appearProg = 0;

    function animate() {
      const t = clock.getElapsedTime();
      const dt = clock.getDelta();

      // Apparition douce (1.2s ease-out)
      if (appearProg < 1) {
        appearProg = Math.min(1, appearProg + dt / 1.2);
        const ease = 1 - Math.pow(1 - appearProg, 3);
        trap.scale.setScalar(ease);
      }

      // Rotation lente
      trap.rotation.y += 0.0042;
      trap.rotation.x = Math.sin(t * 0.4) * 0.05;

      // UV tube pulse
      const p = 1 + Math.sin(t * 4) * 0.18;
      tube.scale.set(p, 1, p);

      // Light intensity pulse
      pl.intensity = 3.2 + Math.sin(t * 4) * 0.9;

      // Halo scale pulse
      const s = 0.9 + Math.sin(t * 4) * 0.12;
      halo.scale.setScalar(s);

      // Mosquitoes
      const arr = points.geometry.attributes.position.array;
      for (let i = 0; i < COUNT; i++) {
        const d = data[i];
        d.angle += d.speed * 0.012;
        d.radius -= 0.0035 * d.speed;
        if (d.radius < 0.18) {
          d.radius = 1.8 + Math.random() * 2.2;
          d.height = -0.6 + Math.random() * 1.4;
        }
        arr[i * 3]     = Math.cos(d.angle) * d.radius;
        arr[i * 3 + 1] = d.height + Math.sin(t * 3 + d.wobble) * 0.06;
        arr[i * 3 + 2] = Math.sin(d.angle) * d.radius;
      }
      points.geometry.attributes.position.needsUpdate = true;

      renderer.render(scene, camera);
      requestAnimationFrame(animate);
    }
    animate();

    // Resize
    function onResize() {
      camera.aspect = W() / H();
      camera.updateProjectionMatrix();
      renderer.setSize(W(), H(), false);
    }
    window.addEventListener('resize', onResize, { passive: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      loadThree().then(init).catch((e) => console.warn('Three.js failed to load', e));
    });
  } else {
    loadThree().then(init).catch((e) => console.warn('Three.js failed to load', e));
  }
})();
