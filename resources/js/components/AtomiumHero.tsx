import { useEffect, useRef } from 'react';

/**
 * Pyramide / cône d'aluminium brossé en Three.js (SSAA + ombre réelle), réactif au survol.
 * Se place dans un parent qui a une hauteur (ex. `h-[480px]`). Compatible Inertia SSR :
 * three est chargé dynamiquement dans useEffect, donc uniquement côté navigateur.
 *
 * Props
 *  - shape      "pyramid" | "cone"
 *  - ssaa       -1 (désactivé), 2 (4 échantillons), 4 (16 échantillons)
 *  - background couleur du fond de la scène (ex. "#020617"). Sans valeur : fond transparent
 *               (le halo/bloom est alors désactivé pour éviter les artefacts).
 *
 * Prérequis : npm install three   (version r160 ou plus récente)
 */
export default function AtomiumHero({
    shape = 'pyramid',
    ssaa = 2,
    background = null,
    className = '',
}) {
    const ref = useRef(null);

    useEffect(() => {
        let cancelled = false;
        let stop = () => {};

        (async () => {
            const [
                THREE,
                { EffectComposer },
                { RenderPass },
                { SSAARenderPass },
                { UnrealBloomPass },
                { OutputPass },
            ] = await Promise.all([
                import('three'),
                import('three/examples/jsm/postprocessing/EffectComposer.js'),
                import('three/examples/jsm/postprocessing/RenderPass.js'),
                import('three/examples/jsm/postprocessing/SSAARenderPass.js'),
                import('three/examples/jsm/postprocessing/UnrealBloomPass.js'),
                import('three/examples/jsm/postprocessing/OutputPass.js'),
            ]);
            if (cancelled || !ref.current) return;
            stop = start(
                ref.current,
                {
                    THREE,
                    EffectComposer,
                    RenderPass,
                    SSAARenderPass,
                    UnrealBloomPass,
                    OutputPass,
                },
                { shape, ssaa, background },
            );
        })();

        return () => {
            cancelled = true;
            stop();
        };
    }, [shape, ssaa, background]);

    return (
        <div
            ref={ref}
            className={`relative h-full w-full ${className}`}
            aria-hidden="true"
        />
    );
}

