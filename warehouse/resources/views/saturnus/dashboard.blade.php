@extends('layouts.app')

@section('title', 'SATURNUS — Smart Asset Tracking, Registration & Unregistration Network Utility System')

@section('content')

<!-- Load Three.js 3D Engine from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<!-- ==========================================================================
     GALACTIC COMMAND CENTER HEADER
     ========================================================================== -->
<div class="galactic-header">
    <div class="galactic-header-left">
        <div class="galactic-badge">
            <span class="pulse-beacon"></span>
            <span>MAI 3D SATURN OBSERVATORY · COMMAND CENTER</span>
        </div>
        <h1 class="galactic-title">
            <span>SATURNUS</span>
        </h1>
        <p class="galactic-subtitle"><span class="sat-letter sat-s">S</span>mart <span class="sat-letter sat-a">A</span>sset <span class="sat-letter sat-t">T</span>racking, Registration &amp; Unregistration Network Utility System</p>
    </div>

    <!-- Decorative Telemetry Status Badges -->
    <div class="galactic-telemetry-hud">
        <div class="telemetry-item">
            <div class="telemetry-icon-box green">
                <span class="telemetry-dot"></span>
            </div>
            <div class="telemetry-data">
                <span class="telemetry-label">3D WEBGL ENGINE</span>
                <span class="telemetry-val text-green">ACTIVE · 60 FPS GPU</span>
            </div>
        </div>

        <div class="telemetry-item">
            <div class="telemetry-icon-box cyan">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                    <path d="M2 12h20"></path>
                </svg>
            </div>
            <div class="telemetry-data">
                <span class="telemetry-label">CELESTIAL BODY</span>
                <span class="telemetry-val text-cyan">SATURN & 6 MOONS</span>
            </div>
        </div>

        <div class="telemetry-item">
            <div class="telemetry-icon-box purple">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="telemetry-data">
                <span class="telemetry-label">ORBIT SIMULATION</span>
                <span class="telemetry-val text-purple" id="telemetrySpeedVal">REAL-TIME 1.0X</span>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     🪐 CINEMATIC FULL-SCALE 3D WEBGL SATURNUS HERO (ZERO CLUTTER, PURE 3D)
     ========================================================================== -->
