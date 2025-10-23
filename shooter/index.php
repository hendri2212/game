<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <title>Webcam HeaderBall</title>
    <style>
        :root {
            --bg: #0b1020;
            --fg: #e8f0ff;
            --accent: #5de4c7;
            --muted: #94a3b8;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            background: var(--bg);
            color: var(--fg);
            font: 14px/1.35 system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial;
        }

        .wrap {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        #game {
            position: absolute;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            background: radial-gradient(1200px 600px at 50% 120%, #0f1630 0%, #0b1020 60%, #080b18 100%);
        }

        .hud {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
            pointer-events: none;
        }

        .hud .left,
        .hud .right {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .chip {
            pointer-events: auto;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .08);
            padding: 6px 10px;
            border-radius: 10px;
            backdrop-filter: blur(4px);
        }

        .chip strong {
            color: var(--accent);
        }

        .panel {
            position: absolute;
            bottom: 10px;
            left: 10px;
            display: flex;
            gap: 8px;
            align-items: center;
            pointer-events: auto;
        }

        button,
        input[type="range"],
        input[type="checkbox"],
        label {
            cursor: pointer;
        }

        button {
            background: #1f2937;
            color: var(--fg);
            border: 1px solid #334155;
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 600;
        }

        button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        button.primary {
            background: #0ea5e9;
            border-color: #0284c7;
            color: #02131f;
        }

        .controls {
            position: absolute;
            right: 10px;
            bottom: 10px;
            display: grid;
            gap: 6px;
            padding: 10px;
            background: rgba(2, 10, 20, .6);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 12px;
            pointer-events: auto;
        }

        .row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px;
            align-items: center;
            color: var(--muted);
        }

        .row code {
            color: var(--fg);
        }


        #video {
            display: none;
        }

        .help {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 10px;
            color: var(--muted);
            opacity: .9;
            text-align: center;
        }

        .help kbd {
            background: #111827;
            border: 1px solid #374151;
            padding: 2px 6px;
            border-radius: 6px;
            font-weight: 600;
        }
        /* Right-side Leaderboard */
        .leaderboard {
            position: absolute;
            top: 70px;
            right: 10px;
            width: 280px;
            max-height: 60vh;
            overflow: auto;
            background: rgba(2, 10, 20, .6);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 12px;
            padding: 10px;
            pointer-events: auto;
        }
        .leaderboard .lb-title {
            font-weight: 700;
            letter-spacing: .3px;
            margin-bottom: 6px;
            color: var(--accent);
        }
        .leaderboard .lb-list {
            margin: 0; padding-left: 20px;
        }
        .leaderboard .lb-list li {
            margin-bottom: 4px;
        }
        .leaderboard .name { color: var(--fg); }
        .leaderboard .score { color: var(--muted); }
        .leaderboard .lb-highlight {
            background: rgba(93, 228, 199, .14);
            border-left: 3px solid var(--accent);
            padding-left: 6px;
            border-radius: 6px;
        }
        /* --- Splash Screen (rank, name, score) --- */
        .splash {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #0b1020 0%, #1a1f3a 50%, #0b1020 100%);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.5s ease-out;
        }
        .splash.active { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes glow {
            0%, 100% { text-shadow: 0 0 20px var(--accent), 0 0 40px var(--accent); }
            50%      { text-shadow: 0 0 30px var(--accent), 0 0 60px var(--accent); }
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .splash-content { text-align: center; animation: slideUp 0.8s ease-out 0.3s both; }
        .splash-trophy { font-size: 80px; margin-bottom: 20px; animation: bounce 1s ease-in-out infinite; }
        .splash-rank {
            font-size: 120px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #5de4c7, #3b9aff, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glow 2s ease-in-out infinite;
        }
        .splash-rank.gold {
            background: linear-gradient(135deg, #ffd700, #ffed4e, #ffd700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .splash-rank.silver {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8, #c0c0c0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .splash-rank.bronze {
            background: linear-gradient(135deg, #cd7f32, #e8a87c, #cd7f32);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .splash-label { font-size: 24px; color: var(--muted); margin-bottom: 30px; letter-spacing: 2px; text-transform: uppercase; }
        .splash-name  { font-size: 48px; font-weight: 700; color: var(--fg); margin-bottom: 15px; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
        .splash-score { font-size: 72px; font-weight: 900; color: var(--accent); margin-bottom: 40px; text-shadow: 0 0 30px rgba(93, 228, 199, 0.5); }
        .splash-message { font-size: 18px; color: var(--muted); margin-top: 30px; }
    </style>
</head>

<body>
    <div class="wrap">
        <canvas id="game"></canvas>

        <div class="hud">
            <div class="left">
                <div class="chip">Skor: <strong id="score">0</strong></div>
                <div class="chip">Akurasi: <strong id="acc">—</strong></div>
                <div class="chip">Waktu: <strong id="timeleft">00:00</strong></div>
            </div>
            <div class="right">
                <div class="chip" id="status">⏳ Kamera & model belum aktif</div>
            </div>
        </div>

        <div class="panel">
            <button id="startBtn" class="primary">🎥 Start Camera</button>
        </div>

        <div class="controls">
            <div class="row"><span>Durasi (menit)</span><input id="dur" type="number" min="1" max="60" value="1" /></div>
            <div class="row"><span>Mirror Video</span><input id="mirror" type="checkbox" checked /></div>
            <div class="row"><span>Deteksi tiap (ms)</span><input id="interval" type="range" min="40" max="200"
                    value="80" /></div>
            <div class="row"><span>Head radius scale</span><input id="hscale" type="range" min="30" max="80"
                    value="45" /></div>
            <div class="row"><span>Target aktif</span><code id="tcount">0</code></div>
        </div>
        <!-- Right-side Leaderboard Panel -->
        <div class="leaderboard" id="leaderboard">
            <div class="lb-title">Leaderboard</div>
            <div class="lb-body">
                <ol id="lbList" class="lb-list"></ol>
            </div>
        </div>

        <div class="help">Sundul bola dengan <strong>kepala</strong> (deteksi wajah). | Tips: berdiri 0.5–2 m dari
            kamera, cahaya cukup.</div>

        <div class="splash" id="splash">
            <div class="splash-content">
                <div class="splash-trophy" id="splashTrophy">🏆</div>
                <div class="splash-rank" id="splashRank">#1</div>
                <div class="splash-label">Peringkat</div>
                <div class="splash-name" id="splashName">Nama Pemain</div>
                <div class="splash-score"><span id="splashScore">0</span> Poin</div>
                <div class="splash-message">Selamat! Terima kasih sudah bermain 🎉</div>
            </div>
        </div>

        <video id="video" playsinline></video>
    </div>

    <!-- Registration Modal -->
    <div class="modal fade text-black" id="regModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="regForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrasi Pemain</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="full_name" placeholder="Nama lengkap" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor WhatsApp</label>
                        <input type="tel" class="form-control" name="phone" placeholder="08xxxxxxxxxx atau +62xxxxxxxxxx" required>
                        <div class="form-text">Gunakan format Indonesia. Contoh: 08xxxxxxxxxx (atau +62xxxxxxxxxx)</div>
                    </div>
                    <div id="regErr" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100">Daftar &amp; Mulai</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Bootstrap JS (for modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <script type="module">
        import { FilesetResolver, FaceLandmarker, PoseLandmarker } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14";

        (() => {
            // ===== Elements =====
            const video = document.getElementById('video');
            const game = document.getElementById('game');
            const g = game.getContext('2d', { alpha: false });

            const scoreEl = document.getElementById('score');
            const accEl = document.getElementById('acc');
            const statusEl = document.getElementById('status');
            const startBtn = document.getElementById('startBtn');

            const mirrorIn = document.getElementById('mirror');
            const intervalIn = document.getElementById('interval');
            const hscaleIn = document.getElementById('hscale');
            const tcountEl = document.getElementById('tcount');
            const timeEl  = document.getElementById('timeleft');
            const durIn   = document.getElementById('dur');
            const lbList = document.getElementById('lbList');
            let lastFinishedPhone = null;
            function setHighlight(phone) {
                lastFinishedPhone = (phone || '').trim();
                if (lastFinishedPhone) {
                    // auto-clear highlight after 10s
                    setTimeout(() => {
                        if (lastFinishedPhone === phone) {
                            lastFinishedPhone = null;
                            loadLeaders();
                        }
                    }, 10000);
                }
            }
            const splash = document.getElementById('splash');
            const splashRank = document.getElementById('splashRank');
            const splashName = document.getElementById('splashName');
            const splashScore = document.getElementById('splashScore');
            const splashTrophy = document.getElementById('splashTrophy');

            // ===== Registration (Bootstrap modal) =====
            const regForm = document.getElementById('regForm');
            const regErr  = document.getElementById('regErr');
            const regModalEl = document.getElementById('regModal');
            let regModal;

            function showReg() {
                regModal = regModal || new bootstrap.Modal(regModalEl, { backdrop: 'static', keyboard: false });
                // Clear form inputs and error message every time the modal opens
                if (regForm) {
                    regForm.reset();
                }
                if (regErr) {
                    regErr.textContent = '';
                    regErr.classList.add('d-none');
                }
                regModal.show();
            }

            // Disable Start until registered
            const storedId = localStorage.getItem('player_id');
            const storedName = localStorage.getItem('player_name');
            if (!storedId) {
              startBtn.disabled = true;
              showReg();
            } else {
              startBtn.disabled = false;
              if (storedName) statusEl.textContent = '👤 ' + storedName;
            }

            // ===== State =====
            const state = {
                running: false,
                lastInfer: 0,
                inferInterval: 80,
                face: null, // {x, y, r} in canvas space
                smooth: 0.35,
                mirror: true,
                score: 0,
                hits: 0,
                swings: 0,
                targets: [],
                particles: [],
                playing: false,
                gameDurationMs: 60_000,
                timeLeftMs: 60_000,
                scoreSaved: false,
                camW: 640, camH: 480 // will be updated by stream
            };

            // ===== Models (MediaPipe Tasks Vision) =====
            let faceLandmarker = null;
            let poseLandmarker = null;

            async function loadModels() {
                const fileset = await FilesetResolver.forVisionTasks(
                    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm"
                );

                // Face Landmarker (landmarks-based for stability)
                faceLandmarker = await FaceLandmarker.createFromOptions(fileset, {
                    baseOptions: {
                        // Google-hosted prebuilt task model
                        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task"
                    },
                    runningMode: "VIDEO",
                    numFaces: 1
                });

                // Pose Landmarker (BlazePose)
                poseLandmarker = await PoseLandmarker.createFromOptions(fileset, {
                    baseOptions: {
                        modelAssetPath: "https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_full/float16/1/pose_landmarker_full.task"
                    },
                    runningMode: "VIDEO",
                    numPoses: 1
                });
            }

            // ===== Utils =====
            const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
            const lerp = (a, b, t) => a + (b - a) * t;
            function dist2(ax, ay, bx, by) { const dx = ax - bx, dy = ay - by; return dx * dx + dy * dy; }

            // Audio
            let actx;
            function beep(freq = 660, ms = 80, vol = 0.12) {
                try {
                    actx = actx || new (window.AudioContext || window.webkitAudioContext)();
                    const o = actx.createOscillator(), g = actx.createGain();
                    o.type = 'square'; o.frequency.value = freq; g.gain.value = vol;
                    o.connect(g); g.connect(actx.destination);
                    o.start(); setTimeout(() => o.stop(), ms);
                } catch (_) { }
            }

            // ===== Camera =====
            async function startCamera() {
                if (!navigator.mediaDevices?.getUserMedia) {
                    statusEl.textContent = '❌ getUserMedia tidak didukung browser ini'; return;
                }
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                        audio: false
                    });
                    video.srcObject = stream;
                    await video.play();
                    state.camW = video.videoWidth || 1280;
                    state.camH = video.videoHeight || 720;
                    statusEl.textContent = '📷 Kamera aktif, memuat model MediaPipe...';
                    startBtn.disabled = true;
                    await loadModels();
                    statusEl.textContent = '✅ Siap! Gerakkan kepala untuk menyundul bola.';
                    state.running = true;
                    startRound();
                    onResize();
                    requestAnimationFrame(loop);
                } catch (e) {
                    console.error(e);
                    statusEl.textContent = '❌ Akses kamera ditolak/gagal';
                }
            }

            function stopCamera() {
                try {
                    const stream = video.srcObject;
                    if (stream && typeof stream.getTracks === 'function') {
                        stream.getTracks().forEach(t => t.stop());
                    }
                } catch (_) {}
                video.srcObject = null;
            }

            startBtn.addEventListener('click', startCamera);

            // Handle registration submit
            regForm.addEventListener('submit', async (e) => {
              e.preventDefault();
              regErr.classList.add('d-none');

              const fd = new FormData(regForm);
              let full_name = (fd.get('full_name') || '').toString().trim();
              let phone = (fd.get('phone') || '').toString().trim();

              if (full_name.length < 3) {
                regErr.textContent = 'Nama minimal 3 karakter.'; regErr.classList.remove('d-none'); return;
              }
              // Normalize phone
              phone = phone.replace(/[\s\-().]/g, '');
              if (phone.startsWith('+62')) phone = '0' + phone.slice(3);
              if (phone.startsWith('62')) phone = '0' + phone.slice(2);
              if (!/^0\d{8,14}$/.test(phone)) {
                regErr.textContent = 'Nomor WhatsApp tidak valid.'; regErr.classList.remove('d-none'); return;
              }

              try {
                const res = await fetch('save.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: new URLSearchParams({ full_name, phone }).toString()
                });
                const data = await res.json();
                if (!res.ok || !data.ok) throw new Error(data.error || 'Gagal menyimpan.');

                localStorage.setItem('player_id', String(data.id));
                localStorage.setItem('player_name', full_name);
                localStorage.setItem('player_phone', phone);

                statusEl.textContent = '👤 ' + full_name;
                startBtn.disabled = false;
                if (regModal) regModal.hide();
                loadLeaders();
              } catch (err) {
                regErr.textContent = 'Gagal daftar: ' + err.message;
                regErr.classList.remove('d-none');
              }
            });

            // ===== Resize =====
            function onResize() {
                game.width = game.clientWidth;
                game.height = game.clientHeight;
            }
            addEventListener('resize', onResize);

            // ===== Inference (Face Landmarker + Pose Landmarker fusion) =====
            async function maybeInfer(now) {
                if (!faceLandmarker || !poseLandmarker || video.readyState < 2) return;
                if (now - state.lastInfer < state.inferInterval) return;
                state.lastInfer = now;

                const ts = performance.now();

                const faceRes = faceLandmarker.detectForVideo(video, ts);
                const poseRes = poseLandmarker.detectForVideo(video, ts);

                let faceCenter = null, faceRadius = null;

                if (faceRes && faceRes.faceLandmarks && faceRes.faceLandmarks.length) {
                    const pts = faceRes.faceLandmarks[0]; // 468 points, normalized [0..1]
                    let minx = 1, miny = 1, maxx = 0, maxy = 0;
                    for (const pt of pts) {
                        if (pt.x < minx) minx = pt.x;
                        if (pt.y < miny) miny = pt.y;
                        if (pt.x > maxx) maxx = pt.x;
                        if (pt.y > maxy) maxy = pt.y;
                    }
                    const cxNorm = (minx + maxx) * 0.5;
                    const cyNorm = (miny + maxy) * 0.5;
                    let cx = cxNorm * game.width;
                    if (state.mirror) cx = game.width - cx;
                    const cy = cyNorm * game.height;
                    const rpx = (maxx - minx) * game.width * 0.5;
                    const rpy = (maxy - miny) * game.height * 0.5;
                    const r = Math.max(rpx, rpy) * (+hscaleIn.value / 100);

                    faceCenter = { x: cx, y: cy };
                    faceRadius = r;
                }

                // Pose landmarks
                let poseCenter = null, poseRadius = null;
                const poseLmsList = (poseRes && (poseRes.landmarks || poseRes.poseLandmarks)) || [];
                if (poseLmsList.length) {
                    const lm = poseLmsList[0]; // 33 landmarks
                    const nose = lm[0];
                    const leftEar = lm[7];   // MediaPipe index (may vary by version)
                    const rightEar = lm[8];
                    const pts = [];
                    if (nose) pts.push(nose);
                    if (leftEar) pts.push(leftEar);
                    if (rightEar) pts.push(rightEar);
                    if (pts.length) {
                        let cxNorm = 0, cyNorm = 0;
                        for (const p of pts) { cxNorm += p.x; cyNorm += p.y; }
                        cxNorm /= pts.length; cyNorm /= pts.length;

                        let cx = cxNorm * game.width;
                        if (state.mirror) cx = game.width - cx;
                        const cy = cyNorm * game.height;
                        poseCenter = { x: cx, y: cy };

                        if (leftEar && rightEar) {
                            const dx = (rightEar.x - leftEar.x) * game.width;
                            const dy = (rightEar.y - leftEar.y) * game.height;
                            poseRadius = Math.hypot(dx, dy) * 0.35;
                        } else {
                            poseRadius = faceRadius || 40;
                        }
                    }
                }

                // Fuse centers (prefer face for accuracy, blend with pose for stability)
                let cx, cy, r;
                if (faceCenter && poseCenter) {
                    cx = lerp(poseCenter.x, faceCenter.x, 0.7);
                    cy = lerp(poseCenter.y, faceCenter.y, 0.7);
                    r = lerp(poseRadius || faceRadius, faceRadius || poseRadius, 0.7);
                } else if (faceCenter) {
                    cx = faceCenter.x; cy = faceCenter.y; r = faceRadius;
                } else if (poseCenter) {
                    cx = poseCenter.x; cy = poseCenter.y; r = poseRadius;
                } else {
                    state.face = null; return;
                }

                if (state.face) {
                    state.face.x = lerp(state.face.x, cx, state.smooth);
                    state.face.y = lerp(state.face.y, cy, state.smooth);
                    state.face.r = lerp(state.face.r, r, 0.2);
                } else {
                    state.face = { x: cx, y: cy, r };
                }
            }

            // ===== Game Objects (same as current, with TTL + countdown ring) =====
            function spawnTarget() {
                const r = 26 + Math.random() * 24;
                const margin = Math.max(40, r + 10);
                const x = margin + Math.random() * (game.width - margin * 2);
                const y = margin + Math.random() * (game.height - margin * 2);
                const maxTtl = 7 + Math.random() * 4; // seconds
                const ttl = maxTtl;
                const hue = 200 + Math.random() * 100;
                state.targets.push({ x, y, r, ttl, maxTtl, hue });
            }

            function ensureTargets(n = 4) {
                while (state.targets.length < n) spawnTarget();
                tcountEl.textContent = state.targets.length;
            }

            function popBall(i) {
                const t = state.targets[i];
                if (!t) return;
                state.targets.splice(i, 1);
                state.score += 1; state.hits += 1;
                beep(240, 120, 0.18);
                for (let k = 0; k < 18; k++) {
                    const a = Math.random() * Math.PI * 2;
                    const sp = 100 + Math.random() * 140;
                    state.particles.push({ x: t.x, y: t.y, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, life: .4 + .3 * Math.random() });
                }
                setTimeout(spawnTarget, 350 + Math.random() * 400);
                scoreEl.textContent = state.score;
                updateAcc();
            }

            function updateAcc() {
                const a = state.swings ? Math.round((state.hits / state.swings) * 100) : 0;
                accEl.textContent = state.swings ? (a + '%') : '—';
            }
            // ===== Leaderboard =====
            function esc(s) {
                return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            }

            async function loadLeaders() {
                try {
                    const res = await fetch('save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'leaders' }).toString()
                    });
                    const data = await res.json();
                    if (!res.ok || !data.ok) throw new Error(data.error || 'Gagal memuat leaderboard');
                    renderLeaders(data.players || []);
                } catch (_) {
                    renderLeaders([]);
                }
            }

            function renderLeaders(players) {
                if (!lbList) return;
                if (!players.length) {
                    lbList.innerHTML = '<li class="text-muted">Belum ada data</li>';
                    return;
                }
                const items = players.map(p => {
                    const name = esc(p.full_name || 'Anonim');
                    const score = Number(p.score || 0);
                    const isMe = !!(lastFinishedPhone && p.phone && p.phone === lastFinishedPhone);
                    const cls = isMe ? 'lb-highlight' : '';
                    return `<li class="${cls}"><span class="name">${name}</span> — <span class="score">${score}</span></li>`;
                }).join('');
                lbList.innerHTML = items;
            }

            function fmtTime(ms) {
                const total = Math.max(0, Math.ceil(ms / 1000));
                const m = Math.floor(total / 60);
                const s = total % 60;
                return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
            }

            function startRound() {
                state.score = 0; scoreEl.textContent = state.score;
                state.hits = 0; state.swings = 0; updateAcc();
                state.targets = []; state.particles = [];
                state.scoreSaved = false;

                const mins = Math.max(1, parseInt(durIn.value || '1', 10));
                state.gameDurationMs = mins * 60_000;
                state.timeLeftMs = state.gameDurationMs;

                state.playing = true;
                timeEl.textContent = fmtTime(state.timeLeftMs);
                statusEl.textContent = '🎮 Game dimulai · durasi ' + mins + ' menit';
            }

            async function sendScore() {
                if (state.scoreSaved) return;
                const phone = (localStorage.getItem('player_phone') || '').trim();
                if (!phone) { state.scoreSaved = true; return; } // fallback: tidak kirim kalau belum register (harusnya sudah)

                try {
                    const res = await fetch('save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'score', phone, score: String(state.score|0) }).toString()
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                    console.warn('Update score gagal:', data.error || res.statusText);
                    }
                } catch (e) {
                    console.warn('Update score gagal:', e);
                } finally {
                    state.scoreSaved = true;
                }
            }

            function showSplash(rank, name, score) {
                if (!splash) return;
                // reset classes
                splashRank.classList.remove('gold','silver','bronze');
                let trophy = '🏆';
                if (rank === 1) { splashRank.classList.add('gold'); trophy = '🥇'; }
                else if (rank === 2) { splashRank.classList.add('silver'); trophy = '🥈'; }
                else if (rank === 3) { splashRank.classList.add('bronze'); trophy = '🥉'; }
                splashTrophy.textContent = trophy;
                splashRank.textContent = '#' + (rank || '?');
                splashName.textContent = name || 'Pemain';
                splashScore.textContent = String(score || 0);
                splash.classList.add('active');
                // auto-hide after 6s
                setTimeout(() => splash.classList.remove('active'), 6000);
            }

            async function endRound() {
                if (!state.playing) return;
                state.playing = false;
                statusEl.textContent = '⏱️ Waktu habis! Menyimpan skor & menampilkan peringkat...';

                const phoneJustFinished = (localStorage.getItem('player_phone') || '').trim();
                const nameJustFinished  = (localStorage.getItem('player_name')  || 'Pemain').trim() || 'Pemain';
                const finalScore = state.score | 0;
                setHighlight(phoneJustFinished);

                // 1) Simpan skor ke server
                await sendScore();

                // 2) Ambil leaderboard & hitung peringkat pemain saat ini
                try {
                    const res = await fetch('save.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'leaders' }).toString()
                    });
                    const data = await res.json().catch(() => ({}));
                    const players = Array.isArray(data.players)
                        ? data.players.map(p => ({ ...p, score: Number(p.score || 0) }))
                        : [];

                    // Pastikan entri pemain ada (pakai skor terbaru)
                    const idxExisting = players.findIndex(p => p.phone === phoneJustFinished);
                    if (idxExisting >= 0) {
                        players[idxExisting].score = Math.max(Number(players[idxExisting].score || 0), finalScore);
                    } else {
                        players.push({ phone: phoneJustFinished, full_name: nameJustFinished, score: finalScore });
                    }

                    // Urutkan desc berdasarkan skor dan cari peringkat
                    players.sort((a, b) => b.score - a.score);
                    const myIdx = players.findIndex(p => p.phone === phoneJustFinished);
                    const myRank = myIdx >= 0 ? (myIdx + 1) : null;

                    // Tampilkan Splash
                    showSplash(myRank, nameJustFinished, finalScore);

                    // Render ulang panel leaderboard
                    renderLeaders(players);
                } catch (e) {
                    // Jika gagal ambil leaderboard, tetap tampilkan splash tanpa peringkat pasti
                    showSplash(null, nameJustFinished, finalScore);
                }

                // 3) Bersihkan objek game
                state.targets = [];
                state.particles = [];

                // 4) Hentikan kamera & loop
                stopCamera();
                state.running = false;

                // 5) Logout pemain (hapus identitas lokal)
                localStorage.removeItem('player_id');
                localStorage.removeItem('player_name');
                localStorage.removeItem('player_phone');

                // 6) Reset HUD & lock Start
                timeEl.textContent = '00:00';
                scoreEl.textContent = '0';
                accEl.textContent = '—';
                startBtn.disabled = true;

                // 7) Tampilkan modal registrasi untuk pemain berikutnya
                statusEl.textContent = '👋 Pemain keluar. Silakan daftar pemain berikutnya.';
                showReg();
            }

            // ===== Loop =====
            let last = performance.now();
            function loop(now) {
                if (!state.running) return;
                const dt = Math.min(0.05, (now - last) / 1000); last = now;
                // Timer countdown
                if (state.playing) {
                    state.timeLeftMs -= dt * 1000;
                    if (state.timeLeftMs <= 0) {
                        state.timeLeftMs = 0;
                        endRound();
                    }
                }
                timeEl.textContent = fmtTime(state.timeLeftMs);

                ensureTargets(state.playing ? 4 : 0);
                maybeInfer(now);

                // Physics (TTL countdown + head collision)
                for (let i = state.targets.length - 1; i >= 0; i--) {
                    const t = state.targets[i];
                    // countdown
                    t.ttl -= dt;
                    if (t.ttl <= 0) { state.targets.splice(i, 1); continue; }

                    // collision with head (sundul)
                    if (state.face) {
                        const d2 = dist2(state.face.x, state.face.y, t.x, t.y);
                        const rr = (state.face.r + t.r);
                        if (d2 <= rr * rr) {
                            state.swings++;
                            popBall(i);
                        }
                    }
                }

                // particles
                for (let i = state.particles.length - 1; i >= 0; i--) {
                    const p = state.particles[i];
                    p.life -= dt; p.x += p.vx * dt; p.y += p.vy * dt; p.vx *= 0.96; p.vy *= 0.96;
                    if (p.life <= 0) state.particles.splice(i, 1);
                }

                draw();
                requestAnimationFrame(loop);
            }

            function draw() {
                const w = game.width, h = game.height;

                // 1) Draw webcam as background (object-fit: cover), honoring Mirror setting
                if (video.readyState >= 2) {
                    g.save();
                    // Mirror the whole background if enabled
                    if (state.mirror) {
                        g.translate(w, 0);
                        g.scale(-1, 1);
                    }

                    // Compute "cover" size to fill canvas without stretching
                    const vidW = state.camW || video.videoWidth || 1280;
                    const vidH = state.camH || video.videoHeight || 720;
                    const scale = Math.max(w / vidW, h / vidH);
                    const dw = vidW * scale;
                    const dh = vidH * scale;
                    const dx = (w - dw) / 2;
                    const dy = (h - dh) / 2;

                    // Draw the camera frame
                    g.drawImage(video, dx, dy, dw, dh);
                    g.restore();
                } else {
                    // Fallback: simple clear if camera not ready
                    g.clearRect(0, 0, w, h);
                }

                // 2) Draw targets (stationary with TTL + countdown ring) on top of the video
                for (const t of state.targets) {
                    g.save();
                    const grad = g.createRadialGradient(t.x - 4, t.y - 6, t.r * 0.2, t.x, t.y, t.r);
                    grad.addColorStop(0, `hsl(${t.hue}, 90%, 65%)`);
                    grad.addColorStop(1, `hsl(${t.hue}, 90%, 40%)`);
                    g.fillStyle = grad;
                    g.beginPath(); g.arc(t.x, t.y, t.r, 0, Math.PI * 2); g.fill();
                    g.lineWidth = 2; g.strokeStyle = 'rgba(255,255,255,.35)'; g.stroke();

                    // countdown ring
                    const frac = clamp(t.ttl / t.maxTtl, 0, 1);
                    g.beginPath(); g.strokeStyle = 'rgba(255,255,255,.6)';
                    g.arc(t.x, t.y, t.r + 4, -Math.PI / 2, -Math.PI / 2 + frac * 2 * Math.PI);
                    g.stroke();
                    g.restore();
                }

                // 3) Particles
                for (const p of state.particles) {
                    g.globalAlpha = clamp(p.life / 0.4, 0, 1);
                    g.fillStyle = '#ffe08a'; g.fillRect(p.x, p.y, 2, 2);
                }
                g.globalAlpha = 1;

                // 4) Head circle (kept as a subtle visual aid for collision). Comment out if you want it invisible.
                if (state.face) {
                    g.save();
                    g.strokeStyle = 'rgba(229, 251, 255, .85)'; g.lineWidth = 3;
                    g.beginPath(); g.arc(state.face.x, state.face.y, state.face.r, 0, Math.PI * 2); g.stroke();
                    g.lineWidth = 1.5; g.beginPath();
                    g.moveTo(state.face.x - 22, state.face.y); g.lineTo(state.face.x - 8, state.face.y);
                    g.moveTo(state.face.x + 8, state.face.y); g.lineTo(state.face.x + 22, state.face.y);
                    g.moveTo(state.face.x, state.face.y - 22); g.lineTo(state.face.x, state.face.y - 8);
                    g.moveTo(state.face.x, state.face.y + 8); g.lineTo(state.face.x, state.face.y + 22);
                    g.stroke();
                    g.restore();
                } else {
                    // hint overlay if face not detected
                    g.fillStyle = 'rgba(0,0,0,.25)'; // soft overlay for readability
                    g.fillRect(0, 0, w, h);
                    g.fillStyle = 'rgba(255,255,255,.9)';
                    g.textAlign = 'center'; g.textBaseline = 'middle'; g.font = '600 18px system-ui';
                    g.fillText('Hadapkan wajah ke kamera. Pencahayaan cukup.', w / 2, h / 2);
                }
            }

            // ===== Controls =====
            mirrorIn.addEventListener('change', () => { state.mirror = mirrorIn.checked; });
            intervalIn.addEventListener('input', () => { state.inferInterval = +intervalIn.value | 0; });
            hscaleIn.addEventListener('input', () => {/* used during infer to set radius */ });

            // ===== Boot =====
            if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                statusEl.textContent = 'ℹ️ Jalankan di https:// atau http://localhost untuk akses kamera.';
                // Load leaderboard initially and refresh periodically
                loadLeaders();
                setInterval(loadLeaders, 5000);
            }
        })();
    </script>
</body>

</html>