function start(container, L, { shape, ssaa, background }) {
    const {
        THREE,
        EffectComposer,
        RenderPass,
        SSAARenderPass,
        UnrealBloomPass,
        OutputPass,
    } = L;
    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ---------- Rendu ----------
    const renderer = new THREE.WebGLRenderer({
        antialias: false,
        alpha: !background,
        powerPreference: 'high-performance',
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    Object.assign(renderer.domElement.style, {
        position: 'absolute',
        inset: '0',
        width: '100%',
        height: '100%',
        display: 'block',
    });
    container.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    if (background) scene.background = new THREE.Color(background);
    const camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
    camera.position.set(0, 1.2, 13.5);
    camera.lookAt(0, 0, 0);

    // ---------- Environnement (ciel, nuages, soleil, sol) pour les reflets du métal ----------
    (function () {
        const c = document.createElement('canvas');
        c.width = 512;
        c.height = 256;
        const x = c.getContext('2d');
        const g = x.createLinearGradient(0, 0, 0, 256);
        g.addColorStop(0, '#3f7cc4');
        g.addColorStop(0.3, '#8fbbe3');
        g.addColorStop(0.49, '#dbe8f1');
        g.addColorStop(0.5, '#9a9488');
        g.addColorStop(0.7, '#55524b');
        g.addColorStop(1, '#2a2926');
        x.fillStyle = g;
        x.fillRect(0, 0, 512, 256);
        [
            [90, 50, 60],
            [230, 80, 70],
            [380, 40, 55],
            [470, 90, 45],
            [20, 95, 40],
        ].forEach(([cx, cy, r]) => {
            const cg = x.createRadialGradient(cx, cy, 0, cx, cy, r);
            cg.addColorStop(0, 'rgba(255,255,255,.85)');
            cg.addColorStop(1, 'rgba(255,255,255,0)');
            x.fillStyle = cg;
            x.fillRect(cx - r, cy - r, r * 2, r * 2);
        });
        const sun = x.createRadialGradient(150, 55, 0, 150, 55, 38);
        sun.addColorStop(0, '#ffffff');
        sun.addColorStop(0.25, '#fff0cf');
        sun.addColorStop(1, 'rgba(255,230,180,0)');
        x.fillStyle = sun;
        x.fillRect(100, 5, 100, 100);
        const tex = new THREE.CanvasTexture(c);
        tex.mapping = THREE.EquirectangularReflectionMapping;
        tex.colorSpace = THREE.SRGBColorSpace;
        const pm = new THREE.PMREMGenerator(renderer);
        scene.environment = pm.fromEquirectangular(tex).texture;
        tex.dispose();
        pm.dispose();
    })();

    // Intensités plus fortes qu'en Three r147 (éclairage physique depuis r155)
    const key = new THREE.DirectionalLight(0xfff0dc, 4.5);
    key.position.set(5, 9, 6);
    key.castShadow = true;
    key.shadow.mapSize.set(2048, 2048);
    Object.assign(key.shadow.camera, {
        left: -8,
        right: 8,
        top: 8,
        bottom: -8,
        near: 1,
        far: 30,
    });
    key.shadow.camera.updateProjectionMatrix();
    key.shadow.bias = -0.0004;
    key.shadow.radius = 4;
    scene.add(key, new THREE.HemisphereLight(0xcfe3f5, 0x6b6558, 1.1));

    // ---------- Structure ----------
    const tilt = new THREE.Group(),
        spin = new THREE.Group(),
        atom = new THREE.Group();
    scene.add(tilt);
    tilt.add(spin);
    spin.add(atom);

    function brushed(vertical) {
        const c = document.createElement('canvas');
        c.width = c.height = 256;
        const x = c.getContext('2d');
        x.fillStyle = '#454545';
        x.fillRect(0, 0, 256, 256);
        for (let i = 0; i < 1800; i++) {
            const v = (50 + Math.random() * 70) | 0;
            x.fillStyle = `rgba(${v},${v},${v},${0.25 + Math.random() * 0.4})`;
            const p = Math.random() * 256,
                len = 40 + Math.random() * 216,
                off = Math.random() * 256;
            if (vertical) x.fillRect(p, off, 1, len);
            else x.fillRect(off, p, len, 1);
        }
        const t = new THREE.CanvasTexture(c);
        t.wrapS = t.wrapT = THREE.RepeatWrapping;
        t.anisotropy = renderer.capabilities.getMaxAnisotropy();
        return t;
    }
    const texSph = brushed(false),
        texTube = brushed(true);
    texSph.repeat.set(2, 1);

    const sphColor = (i) =>
        i === 0
            ? new THREE.Color('#d9a82e')
            : new THREE.Color().setHSL(
                  (i * 0.618) % 1,
                  0.62,
                  0.52,
                  THREE.SRGBColorSpace,
              );

    const sphGeo = new THREE.SphereGeometry(0.5, 64, 48);
    const tubeGeo = new THREE.CylinderGeometry(0.1, 0.1, 1, 24, 1, true);
    const tubeMat = new THREE.MeshPhysicalMaterial({
        color: new THREE.Color('#b9bec3'),
        metalness: 1,
        roughness: 1,
        roughnessMap: texTube,
        bumpMap: texTube,
        bumpScale: 0.25,
        clearcoat: 0.2,
        clearcoatRoughness: 0.2,
        envMapIntensity: 1.3,
    });

    // 3 niveaux : couches de plus en plus larges vers le bas, et liaisons entre sphères
    function layout() {
        const N = 3,
            H = 1.9,
            sp = 1.7,
            pts = [],
            pairs = [],
            layers = [];
        for (let k = 0; k < N; k++) {
            const arr = [];
            if (shape === 'pyramid') {
                const n = k + 1;
                for (let i = 0; i < n; i++)
                    for (let j = 0; j < n; j++) {
                        arr.push(pts.length);
                        pts.push(
                            new THREE.Vector3(
                                (i - k / 2) * sp,
                                -k * H,
                                (j - k / 2) * sp,
                            ),
                        );
                    }
            } else {
                const c = k ? 6 * k : 1;
                for (let i = 0; i < c; i++) {
                    const a = (i / c) * Math.PI * 2;
                    arr.push(pts.length);
                    pts.push(
                        new THREE.Vector3(
                            Math.cos(a) * k * 1.15,
                            -k * H,
                            Math.sin(a) * k * 1.15,
                        ),
                    );
                }
            }
            layers.push(arr);
        }
        layers.forEach((arr, k) => {
            if (shape === 'pyramid') {
                const n = k + 1,
                    m = n + 1;
                for (let i = 0; i < n; i++)
                    for (let j = 0; j < n; j++) {
                        const id = arr[i * n + j];
                        if (i + 1 < n) pairs.push([id, arr[(i + 1) * n + j]]);
                        if (j + 1 < n) pairs.push([id, arr[i * n + j + 1]]);
                        if (k + 1 < N)
                            [
                                [0, 0],
                                [1, 0],
                                [0, 1],
                                [1, 1],
                            ].forEach(([di, dj]) =>
                                pairs.push([
                                    id,
                                    layers[k + 1][(i + di) * m + j + dj],
                                ]),
                            );
                    }
            } else {
                const cnt = arr.length;
                if (cnt > 1)
                    arr.forEach((id, i) =>
                        pairs.push([id, arr[(i + 1) % cnt]]),
                    );
                if (k + 1 < N) {
                    const next = layers[k + 1],
                        cn = next.length;
                    arr.forEach((id, i) => {
                        const f = cnt === 1 ? 0 : i / cnt;
                        const order = next
                            .map((nid, j) => ({
                                nid,
                                d: Math.abs(((j / cn - f + 1.5) % 1) - 0.5),
                            }))
                            .sort((a, b) => a.d - b.d);
                        (k === 0 ? order : order.slice(0, 3)).forEach((o) =>
                            pairs.push([id, o.nid]),
                        );
                    });
                }
            }
        });
        const off = ((N - 1) * H) / 2;
        pts.forEach((p) => {
            p.y += off;
        });
        return { pts, pairs };
    }

    let base = [],
        spheres = [],
        tubes = [],
        hitList = [],
        groundY = -2.9;
    (function build() {
        const { pts, pairs } = layout();
        base = pts;
        // Posée sur le côté : l'arête (pyramide) ou la génératrice (cône) partant du sommet est au sol
        const tip = pts[shape === 'pyramid' ? 5 : 7]
            .clone()
            .sub(pts[0])
            .normalize();
        const down = new THREE.Vector3(0, -1, 0);
        const upv = down
            .clone()
            .addScaledVector(tip, -down.dot(tip))
            .normalize();
        const side = new THREE.Vector3().crossVectors(tip, upv);
        atom.quaternion.setFromRotationMatrix(
            new THREE.Matrix4().set(
                tip.x,
                tip.y,
                tip.z,
                0,
                upv.x,
                upv.y,
                upv.z,
                0,
                side.x,
                side.y,
                side.z,
                0,
                0,
                0,
                0,
                1,
            ),
        );
        const box = new THREE.Box3().setFromPoints(
            pts.map((p) => p.clone().applyQuaternion(atom.quaternion)),
        );
        const mid = box.getCenter(new THREE.Vector3());
        atom.position.copy(mid).negate();
        groundY = box.min.y - mid.y - 0.55;

        spheres = pts.map((p, i) => {
            const k = i === 0 ? 1.5 : 1;
            const m = new THREE.Mesh(
                sphGeo,
                new THREE.MeshPhysicalMaterial({
                    color: sphColor(i),
                    metalness: 1,
                    roughness: 1,
                    roughnessMap: texSph,
                    bumpMap: texSph,
                    bumpScale: 0.25,
                    clearcoat: 0.3,
                    clearcoatRoughness: 0.12,
                    envMapIntensity: 1.4,
                    emissive: sphColor(i),
                    emissiveIntensity: 0,
                }),
            );
            m.userData = { i, s: k };
            m.castShadow = true;
            m.scale.setScalar(k);
            m.position.copy(p);
            atom.add(m);
            return m;
        });
        tubes = pairs.map(([a, b]) => {
            const m = new THREE.Mesh(tubeGeo, tubeMat);
            m.castShadow = true;
            m.quaternion.setFromUnitVectors(
                new THREE.Vector3(0, 1, 0),
                base[b].clone().sub(base[a]).normalize(),
            );
            atom.add(m);
            return { m, a, b };
        });
        hitList = [...spheres, ...tubes.map((t) => t.m)];
    })();

    // Ombre douce + ombre portée réelle sur le sol
    const sc = document.createElement('canvas');
    sc.width = sc.height = 128;
    const sx = sc.getContext('2d');
    const sg = sx.createRadialGradient(64, 64, 0, 64, 64, 64);
    sg.addColorStop(0, 'rgba(0,0,0,.55)');
    sg.addColorStop(1, 'rgba(0,0,0,0)');
    sx.fillStyle = sg;
    sx.fillRect(0, 0, 128, 128);
    const blobTex = new THREE.CanvasTexture(sc);
    blobTex.colorSpace = THREE.SRGBColorSpace;
    const blob = new THREE.Mesh(
        new THREE.PlaneGeometry(8, 8),
        new THREE.MeshBasicMaterial({
            map: blobTex,
            transparent: true,
            depthWrite: false,
        }),
    );
    blob.rotation.x = -Math.PI / 2;
    const catcher = new THREE.Mesh(
        new THREE.PlaneGeometry(40, 40),
        new THREE.ShadowMaterial({ opacity: 0.3 }),
    );
    catcher.rotation.x = -Math.PI / 2;
    catcher.receiveShadow = true;
    scene.add(blob, catcher);

    // ---------- Post-traitement ----------
    const composer = new EffectComposer(renderer);
    if (ssaa >= 0) {
        const p = new SSAARenderPass(scene, camera, 0x000000, 0);
        p.sampleLevel = ssaa;
        composer.addPass(p);
    } else {
        composer.addPass(new RenderPass(scene, camera));
    }
    if (background)
        composer.addPass(
            new UnrealBloomPass(new THREE.Vector2(1, 1), 0.15, 0.6, 0.92),
        );
    composer.addPass(new OutputPass());

    function resize() {
        const w = container.clientWidth,
            h = container.clientHeight;
        if (!w || !h) return;
        renderer.setSize(w, h, false);
        composer.setPixelRatio(renderer.getPixelRatio());
        composer.setSize(w, h);
        camera.aspect = w / h;
        camera.fov = w / h < 0.8 ? 52 : 38;
        camera.position.z = w / h < 0.8 ? 17.5 : 13.5;
        camera.updateProjectionMatrix();
    }
    const ro = new ResizeObserver(resize);
    ro.observe(container);
    resize();

    // ---------- Interaction (écoute la fenêtre : fonctionne même sous du contenu) ----------
    const ray = new THREE.Raycaster(),
        ndc = new THREE.Vector2(9, 9);
    let hover = false,
        hoveredI = -1,
        last = null,
        spinVel = 0,
        explode = 0,
        tiltX = 0,
        tiltZ = 0,
        visible = true;

    const onMove = (e) => {
        const b = container.getBoundingClientRect();
        if (
            e.clientX < b.left ||
            e.clientX > b.right ||
            e.clientY < b.top ||
            e.clientY > b.bottom
        ) {
            hover = false;
            hoveredI = -1;
            last = null;
            return;
        }
        ndc.set(
            ((e.clientX - b.left) / b.width) * 2 - 1,
            -((e.clientY - b.top) / b.height) * 2 + 1,
        );
        const dx = last ? e.clientX - last.x : 0,
            dy = last ? e.clientY - last.y : 0;
        last = { x: e.clientX, y: e.clientY };
        ray.setFromCamera(ndc, camera);
        const hit = ray.intersectObjects(hitList, false)[0];
        hover = !!hit;
        hoveredI =
            hit && hit.object.userData.i !== undefined
                ? hit.object.userData.i
                : -1;
        if (hover)
            spinVel = THREE.MathUtils.clamp(
                spinVel + (dx + dy * 0.4) * 0.0003,
                -0.02,
                0.02,
            );
    };
    window.addEventListener('pointermove', onMove, { passive: true });

    // Pause le rendu quand le hero n'est plus visible
    const io = new IntersectionObserver(([en]) => {
        visible = en.isIntersecting;
    });
    io.observe(container);

    // ---------- Animation ----------
    const t0 = performance.now();
    let raf = 0;
    const frame = () => {
        raf = requestAnimationFrame(frame);
        if (!visible) return;
        const t = (performance.now() - t0) / 1000;
        explode += ((hover ? 0.28 : 0) - explode) * 0.025;
        tiltX += ((hover ? -ndc.y * 0.35 : 0) - tiltX) * 0.02;
        tiltZ += ((hover ? -ndc.x * 0.15 : 0) - tiltZ) * 0.02;
        spinVel *= 0.975;
        spin.rotation.y += (calm ? 0 : 0.0007) + spinVel;
        tilt.rotation.set(0.16 + tiltX, 0, tiltZ);
        tilt.position.y = Math.sin(t * 0.35) * 0.12;
        blob.position.y = groundY;
        catcher.position.y = groundY + 0.01;

        spheres.forEach((m, i) => {
            // à l'ouverture, les couches s'éloignent du sommet
            m.position
                .copy(base[i])
                .sub(base[0])
                .multiplyScalar(1 + explode)
                .add(base[0]);
            const target =
                (i === 0 ? 1.5 : 1) * (i === hoveredI ? 1.28 : 1) +
                Math.sin(t * 0.8 + i) * 0.02;
            m.userData.s += (target - m.userData.s) * 0.05;
            m.scale.setScalar(m.userData.s);
            m.material.emissiveIntensity +=
                ((i === hoveredI ? 0.45 : 0) - m.material.emissiveIntensity) *
                0.05;
        });
        tubes.forEach(({ m, a, b }) => {
            const pa = spheres[a].position,
                pb = spheres[b].position;
            m.position.addVectors(pa, pb).multiplyScalar(0.5);
            m.scale.set(1, pa.distanceTo(pb), 1);
        });
        composer.render();
    };
    frame();

    // ---------- Nettoyage ----------
    return () => {
        cancelAnimationFrame(raf);
        window.removeEventListener('pointermove', onMove);
        ro.disconnect();
        io.disconnect();
        scene.traverse((o) => {
            if (o.geometry) o.geometry.dispose();
            const ms = Array.isArray(o.material)
                ? o.material
                : o.material
                  ? [o.material]
                  : [];
            ms.forEach((m) => {
                ['map', 'roughnessMap', 'bumpMap'].forEach(
                    (k) => m[k] && m[k].dispose(),
                );
                m.dispose();
            });
        });
        if (scene.environment) scene.environment.dispose();
        composer.dispose();
        renderer.dispose();
        renderer.domElement.remove();
    };
}