<div class="spatial-orbit-hero saturn-webgl-hero saturn-hero-fullscreen" id="spatialOrbitHero">
    <!-- WebGL Canvas Container for Three.js -->
    <div id="saturnWebglContainer" class="saturn-webgl-canvas-container"></div>

    <!-- HUD Tech Corners -->
    <div class="hud-corner top-left"></div>
    <div class="hud-corner top-right"></div>
    <div class="hud-corner bottom-left"></div>
    <div class="hud-corner bottom-right"></div>

    <!-- Top Camera & Simulation Controls Ribbon -->
    <div class="orbit-top-ribbon">
        <div class="orbit-sector-pills">
            <span class="hud-mono" style="font-size:0.65rem; color:#94a3b8; margin-left:0.35rem; margin-right:0.25rem;">CAMERA VIEW:</span>
            <button type="button" class="sector-pill active" id="camOrbitBtn" onclick="setSaturnCameraView('orbit', this)">🪐 CINEMATIC ORBIT</button>
            <button type="button" class="sector-pill" id="camRingBtn" onclick="setSaturnCameraView('ring', this)">🧭 RING PLANE</button>
            <button type="button" class="sector-pill" id="camPoleBtn" onclick="setSaturnCameraView('pole', this)">🌐 NORTH POLE</button>
            <button type="button" class="sector-pill" id="camTitanBtn" onclick="setSaturnCameraView('titan', this)">🛰️ TITAN VIEW</button>
        </div>

        <div class="orbit-speed-controls">
            <span class="hud-mono" style="font-size:0.65rem; color:#94a3b8; margin-right:0.35rem;">SIM SPEED:</span>
            <button type="button" class="speed-btn active" id="speed1xBtn" onclick="setSimulationSpeed(1.0, this)">1X</button>
            <button type="button" class="speed-btn" id="speed3xBtn" onclick="setSimulationSpeed(3.0, this)">3X WARP</button>
            <button type="button" class="speed-btn" id="speedPauseBtn" onclick="toggleSimulationPause(this)">⏸ PAUSE</button>
        </div>
    </div>

    <!-- Moon Telemetry Info Drawer (Opens on Moon Click) -->
    <div class="orbit-inspector-box" id="orbitInspectorBox">
        <div class="inspector-header">
            <span class="inspector-badge" id="inspBadge">MOON TELEMETRY</span>
            <button type="button" class="inspector-close-btn" onclick="closeInspector()">&times;</button>
        </div>
        <div class="inspector-body">
            <h4 class="inspector-title" id="inspTitle">TITAN (SATURN VI)</h4>
            <p class="inspector-sub" id="inspSub">Largest Moon of Saturn with Dense Nitrogen Atmosphere</p>
            <div class="inspector-metrics">
                <div class="insp-metric">
                    <span class="insp-k">DIAMETER</span>
                    <span class="insp-v text-amber" id="inspDiam">5,149 KM</span>
                </div>
                <div class="insp-metric">
                    <span class="insp-k">ORBIT RADIUS</span>
                    <span class="insp-v" id="inspDist">1,221,870 KM</span>
                </div>
                <div class="insp-metric">
                    <span class="insp-k">ASSOCIATED SECTOR</span>
                    <span class="insp-v text-cyan" id="inspDept">PRODUCTION / USER</span>
                </div>
            </div>
            <a href="{{ route('form-registrasi') }}" class="insp-action-btn" id="inspLink">
                <span>Buka Form Registrasi</span>
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>
    </div>

    <!-- Floating Sci-Fi Command Dock (Clean Bottom Quick Launcher) -->
    <div class="saturn-floating-dock">
        <a href="{{ route('form-registrasi') }}" class="dock-launcher-btn primary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>+ Form Registrasi Baru</span>
        </a>

        <a href="{{ route('form-registrasi') }}#proses-approval" class="dock-launcher-btn secondary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Proses Approval</span>
        </a>

        <a href="{{ route('form-registrasi') }}#data-view" class="dock-launcher-btn secondary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
            </svg>
            <span>Data View Explorer</span>
        </a>

        @if(in_array(strtoupper(Auth::user()->role ?? ''), ['MASTER', 'ADMIN']))
        <a href="{{ route('form-registrasi') }}#account-master" class="dock-launcher-btn secondary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
            </svg>
            <span>Account Master</span>
        </a>
        @endif
    </div>
</div>

@endsection

