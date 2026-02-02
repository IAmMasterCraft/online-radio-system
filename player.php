<?php
require_once __DIR__ . '/config.php';
$stationName = getSetting('station_name', 'Online Radio');
$tagline = getSetting('station_tagline', 'Broadcasting hope, one story at a time');
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($stationName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif&family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #080808;
            --surface: #111113;
            --surface-2: #19191c;
            --border: #222226;
            --text: #ebebf0;
            --text-dim: #6e6e78;
            --warm: #e8a838;
            --warm-glow: rgba(232, 168, 56, 0.08);
            --warm-soft: rgba(232, 168, 56, 0.25);
            --on-air: #e84848;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Ambient Background ────────────────── */
        .ambient {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            overflow: hidden;
        }
        .ambient::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(ellipse at 30% 20%, rgba(232, 168, 56, 0.04) 0%, transparent 50%),
                        radial-gradient(ellipse at 70% 80%, rgba(200, 120, 60, 0.03) 0%, transparent 50%);
            animation: ambientDrift 20s ease-in-out infinite alternate;
        }
        @keyframes ambientDrift {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-3%, 2%) rotate(3deg); }
        }

        /* ── Layout ────────────────────────────── */
        .container {
            position: relative; z-index: 1;
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 2rem;
        }

        /* ── Station Header ────────────────────── */
        .station-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .station-header .name {
            font-family: 'Instrument Serif', Georgia, serif;
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 400;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }
        .station-header .tagline {
            color: var(--text-dim);
            font-size: 0.95rem;
            margin-top: 0.4rem;
        }

        /* ── Player Card ───────────────────────── */
        .player-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
            position: relative;
            overflow: hidden;
        }
        .player-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--warm-soft), transparent);
        }

        /* ── Cover Art ─────────────────────────── */
        .cover-wrap {
            width: 100%;
            aspect-ratio: 1;
            max-width: 280px;
            margin: 0 auto 1.8rem;
            border-radius: 16px;
            overflow: hidden;
            background: var(--surface-2);
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .cover-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .cover-placeholder {
            font-size: 4rem;
            opacity: 0.3;
        }
        /* Spinning animation for when playing */
        .cover-wrap.spinning {
            animation: spin 30s linear infinite;
            border-radius: 50%;
        }
        .cover-wrap.spinning img {
            border-radius: 50%;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        /* ── Status Badge ──────────────────────── */
        .status-bar {
            display: flex; align-items: center; justify-content: center;
            gap: 0.5rem; margin-bottom: 1.2rem;
        }
        .status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--text-dim);
        }
        .status-dot.live {
            background: var(--on-air);
            animation: pulse 1.5s ease-in-out infinite;
        }
        .status-dot.loop {
            background: var(--warm);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 currentColor; }
            50% { opacity: 0.7; box-shadow: 0 0 8px 2px currentColor; }
        }
        .status-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .status-label.live { color: var(--on-air); }
        .status-label.loop { color: var(--warm); }
        .status-label.offline { color: var(--text-dim); }

        /* ── Track Info ────────────────────────── */
        .track-info {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .track-title {
            font-family: 'Instrument Serif', Georgia, serif;
            font-size: 1.5rem;
            font-weight: 400;
            line-height: 1.3;
            margin-bottom: 0.2rem;
        }
        .track-artist {
            color: var(--text-dim);
            font-size: 0.9rem;
        }
        .track-desc {
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            line-height: 1.5;
        }

        /* ── Progress Bar ──────────────────────── */
        .progress-wrap {
            margin-bottom: 1.2rem;
        }
        .progress-track {
            width: 100%; height: 4px;
            background: var(--surface-2);
            border-radius: 2px;
            overflow: hidden;
            cursor: pointer;
        }
        .progress-track:hover { height: 6px; }
        .progress-played {
            height: 100%;
            background: var(--warm);
            border-radius: 2px;
            transition: width 0.5s linear;
            width: 0;
        }
        .progress-times {
            display: flex; justify-content: space-between;
            margin-top: 0.4rem;
            font-family: 'DM Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-dim);
        }

        /* ── Controls ──────────────────────────── */
        .controls {
            display: flex; align-items: center; justify-content: center;
            gap: 1.5rem;
        }
        .play-btn {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: var(--warm);
            border: none;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(232, 168, 56, 0.25);
        }
        .play-btn:hover {
            transform: scale(1.06);
            box-shadow: 0 6px 30px rgba(232, 168, 56, 0.35);
        }
        .play-btn:active { transform: scale(0.96); }
        .play-btn svg { width: 28px; height: 28px; fill: #000; }

        .vol-wrap {
            display: flex; align-items: center; gap: 0.5rem;
        }
        .vol-icon {
            color: var(--text-dim); font-size: 1rem; cursor: pointer;
        }
        .vol-slider {
            -webkit-appearance: none; appearance: none;
            width: 80px; height: 4px;
            background: var(--surface-2);
            border-radius: 2px;
            outline: none;
        }
        .vol-slider::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 14px; height: 14px;
            border-radius: 50%;
            background: var(--warm);
            cursor: pointer;
        }

        /* ── Up Next ───────────────────────────── */
        .up-next {
            margin-top: 2rem;
            border-top: 1px solid var(--border);
            padding-top: 1.2rem;
        }
        .up-next-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-dim);
            margin-bottom: 0.4rem;
        }
        .up-next-title {
            font-size: 0.95rem;
            font-weight: 500;
        }
        .up-next-time {
            font-family: 'DM Mono', monospace;
            font-size: 0.8rem;
            color: var(--text-dim);
            margin-top: 0.15rem;
        }

        /* ── Video container ───────────────────── */
        .video-wrap {
            display: none;
            width: 100%;
            max-width: 480px;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            background: #000;
        }
        .video-wrap video {
            width: 100%;
            display: block;
        }
        .video-wrap.visible { display: block; }

        /* ── Offline State ─────────────────────── */
        .offline-msg {
            text-align: center;
            padding: 2rem 0;
        }
        .offline-msg .icon { font-size: 2.5rem; opacity: 0.4; margin-bottom: 0.5rem; }
        .offline-msg .text { color: var(--text-dim); }

        /* ── Loading ───────────────────────────── */
        .loading {
            display: flex; align-items: center; justify-content: center;
            gap: 0.5rem; padding: 2rem; color: var(--text-dim);
        }
        .loading .spinner {
            width: 20px; height: 20px;
            border: 2px solid var(--border);
            border-top-color: var(--warm);
            border-radius: 50%;
            animation: sp 0.6s linear infinite;
        }
        @keyframes sp { to { transform: rotate(360deg); } }

        /* ── Responsive ────────────────────────── */
        @media (max-width: 520px) {
            .player-card { padding: 1.5rem; border-radius: 16px; }
            .cover-wrap { max-width: 200px; }
        }

        .visualizer {
            display: flex; align-items: flex-end; justify-content: center;
            gap: 3px; height: 30px; margin-bottom: 1rem;
        }
        .visualizer .bar {
            width: 4px; border-radius: 2px;
            background: var(--warm);
            transition: height 0.15s;
        }
    </style>
