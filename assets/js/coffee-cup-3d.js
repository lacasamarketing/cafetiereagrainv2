/**
 * Coffee Cup 3D - Three.js
 * Tasse a cafe ceramique avec crema doree, vapeur animee et grains en orbite.
 * Container : <div id="cup-3d-container"></div>
 */
(function () {
    'use strict';
    if (typeof THREE === 'undefined') return;

    const container = document.getElementById('cup-3d-container');
    if (!container) return;

    const w = container.clientWidth || 600;
    const h = container.clientHeight || 600;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, w / h, 0.1, 100);
    camera.position.set(0, 1.5, 7);
    camera.lookAt(0, 0.5, 0);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(w, h);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xfff4e0, 0.8));
    const key = new THREE.PointLight(0xffffff, 1.8, 18); key.position.set(3, 5, 4); scene.add(key);
    const rim = new THREE.PointLight(0xffd4a0, 1.0, 12); rim.position.set(-3, 2, -1); scene.add(rim);
    const fill = new THREE.DirectionalLight(0xfff0d8, 0.4); fill.position.set(-2, 3, 4); scene.add(fill);

    const mug = new THREE.Group();
    const ceramicMat = new THREE.MeshStandardMaterial({ color: 0xf5ede0, roughness: 0.35, metalness: 0.05, side: THREE.DoubleSide });

    const cupGeo = new THREE.CylinderGeometry(1.05, 0.85, 1.6, 64, 1, true);
    mug.add(new THREE.Mesh(cupGeo, ceramicMat));

    const bottom = new THREE.Mesh(new THREE.CircleGeometry(0.85, 64), ceramicMat);
    bottom.rotation.x = -Math.PI / 2; bottom.position.y = -0.8;
    mug.add(bottom);

    const innerCup = new THREE.Mesh(cupGeo.clone(), new THREE.MeshStandardMaterial({ color: 0x3a2418, roughness: 0.7, side: THREE.BackSide }));
    innerCup.scale.set(0.97, 0.97, 0.97);
    mug.add(innerCup);

    const coffee = new THREE.Mesh(new THREE.CircleGeometry(1.0, 64), new THREE.MeshStandardMaterial({ color: 0x2a1810, roughness: 0.3, metalness: 0.3 }));
    coffee.rotation.x = -Math.PI / 2; coffee.position.y = 0.7;
    mug.add(coffee);

    const crema = new THREE.Mesh(new THREE.RingGeometry(0.65, 1.0, 64), new THREE.MeshStandardMaterial({ color: 0xc89863, roughness: 0.4, transparent: true, opacity: 0.9, emissive: 0x8b5a35, emissiveIntensity: 0.2 }));
    crema.rotation.x = -Math.PI / 2; crema.position.y = 0.71;
    mug.add(crema);

    const cremaInner = new THREE.Mesh(new THREE.CircleGeometry(0.65, 64), new THREE.MeshStandardMaterial({ color: 0x6b4a2a, roughness: 0.5, transparent: true, opacity: 0.7 }));
    cremaInner.rotation.x = -Math.PI / 2; cremaInner.position.y = 0.715;
    mug.add(cremaInner);

    const handle = new THREE.Mesh(new THREE.TorusGeometry(0.5, 0.12, 16, 32, Math.PI), ceramicMat);
    handle.position.set(1.05, 0, 0); handle.rotation.z = Math.PI / 2;
    mug.add(handle);

    const saucer = new THREE.Mesh(new THREE.CylinderGeometry(1.6, 1.5, 0.1, 64), ceramicMat);
    saucer.position.y = -0.9;
    mug.add(saucer);

    const goldRim = new THREE.Mesh(new THREE.TorusGeometry(1.05, 0.025, 12, 64), new THREE.MeshStandardMaterial({ color: 0xc89863, roughness: 0.2, metalness: 0.8 }));
    goldRim.rotation.x = Math.PI / 2; goldRim.position.y = 0.8;
    mug.add(goldRim);

    const steamParticles = [];
    for (let i = 0; i < 18; i++) {
        const p = new THREE.Mesh(
            new THREE.SphereGeometry(0.18 + Math.random() * 0.12, 12, 12),
            new THREE.MeshBasicMaterial({ color: 0x8b7560, transparent: true, opacity: 0 })
        );
        p.position.set((Math.random() - 0.5) * 0.6, 0.8 + Math.random() * 0.5, (Math.random() - 0.5) * 0.6);
        p.userData = { wobble: Math.random() * Math.PI * 2, startY: p.position.y, baseX: p.position.x, baseZ: p.position.z, delay: Math.random() * 80 };
        mug.add(p);
        steamParticles.push(p);
    }

    mug.position.y = -0.2;
    scene.add(mug);

    const beans = [];
    const beanMat = new THREE.MeshStandardMaterial({ color: 0x6b4a2a, roughness: 0.6, metalness: 0.1 });
    for (let i = 0; i < 6; i++) {
        const beanGeo = new THREE.SphereGeometry(0.15, 16, 12);
        beanGeo.scale(1, 1.4, 0.6);
        const bean = new THREE.Mesh(beanGeo, beanMat);
        const angle = (i / 6) * Math.PI * 2;
        bean.position.set(Math.cos(angle) * 2.5, -0.5 + Math.random() * 1.5, Math.sin(angle) * 2.5);
        bean.userData = { angle, speed: 0.005 + Math.random() * 0.005, baseY: bean.position.y, bobOffset: Math.random() * Math.PI * 2 };
        scene.add(bean);
        beans.push(bean);
    }

    const mouse = { x: 0, y: 0 };
    container.addEventListener('mousemove', (e) => {
        const r = container.getBoundingClientRect();
        mouse.x = ((e.clientX - r.left) / r.width - 0.5) * 2;
        mouse.y = ((e.clientY - r.top) / r.height - 0.5) * 2;
    });

    let frame = 0;
    function animate() {
        requestAnimationFrame(animate);
        frame++;
        const targetRotY = (frame * 0.003) + mouse.x * 0.5;
        const targetRotX = mouse.y * 0.15;
        mug.rotation.y += (targetRotY - mug.rotation.y) * 0.05;
        mug.rotation.x += (targetRotX - mug.rotation.x) * 0.05;
        mug.position.y = -0.2 + Math.sin(frame * 0.02) * 0.06;

        steamParticles.forEach((p) => {
            if (frame < p.userData.delay) return;
            const lifeFrame = (frame - p.userData.delay) % 200;
            const t = lifeFrame / 200;
            p.position.y = p.userData.startY + t * 2.2;
            p.position.x = p.userData.baseX + Math.sin(frame * 0.03 + p.userData.wobble) * 0.15;
            p.position.z = p.userData.baseZ + Math.cos(frame * 0.025 + p.userData.wobble) * 0.15;
            const scale = 0.6 + t * 1.8;
            p.scale.set(scale, scale, scale);
            let opacity;
            if (t < 0.15) opacity = (t / 0.15) * 0.35;
            else if (t > 0.6) opacity = ((1 - t) / 0.4) * 0.35;
            else opacity = 0.35;
            p.material.opacity = opacity;
        });

        beans.forEach((b) => {
            b.userData.angle += b.userData.speed;
            b.position.x = Math.cos(b.userData.angle) * 2.5;
            b.position.z = Math.sin(b.userData.angle) * 2.5;
            b.position.y = b.userData.baseY + Math.sin(frame * 0.03 + b.userData.bobOffset) * 0.15;
            b.rotation.x += 0.02; b.rotation.z += 0.015;
        });

        renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
        const nw = container.clientWidth, nh = container.clientHeight;
        camera.aspect = nw / nh;
        camera.updateProjectionMatrix();
        renderer.setSize(nw, nh);
    });
})();