@section('scripts')
<script>
    // =========================================================================
    // SATURN MOONS METADATA & INSPECTOR DRAWER
    // =========================================================================
    const moonMetadata = {
        titan: {
            badge: 'SATURN VI · TITAN',
            title: 'TITAN (PRODUCTION FLEET)',
            sub: 'Largest Saturnian Moon with Dense Golden Atmosphere and Liquid Methane Lakes',
            diam: '5,149 KM',
            dist: '1,221,870 KM',
            dept: 'Production Requestor Node',
            link: "{{ route('form-registrasi') }}"
        },
        enceladus: {
            badge: 'SATURN II · ENCELADUS',
            title: 'ENCELADUS (STAFF VERIFIER)',
            sub: 'Glistening Ice World with Active Cryovolcanic Geysers into Saturn E-Ring',
            diam: '504 KM',
            dist: '238,020 KM',
            dept: 'Staff Verification Node',
            link: "{{ route('form-registrasi') }}#proses-approval"
        },
        rhea: {
            badge: 'SATURN V · RHEA',
            title: 'RHEA (ACCOUNTING HUB)',
            sub: 'Heavily Cratered Ice Giant with Tenuous Oxygen Atmosphere',
            diam: '1,527 KM',
            dist: '527,108 KM',
            dept: 'Accounting & Budget Hub',
            link: "{{ route('form-registrasi') }}#proses-approval"
        },
        dione: {
            badge: 'SATURN IV · DIONE',
            title: 'DIONE (WAREHOUSE DOCK)',
            sub: 'Dense Ice Body with Dramatic Glowing Ice Chasm Cliffs',
            diam: '1,122 KM',
            dist: '377,396 KM',
            dept: 'Warehouse Master Dock',
            link: "{{ route('form-registrasi') }}#data-view"
        },
        tethys: {
            badge: 'SATURN III · TETHYS',
            title: 'TETHYS (INVENTORY FLEET)',
            sub: 'Low Density Pure Water-Ice Body with Ithaca Chasma Trench',
            diam: '1,062 KM',
            dist: '294,619 KM',
            dept: 'Inventory Threshold Controller',
            link: "{{ route('form-registrasi') }}#data-view"
        },
        mimas: {
            badge: 'SATURN I · MIMAS',
            title: 'MIMAS (CONSUMABLE CORE)',
            sub: 'Inner Ring-Shepherd Moon with Giant Herschel Impact Crater',
            diam: '396 KM',
            dist: '185,539 KM',
            dept: 'Fast-Moving Consumables',
            link: "{{ route('dashboard') }}"
        }
    };

    function inspectMoon(key) {
        const data = moonMetadata[key];
        if (!data) return;

        document.getElementById('inspBadge').innerText = data.badge;
        document.getElementById('inspTitle').innerText = data.title;
        document.getElementById('inspSub').innerText = data.sub;
        document.getElementById('inspDiam').innerText = data.diam;
        document.getElementById('inspDist').innerText = data.dist;
        document.getElementById('inspDept').innerText = data.dept;
        document.getElementById('inspLink').href = data.link;

        document.getElementById('orbitInspectorBox').classList.add('show');
    }

    function closeInspector() {
        document.getElementById('orbitInspectorBox').classList.remove('show');
    }

    // =========================================================================
    // 🪐 PHOTOREALISTIC 3D WEBGL SATURN SIMULATION ENGINE (THREE.JS)
    // =========================================================================
    let simSpeed = 1.0;
    let isSimPaused = false;
    let targetCameraPos = { x: 0, y: 14, z: 34 };
    let activeCameraView = 'orbit';

    function setSimulationSpeed(mult, btn) {
        simSpeed = mult;
        isSimPaused = false;
        document.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        document.getElementById('telemetrySpeedVal').innerText = mult + 'X WARP SPEED';
    }

    function toggleSimulationPause(btn) {
        isSimPaused = !isSimPaused;
        document.querySelectorAll('.speed-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        btn.innerText = isSimPaused ? '▶ RESUME' : '⏸ PAUSE';
        document.getElementById('telemetrySpeedVal').innerText = isSimPaused ? 'PAUSED' : (simSpeed + 'X');
    }

    function setSaturnCameraView(viewKey, btn) {
        activeCameraView = viewKey;
        document.querySelectorAll('.sector-pill').forEach(p => p.classList.remove('active'));
        if (btn) btn.classList.add('active');

        if (viewKey === 'orbit') {
            targetCameraPos = { x: 0, y: 14, z: 34 };
        } else if (viewKey === 'ring') {
            targetCameraPos = { x: 28, y: 2.2, z: 12 };
        } else if (viewKey === 'pole') {
            targetCameraPos = { x: 0, y: 38, z: 2 };
        } else if (viewKey === 'titan') {
            targetCameraPos = { x: -24, y: 8, z: 18 };
            inspectMoon('titan');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('saturnWebglContainer');
        if (!container || typeof THREE === 'undefined') return;

        let width = container.offsetWidth;
        let height = container.offsetHeight;

        // 1. Scene, Camera, Renderer
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        camera.position.set(0, 14, 34);

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: "high-performance" });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        container.appendChild(renderer.domElement);

        // 2. Realistic Procedural Textures (Saturn Gas Bands & Rings)
        const planetCanvas = document.createElement('canvas');
        planetCanvas.width = 1024;
        planetCanvas.height = 512;
        const pCtx = planetCanvas.getContext('2d');

        const pGrad = pCtx.createLinearGradient(0, 0, 0, 512);
        pGrad.addColorStop(0.00, '#3a506b');
        pGrad.addColorStop(0.15, '#5c677d');
        pGrad.addColorStop(0.30, '#d4a373');
        pGrad.addColorStop(0.42, '#fefae0');
        pGrad.addColorStop(0.50, '#e9c46a');
        pGrad.addColorStop(0.58, '#fefae0');
        pGrad.addColorStop(0.70, '#dda15e');
        pGrad.addColorStop(0.85, '#bc6c25');
        pGrad.addColorStop(1.00, '#283618');
        pCtx.fillStyle = pGrad;
        pCtx.fillRect(0, 0, 1024, 512);

        for (let y = 0; y < 512; y += 2) {
            pCtx.fillStyle = Math.random() > 0.5 ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            pCtx.fillRect(0, y, 1024, Math.random() * 4 + 1);
        }

        const planetTexture = new THREE.CanvasTexture(planetCanvas);

        const ringCanvas = document.createElement('canvas');
        ringCanvas.width = 1024;
        ringCanvas.height = 64;
        const rCtx = ringCanvas.getContext('2d');

        const rGrad = rCtx.createLinearGradient(0, 0, 1024, 0);
        rGrad.addColorStop(0.00, 'rgba(0,0,0,0)');
        rGrad.addColorStop(0.18, 'rgba(0,0,0,0)');
        rGrad.addColorStop(0.20, 'rgba(180, 160, 130, 0.25)');
        rGrad.addColorStop(0.38, 'rgba(220, 190, 150, 0.45)');
        rGrad.addColorStop(0.40, 'rgba(245, 225, 185, 0.95)');
        rGrad.addColorStop(0.68, 'rgba(230, 205, 165, 0.90)');
        rGrad.addColorStop(0.70, 'rgba(0, 0, 0, 0.05)');
        rGrad.addColorStop(0.74, 'rgba(0, 0, 0, 0.05)');
        rGrad.addColorStop(0.75, 'rgba(210, 185, 145, 0.70)');
        rGrad.addColorStop(0.92, 'rgba(190, 165, 130, 0.60)');
        rGrad.addColorStop(0.93, 'rgba(0,0,0,0.0)');
        rGrad.addColorStop(0.95, 'rgba(200, 175, 140, 0.55)');
        rGrad.addColorStop(0.98, 'rgba(160, 140, 110, 0.35)');
        rGrad.addColorStop(1.00, 'rgba(0,0,0,0)');
        rCtx.fillStyle = rGrad;
        rCtx.fillRect(0, 0, 1024, 64);

        const ringTexture = new THREE.CanvasTexture(ringCanvas);

        // 3. Saturn Planetary Body (Tilted 26.73°)
        const saturnSystem = new THREE.Group();
        saturnSystem.rotation.z = THREE.MathUtils.degToRad(-26.73);
        scene.add(saturnSystem);

        const planetGeo = new THREE.SphereGeometry(6.5, 64, 64);
        planetGeo.scale(1, 0.91, 1);
        const planetMat = new THREE.MeshStandardMaterial({
            map: planetTexture,
            roughness: 0.72,
            metalness: 0.05
        });
        const planetMesh = new THREE.Mesh(planetGeo, planetMat);
        planetMesh.castShadow = true;
        planetMesh.receiveShadow = false; // Prevents self-shadow acne and polygon clipping artifacts
        saturnSystem.add(planetMesh);

        const ringGeo = new THREE.RingGeometry(7.8, 18.2, 128);
        const pos = ringGeo.attributes.position;
        const uvs = ringGeo.attributes.uv;
        for (let i = 0; i < pos.count; i++) {
            const x = pos.getX(i);
            const y = pos.getY(i);
            const r = Math.sqrt(x * x + y * y);
            const u = (r - 7.8) / (18.2 - 7.8);
            uvs.setXY(i, u, 0.5);
        }
        ringGeo.rotateX(Math.PI / 2);

        const ringMat = new THREE.MeshStandardMaterial({
            map: ringTexture,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.95,
            roughness: 0.6
        });
        const ringMesh = new THREE.Mesh(ringGeo, ringMat);
        ringMesh.receiveShadow = true;
        ringMesh.castShadow = false; // Ring is transparent micro-dust; disable solid polygon shadow casting to prevent black block artifacts
        saturnSystem.add(ringMesh);

        // 4. Moons of Saturn
        const moonsData = [
            { name: 'mimas',     r: 0.35, dist: 20.2, speed: 0.024, color: '#e2e8f0', key: 'mimas' },
            { name: 'enceladus', r: 0.42, dist: 23.6, speed: 0.019, color: '#38bdf8', key: 'enceladus' },
            { name: 'tethys',    r: 0.48, dist: 27.2, speed: 0.015, color: '#cbd5e1', key: 'tethys' },
            { name: 'dione',     r: 0.52, dist: 31.0, speed: 0.012, color: '#34d399', key: 'dione' },
            { name: 'rhea',      r: 0.60, dist: 35.0, speed: 0.009, color: '#c084fc', key: 'rhea' },
            { name: 'titan',     r: 1.15, dist: 40.0, speed: 0.005, color: '#fbbf24', key: 'titan' }
        ];

        const moonMeshes = [];

        moonsData.forEach((m, idx) => {
            const orbitCurve = new THREE.EllipseCurve(0, 0, m.dist, m.dist, 0, 2 * Math.PI, false, 0);
            const orbitPoints = orbitCurve.getPoints(96);
            const orbitGeo = new THREE.BufferGeometry().setFromPoints(orbitPoints.map(p => new THREE.Vector3(p.x, 0, p.y)));
            const orbitMat = new THREE.LineBasicMaterial({ color: m.color, transparent: true, opacity: 0.22 });
            const orbitLine = new THREE.Line(orbitGeo, orbitMat);
            saturnSystem.add(orbitLine);

            const moonGeo = new THREE.SphereGeometry(m.r, 24, 24);
            const moonMat = new THREE.MeshStandardMaterial({
                color: m.color,
                roughness: 0.6,
                metalness: 0.2,
                emissive: m.color,
                emissiveIntensity: 0.2
            });
            const moonMesh = new THREE.Mesh(moonGeo, moonMat);
            moonMesh.castShadow = true;
            moonMesh.userData = { ...m, angle: (idx * Math.PI) / 3 };
            saturnSystem.add(moonMesh);
            moonMeshes.push(moonMesh);
        });

        // 5. Dust Ring Particles
        const particleGeo = new THREE.BufferGeometry();
        const particleCount = 700;
        const particlePos = new Float32Array(particleCount * 3);
        const particleColors = new Float32Array(particleCount * 3);

        for (let i = 0; i < particleCount; i++) {
            const rad = THREE.MathUtils.randFloat(8.0, 18.0);
            const theta = Math.random() * Math.PI * 2;
            particlePos[i * 3] = Math.cos(theta) * rad;
            particlePos[i * 3 + 1] = THREE.MathUtils.randFloatSpread(0.25);
            particlePos[i * 3 + 2] = Math.sin(theta) * rad;

            particleColors[i * 3] = 0.85 + Math.random() * 0.15;
            particleColors[i * 3 + 1] = 0.78 + Math.random() * 0.2;
            particleColors[i * 3 + 2] = 0.65 + Math.random() * 0.35;
        }

        particleGeo.setAttribute('position', new THREE.BufferAttribute(particlePos, 3));
        particleGeo.setAttribute('color', new THREE.BufferAttribute(particleColors, 3));

        const particleMat = new THREE.PointsMaterial({
            size: 0.18,
            vertexColors: true,
            transparent: true,
            opacity: 0.75
        });
        const ringParticles = new THREE.Points(particleGeo, particleMat);
        saturnSystem.add(ringParticles);

        // 6. Lighting Setup
        const sunLight = new THREE.DirectionalLight(0xfff3db, 2.2);
        sunLight.position.set(45, 18, 30);
        sunLight.castShadow = true;
        sunLight.shadow.mapSize.width = 2048;
        sunLight.shadow.mapSize.height = 2048;
        sunLight.shadow.camera.near = 0.5;
        sunLight.shadow.camera.far = 150;
        sunLight.shadow.camera.left = -35;
        sunLight.shadow.camera.right = 35;
        sunLight.shadow.camera.top = 35;
        sunLight.shadow.camera.bottom = -35;
        sunLight.shadow.bias = -0.0001;
        sunLight.shadow.normalBias = 0.02;
        scene.add(sunLight);

        const ambientLight = new THREE.AmbientLight(0x0a142c, 0.85);
        scene.add(ambientLight);

        const rimLight = new THREE.DirectionalLight(0x00adef, 0.9);
        rimLight.position.set(-30, -10, -20);
        scene.add(rimLight);

        // 7. Interactive Drag to Rotate
        let isDragging = false;
        let prevMousePos = { x: 0, y: 0 };

        container.addEventListener('mousedown', function (e) {
            isDragging = true;
            prevMousePos = { x: e.clientX, y: e.clientY };
        });

        window.addEventListener('mouseup', function () {
            isDragging = false;
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            const deltaX = e.clientX - prevMousePos.x;
            const deltaY = e.clientY - prevMousePos.y;

            saturnSystem.rotation.y += deltaX * 0.006;
            saturnSystem.rotation.x += deltaY * 0.004;

            prevMousePos = { x: e.clientX, y: e.clientY };
        });

        // Raycasting on Moon Click
        const raycaster = new THREE.Raycaster();
        const mouse = new THREE.Vector2();

        container.addEventListener('click', function (e) {
            const rect = container.getBoundingClientRect();
            mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

            raycaster.setFromCamera(mouse, camera);
            const intersects = raycaster.intersectObjects(moonMeshes);

            if (intersects.length > 0) {
                const clickedMoon = intersects[0].object;
                inspectMoon(clickedMoon.userData.key);
            }
        });

        // Resize Listener
        window.addEventListener('resize', function () {
            width = container.offsetWidth;
            height = container.offsetHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        });

        // 8. 60 FPS Render Loop
        let clock = new THREE.Clock();

        function animate() {
            requestAnimationFrame(animate);
            const delta = clock.getDelta();

            camera.position.x += (targetCameraPos.x - camera.position.x) * 0.04;
            camera.position.y += (targetCameraPos.y - camera.position.y) * 0.04;
            camera.position.z += (targetCameraPos.z - camera.position.z) * 0.04;
            camera.lookAt(0, 0, 0);

            if (!isSimPaused) {
                planetMesh.rotation.y += 0.003 * simSpeed;
                ringParticles.rotation.y += 0.0015 * simSpeed;

                if (!isDragging && activeCameraView === 'orbit') {
                    saturnSystem.rotation.y += 0.0012 * simSpeed;
                }

                moonMeshes.forEach(m => {
                    m.userData.angle += m.userData.speed * simSpeed;
                    m.position.x = Math.cos(m.userData.angle) * m.userData.dist;
                    m.position.z = Math.sin(m.userData.angle) * m.userData.dist;
                });
            }

            renderer.render(scene, camera);
        }

        animate();
    });
</script>
@endsection