</head>
<body>
    <div class="ambient"></div>

    <div class="container">
        <header class="station-header">
            <h1 class="name"><?= htmlspecialchars($stationName) ?></h1>
            <p class="tagline"><?= htmlspecialchars($tagline) ?></p>
        </header>

        <div class="video-wrap" id="videoWrap">
            <video id="videoPlayer" playsinline></video>
        </div>

        <div class="player-card" id="playerCard">
            <div class="loading" id="loadingState">
                <div class="spinner"></div>
                <span>Tuning in...</span>
            </div>

            <div id="playerContent" style="display:none">
                <!-- Status -->
                <div class="status-bar">
                    <div class="status-dot" id="statusDot"></div>
                    <span class="status-label" id="statusLabel">—</span>
                </div>

                <!-- Cover Art -->
                <div class="cover-wrap" id="coverWrap">
                    <span class="cover-placeholder" id="coverPlaceholder">📻</span>
                    <img id="coverImg" style="display:none" alt="Cover">
                </div>

                <!-- Mini Visualizer -->
                <div class="visualizer" id="visualizer" style="display:none">
                    <div class="bar"></div><div class="bar"></div><div class="bar"></div>
                    <div class="bar"></div><div class="bar"></div><div class="bar"></div>
                    <div class="bar"></div><div class="bar"></div><div class="bar"></div>
                    <div class="bar"></div><div class="bar"></div><div class="bar"></div>
                </div>

                <!-- Track Info -->
                <div class="track-info">
                    <div class="track-title" id="trackTitle">—</div>
                    <div class="track-artist" id="trackArtist"></div>
                    <div class="track-desc" id="trackDesc"></div>
                </div>

                <!-- Progress -->
                <div class="progress-wrap">
                    <div class="progress-track" id="progressTrack">
                        <div class="progress-played" id="progressPlayed"></div>
                    </div>
                    <div class="progress-times">
                        <span id="timeElapsed">0:00</span>
                        <span id="timeRemaining">0:00</span>
                    </div>
                </div>

                <!-- Controls -->
                <div class="controls">
                    <div class="vol-wrap">
                        <span class="vol-icon" id="volIcon" onclick="toggleMute()">🔊</span>
                        <input type="range" class="vol-slider" id="volSlider" min="0" max="1" step="0.01" value="0.8">
                    </div>
                    <button class="play-btn" id="playBtn" onclick="togglePlay()">
                        <svg viewBox="0 0 24 24" id="playIcon">
                            <polygon points="6,3 20,12 6,21"></polygon>
                        </svg>
                    </button>
                    <div style="width:100px"></div> <!-- Spacer for centering -->
                </div>

                <!-- Up Next -->
                <div class="up-next" id="upNext" style="display:none">
                    <div class="up-next-label">Up Next</div>
                    <div class="up-next-title" id="nextTitle">—</div>
                    <div class="up-next-time" id="nextTime">—</div>
                </div>
            </div>

            <!-- Offline State -->
            <div id="offlineState" style="display:none">
                <div class="offline-msg">
                    <div class="icon">📡</div>
                    <div class="text">
                        <p>We're currently off air.</p>
                        <p id="offlineNext"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <audio id="audioPlayer" preload="auto"></audio>

    <script>
    const BASE = '<?= $baseUrl ?>';
    const SYNC_INTERVAL = <?= SYNC_INTERVAL ?>;
    const MAX_DRIFT = <?= MAX_DRIFT ?>;

    let audioEl = document.getElementById('audioPlayer');
    let videoEl = document.getElementById('videoPlayer');
    let activePlayer = null; // reference to current audio/video element
    let isPlaying = false;
    let shouldAutoPlay = false; // track if user wants continuous playback
    let currentMedia = null;
    let syncTimer = null;
    let checkTimer = null;
    let visualizerInterval = null;

    // ── Core: Fetch Now Playing & Sync ──────────
    async function fetchNowPlaying() {
        const requestStartTime = Date.now();
        try {
            const resp = await fetch(BASE + '/api/now-playing.php?_=' + Date.now());
            const data = await resp.json();
            const requestDuration = (Date.now() - requestStartTime) / 1000; // in seconds
            
            document.getElementById('loadingState').style.display = 'none';
            
            if (data.status === 'offline') {
                showOffline(data);
                scheduleNextCheck(data.next_check_in || 30);
                return;
            }
            
            document.getElementById('playerContent').style.display = 'block';
            document.getElementById('offlineState').style.display = 'none';
            
            // Update status indicator
            updateStatus(data.status);
            
            // Check if we need to change media or just sync
            const mediaChanged = !currentMedia || currentMedia.id !== data.media.id;

            if (mediaChanged) {
                loadMedia(data, requestDuration);
            } else {
                // Just sync the position (accounting for request time)
                syncPosition(data.offset + requestDuration);
            }
            
            // Update track info
            updateTrackInfo(data);

            // Update progress (accounting for request time)
            updateProgress(data.offset + requestDuration, data.media.duration);
            
            // Update "up next"
            updateNext(data.next);
            
            // Schedule next check when current media ends
            scheduleNextCheck(data.next_check_in);
            
            // Report duration back if the server has 0
            if (data.media.duration === 0 && activePlayer && activePlayer.duration) {
                reportDuration(data.media.id, activePlayer.duration);
            }
            
        } catch (err) {
            console.error('Fetch error:', err);
            // Retry in 10 seconds
            scheduleNextCheck(10);
        }
        play();
    }

    function loadMedia(data, requestDuration = 0) {
        currentMedia = data.media;
        const isVideo = data.media.media_type === 'video';

        // Swap active player
        if (isVideo) {
            audioEl.pause();
            audioEl.src = '';
            activePlayer = videoEl;
            document.getElementById('videoWrap').classList.add('visible');
            document.getElementById('coverWrap').style.display = 'none';
        } else {
            videoEl.pause();
            videoEl.src = '';
            activePlayer = audioEl;
            document.getElementById('videoWrap').classList.remove('visible');
            document.getElementById('coverWrap').style.display = '';
        }

        activePlayer.src = data.media.url;
        // Adjust offset to account for network latency and processing time
        activePlayer.currentTime = data.offset + requestDuration;
        activePlayer.volume = document.getElementById('volSlider').value;

        // Auto-play if user wants continuous playback
        if (shouldAutoPlay) {
            activePlayer.play().then(() => {
                setPlayingState(true);
            }).catch(err => {
                console.warn('Autoplay failed:', err);
                setPlayingState(false);
            });
        }

        // Update cover
        const coverImg = document.getElementById('coverImg');
        const placeholder = document.getElementById('coverPlaceholder');
        if (data.media.cover_image) {
            coverImg.src = data.media.cover_image;
            coverImg.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            coverImg.style.display = 'none';
            placeholder.style.display = '';
            placeholder.textContent = isVideo ? '🎬' : '📻';
        }
    }

    function syncPosition(expectedOffset) {
        if (!activePlayer || !isPlaying) return;
        const actualOffset = activePlayer.currentTime;
        const drift = Math.abs(actualOffset - expectedOffset);
        
        if (drift > MAX_DRIFT) {
            console.log(`Sync correction: drift ${drift.toFixed(1)}s`);
            activePlayer.currentTime = expectedOffset;
        }
    }

    // ── UI Updates ──────────────────────────────
    function updateStatus(status) {
        const dot = document.getElementById('statusDot');
        const label = document.getElementById('statusLabel');
        
        dot.className = 'status-dot ' + status;
        label.className = 'status-label ' + status;
        
        if (status === 'scheduled') {
            label.textContent = 'ON AIR';
            dot.classList.add('live');
        } else if (status === 'loop') {
            label.textContent = 'NOW PLAYING';
        } else {
            label.textContent = 'OFF AIR';
        }
    }

    function updateTrackInfo(data) {
        document.getElementById('trackTitle').textContent = 
            (data.status === 'scheduled' && data.schedule_title) 
                ? data.schedule_title 
                : data.media.title;
        document.getElementById('trackArtist').textContent = data.media.artist || '';
        
        const desc = data.schedule_desc || data.media.description || '';
        const descEl = document.getElementById('trackDesc');
        descEl.textContent = desc;
        descEl.style.display = desc ? '' : 'none';
    }

    function updateNext(next) {
        const wrap = document.getElementById('upNext');
        if (!next) { wrap.style.display = 'none'; return; }
        
        wrap.style.display = '';
        document.getElementById('nextTitle').textContent = next.title || '—';
        
        if (next.start_time) {
            const dt = new Date(next.start_time);
            document.getElementById('nextTime').textContent = dt.toLocaleString('en-US', {
                weekday: 'short', hour: '2-digit', minute: '2-digit'
            });
        } else if (next.starts_in) {
            document.getElementById('nextTime').textContent = 'In ' + formatTime(next.starts_in);
        }
    }

    function showOffline(data) {
        document.getElementById('playerContent').style.display = 'none';
        document.getElementById('offlineState').style.display = 'block';
        
        const nextEl = document.getElementById('offlineNext');
        if (data.next && data.next.start_time) {
            const dt = new Date(data.next.start_time);
            nextEl.textContent = 'Next broadcast: ' + dt.toLocaleString('en-US', {
                weekday: 'long', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } else {
            nextEl.textContent = 'Check back soon!';
        }
    }

    // ── Progress Tracking ───────────────────────
    let progressInterval = null;
    let trackStartOffset = 0;
    let trackStartTime = 0;

    function updateProgress(offset, duration) {
        trackStartOffset = offset;
        trackStartTime = Date.now();
        
        clearInterval(progressInterval);
        progressInterval = setInterval(() => {
            if (!isPlaying || !currentMedia) return;
            
            const elapsed = trackStartOffset + (Date.now() - trackStartTime) / 1000;
            const pct = Math.min((elapsed / duration) * 100, 100);
            
            document.getElementById('progressPlayed').style.width = pct + '%';
            document.getElementById('timeElapsed').textContent = formatTime(elapsed);
            document.getElementById('timeRemaining').textContent = '-' + formatTime(Math.max(duration - elapsed, 0));
        }, 250);
    }

    // ── Playback Controls ───────────────────────
    function togglePlay() {
        if (isPlaying) {
            pause();
        } else {
            play();
        }
    }

    function play() {
        shouldAutoPlay = true; // Enable continuous playback

        if (!activePlayer || !activePlayer.src) {
            // First play — fetch and start
            fetchNowPlaying().then(() => {
                if (activePlayer) {
                    activePlayer.play().then(() => {
                        setPlayingState(true);
                    }).catch(err => {
                        console.warn('Autoplay blocked:', err);
                    });
                }
            });
            return;
        }

        activePlayer.play().then(() => {
            setPlayingState(true);
        }).catch(() => {});
    }

    function pause() {
        shouldAutoPlay = false; // Disable continuous playback
        if (activePlayer) activePlayer.pause();
        setPlayingState(false);
    }

    function setPlayingState(playing) {
        isPlaying = playing;
        const icon = document.getElementById('playIcon');
        
        if (playing) {
            icon.innerHTML = '<rect x="5" y="3" width="4" height="18"></rect><rect x="15" y="3" width="4" height="18"></rect>';
            startVisualizer();
        } else {
            icon.innerHTML = '<polygon points="6,3 20,12 6,21"></polygon>';
            stopVisualizer();
        }
    }

    function toggleMute() {
        if (!activePlayer) return;
        const slider = document.getElementById('volSlider');
        const icon = document.getElementById('volIcon');
        
        if (activePlayer.volume > 0) {
            activePlayer._prevVol = activePlayer.volume;
            activePlayer.volume = 0;
            slider.value = 0;
            icon.textContent = '🔇';
        } else {
            activePlayer.volume = activePlayer._prevVol || 0.8;
            slider.value = activePlayer.volume;
            icon.textContent = '🔊';
        }
    }

    document.getElementById('volSlider').addEventListener('input', e => {
        if (activePlayer) {
            activePlayer.volume = parseFloat(e.target.value);
            document.getElementById('volIcon').textContent = 
                e.target.value == 0 ? '🔇' : e.target.value < 0.5 ? '🔉' : '🔊';
        }
    });

    // ── Visualizer ──────────────────────────────
    function startVisualizer() {
        const viz = document.getElementById('visualizer');
        viz.style.display = 'flex';
        const bars = viz.querySelectorAll('.bar');
        
        clearInterval(visualizerInterval);
        visualizerInterval = setInterval(() => {
            bars.forEach(bar => {
                const h = 4 + Math.random() * 26;
                bar.style.height = h + 'px';
                bar.style.opacity = 0.4 + Math.random() * 0.6;
            });
        }, 150);
    }

    function stopVisualizer() {
        clearInterval(visualizerInterval);
        const bars = document.querySelectorAll('.visualizer .bar');
        bars.forEach(bar => { bar.style.height = '4px'; bar.style.opacity = '0.3'; });
    }

    // ── Timers ──────────────────────────────────
    function scheduleNextCheck(seconds) {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(fetchNowPlaying, Math.max(seconds, 2) * 1000);
    }

    // Periodic sync to correct any drift
    setInterval(() => {
        if (isPlaying) fetchNowPlaying();
    }, SYNC_INTERVAL * 1000);

    // ── Report Duration (JS fallback) ───────────
    async function reportDuration(mediaId, duration) {
        try {
            await fetch(BASE + '/api/media.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: mediaId, duration })
            });
        } catch (e) { /* silent */ }
    }

    // ── Utilities ───────────────────────────────
    function formatTime(secs) {
        secs = Math.max(0, Math.round(secs));
        const h = Math.floor(secs / 3600);
        const m = Math.floor((secs % 3600) / 60);
        const s = secs % 60;
        if (h > 0) return h + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        return m + ':' + String(s).padStart(2, '0');
    }

    // ── Init ────────────────────────────────────
    fetchNowPlaying();

    // Handle media ending
    audioEl.addEventListener('ended', fetchNowPlaying);
    videoEl.addEventListener('ended', fetchNowPlaying);
    </script>
</body>
</html>
