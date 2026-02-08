<?php
require_once __DIR__ . '/../config.php';

session_start();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['radio_admin'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $loginError = 'Invalid credentials';
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['radio_admin']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$isLoggedIn = !empty($_SESSION['radio_admin']);
$baseUrl = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Online Radio</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0b;
            --surface: #141416;
            --surface-2: #1c1c1f;
            --surface-3: #242428;
            --border: #2a2a2e;
            --text: #e8e8ed;
            --text-dim: #8e8e93;
            --accent: #e8a838;
            --accent-hover: #f0b848;
            --accent-dim: rgba(232, 168, 56, 0.12);
            --danger: #e84848;
            --danger-dim: rgba(232, 72, 72, 0.12);
            --success: #48c878;
            --success-dim: rgba(72, 200, 120, 0.12);
            --radius: 10px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DM Sans', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ── Login ─────────────────────────────── */
        .login-wrap {
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 2rem;
        }
        .login-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%; max-width: 380px;
        }
        .login-box h1 {
            font-size: 1.5rem; margin-bottom: 0.3rem; color: var(--accent);
        }
        .login-box p { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 1.5rem; }
        .login-error {
            background: var(--danger-dim); color: var(--danger);
            padding: 0.6rem 1rem; border-radius: 8px; margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        /* ── Form Elements ─────────────────────── */
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block; font-size: 0.8rem; font-weight: 600;
            color: var(--text-dim); margin-bottom: 0.35rem;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        input[type="text"], input[type="password"], input[type="datetime-local"],
        input[type="number"], select, textarea {
            width: 100%; padding: 0.65rem 0.85rem;
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text);
            font-family: inherit; font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--accent);
        }
        textarea { resize: vertical; min-height: 80px; }

        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.65rem 1.3rem; border: none; border-radius: 8px;
            font-family: inherit; font-size: 0.9rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--accent); color: #000;
        }
        .btn-primary:hover { background: var(--accent-hover); }
        .btn-secondary {
            background: var(--surface-3); color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { border-color: var(--text-dim); }
        .btn-danger {
            background: var(--danger-dim); color: var(--danger);
        }
        .btn-danger:hover { background: var(--danger); color: #fff; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-full { width: 100%; justify-content: center; }

        /* ── Layout ────────────────────────────── */
        .app-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            position: sticky; top: 0; z-index: 100;
        }
        .app-header h1 { font-size: 1.2rem; }
        .app-header h1 span { color: var(--accent); }
        .header-actions { display: flex; gap: 0.75rem; align-items: center; }

        .tabs {
            display: flex; gap: 0; border-bottom: 1px solid var(--border);
            background: var(--surface);
            padding: 0 2rem; overflow-x: auto;
        }
        .tab {
            padding: 0.85rem 1.3rem; cursor: pointer;
            font-size: 0.9rem; font-weight: 500;
            color: var(--text-dim); border-bottom: 2px solid transparent;
            transition: all 0.2s; white-space: nowrap;
        }
        .tab:hover { color: var(--text); }
        .tab.active { color: var(--accent); border-bottom-color: var(--accent); }

        .content { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .panel { display: none; }
        .panel.active { display: block; }

        /* ── Upload Zone ───────────────────────── */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 16px; padding: 3rem 2rem;
            text-align: center; cursor: pointer;
            transition: all 0.3s; margin-bottom: 1.5rem;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--accent);
            background: var(--accent-dim);
        }
        .upload-zone .icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .upload-zone .label { font-weight: 600; margin-bottom: 0.3rem; }
        .upload-zone .hint { font-size: 0.85rem; color: var(--text-dim); }

        .upload-form {
            display: none;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .upload-form.visible { display: block; }
        .upload-form .file-info {
            background: var(--accent-dim);
            padding: 0.8rem 1rem; border-radius: 8px;
            margin-bottom: 1rem; font-size: 0.9rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .upload-form .file-info .name { font-weight: 600; color: var(--accent); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .upload-progress {
            display: none; margin-top: 1rem;
        }
        .progress-bar {
            height: 6px; background: var(--surface-3);
            border-radius: 3px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; background: var(--accent);
            border-radius: 3px; transition: width 0.3s;
            width: 0;
        }
        .progress-text {
            font-size: 0.8rem; color: var(--text-dim);
            margin-top: 0.4rem; text-align: center;
        }

        /* ── Media Library ─────────────────────── */
        .media-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        .media-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.2rem;
            transition: border-color 0.2s;
        }
        .media-card:hover { border-color: var(--text-dim); }
        .media-card .card-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 0.6rem;
        }
        .media-card .title { font-weight: 600; font-size: 1rem; }
        .media-card .artist { color: var(--text-dim); font-size: 0.85rem; }
        .media-card .meta {
            display: flex; gap: 0.75rem; margin-top: 0.6rem;
            font-size: 0.8rem; color: var(--text-dim);
        }
        .media-card .meta span { display: flex; align-items: center; gap: 0.3rem; }
        .badge {
            font-size: 0.7rem; padding: 0.15rem 0.5rem;
            border-radius: 4px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .badge-audio { background: #1a2a3a; color: #7baed6; }
        .badge-video { background: #2a1a3a; color: #b07bd6; }
        .badge-loop { background: var(--accent-dim); color: var(--accent); }
        .card-actions { display: flex; gap: 0.4rem; margin-top: 0.8rem; }

        /* ── Schedule ──────────────────────────── */
        .schedule-form-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem; margin-bottom: 2rem;
        }
        .schedule-form-wrap h3 {
            margin-bottom: 1rem; font-size: 1.1rem;
        }
        .schedule-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .schedule-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem 1.2rem;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 1rem; align-items: center;
        }
        .schedule-item .time {
            font-family: 'DM Mono', monospace;
            font-size: 0.85rem; color: var(--accent);
            text-align: center; min-width: 110px;
        }
        .schedule-item .time .date { font-size: 0.75rem; color: var(--text-dim); }
        .schedule-item .info .title { font-weight: 600; }
        .schedule-item .info .artist { font-size: 0.85rem; color: var(--text-dim); }
        .schedule-item .duration {
            font-family: 'DM Mono', monospace;
            font-size: 0.85rem; color: var(--text-dim);
        }
        .schedule-item.playing {
            border-color: var(--accent);
            background: var(--accent-dim);
        }

        /* ── Loop Library ──────────────────────── */
        .loop-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .loop-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0.8rem 1rem;
            display: flex; align-items: center; gap: 1rem;
            cursor: grab;
        }
        .loop-item:active { cursor: grabbing; }
        .loop-item .grip { color: var(--text-dim); font-size: 1.2rem; }
        .loop-item .pos {
            font-family: 'DM Mono', monospace;
            font-size: 0.8rem; color: var(--accent);
            width: 2rem; text-align: center;
        }
        .loop-item .loop-info { flex: 1; }
        .loop-item .loop-info .title { font-weight: 600; font-size: 0.95rem; }
        .loop-item .loop-info .duration { font-size: 0.8rem; color: var(--text-dim); }

        /* ── Alerts ────────────────────────────── */
        .alert {
            padding: 0.8rem 1.2rem; border-radius: 8px;
            margin-bottom: 1rem; font-size: 0.9rem;
        }
        .alert-success { background: var(--success-dim); color: var(--success); }
        .alert-error { background: var(--danger-dim); color: var(--danger); }
        .alert-warning { background: rgba(232, 168, 56, 0.12); color: var(--accent); }
        .alert-info { background: rgba(100, 160, 240, 0.12); color: #78b0e8; }

        .empty-state {
            text-align: center; padding: 3rem; color: var(--text-dim);
        }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 0.5rem; }

        /* ── Responsive ────────────────────────── */
        @media (max-width: 768px) {
            .content { padding: 1rem; }
            .form-row { grid-template-columns: 1fr; }
            .schedule-item { grid-template-columns: 1fr; gap: 0.5rem; }
            .app-header { padding: 0.8rem 1rem; }
            .tabs { padding: 0 1rem; }
        }

        .checkbox-row {
            display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;
        }
        .checkbox-row input[type="checkbox"] {
            width: 18px; height: 18px; accent-color: var(--accent);
        }
        .section-title {
            font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem;
        }
        .section-subtitle {
            font-size: 0.9rem; color: var(--text-dim); margin-bottom: 1.5rem;
        }
        .total-duration {
            background: var(--accent-dim); color: var(--accent);
            padding: 0.6rem 1rem; border-radius: 8px;
            font-family: 'DM Mono', monospace;
            font-size: 0.9rem; margin-bottom: 1rem;
            display: inline-block;
        }

        /* ── Analytics ─────────────────────────── */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .analytics-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
        }
        .analytics-card h3 {
            font-size: 0.9rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .analytics-card .stat {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent);
        }
        .analytics-section {
            margin-bottom: 2rem;
        }
        .analytics-section h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ── Login Screen ──────────────────────────── -->
<div class="login-wrap">
    <div class="login-box">
        <h1>📻 Online Radio</h1>
        <p>Admin panel — sign in to continue</p>
        <?php if (!empty($loginError)): ?>
            <div class="login-error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" value="1" class="btn btn-primary btn-full">Sign In</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── Admin App ─────────────────────────────── -->
<header class="app-header">
    <h1><span>📻</span> Online Radio Admin</h1>
    <div class="header-actions">
        <a href="<?= $baseUrl ?>/player.php" class="btn btn-secondary btn-sm" target="_blank">🎧 Open Player</a>
        <a href="?logout=1" class="btn btn-secondary btn-sm">Sign Out</a>
    </div>
</header>

<nav class="tabs">
    <div class="tab active" data-tab="upload">Upload</div>
    <div class="tab" data-tab="library">Media Library</div>
    <div class="tab" data-tab="schedule">Schedule</div>
    <div class="tab" data-tab="loop">Loop / Filler</div>
    <div class="tab" data-tab="live">Live Streams</div>
    <div class="tab" data-tab="settings">Settings</div>
    <div class="tab" data-tab="analytics">Analytics</div>
</nav>

<div class="content">
    <div id="alerts"></div>

    <!-- ── Upload Panel ──────────────────────── -->
    <div class="panel active" id="panel-upload">
        <div class="section-title">Upload Media</div>
        <div class="section-subtitle">Upload audio or video files for your radio station.</div>

        <div class="upload-zone" id="uploadZone">
            <div class="icon">📁</div>
            <div class="label">Drop files here or click to browse</div>
            <div class="hint">Supports MP3, WAV, OGG, AAC, M4A, MP4, WebM — up to 500 MB</div>
            <input type="file" id="fileInput" hidden accept="audio/*,video/*">
        </div>

        <div class="upload-form" id="uploadForm">
            <div class="file-info">
                <span class="name" id="fileName">—</span>
                <span id="fileDuration">Detecting duration...</span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="uploadTitle" placeholder="Show name or track title">
                </div>
                <div class="form-group">
                    <label>Artist / Presenter</label>
                    <input type="text" id="uploadArtist" placeholder="Optional">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea id="uploadDesc" placeholder="Brief description (optional)"></textarea>
            </div>

            <div class="form-group">
                <label>Cover Image</label>
                <input type="file" id="coverInput" accept="image/*">
            </div>

            <div class="checkbox-row">
                <input type="checkbox" id="uploadIsLoop">
                <label for="uploadIsLoop" style="text-transform:none;font-size:0.9rem;color:var(--text)">
                    Add to loop/filler playlist (plays when nothing is scheduled)
                </label>
            </div>

            <div class="upload-progress" id="uploadProgress">
                <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
                <div class="progress-text" id="progressText">Uploading... 0%</div>
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:1.25rem">
                <button class="btn btn-primary" id="uploadBtn" onclick="submitUpload()">Upload Media</button>
                <button class="btn btn-secondary" onclick="cancelUpload()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- ── Media Library Panel ───────────────── -->
    <div class="panel" id="panel-library">
        <div class="section-title">Media Library</div>
        <div class="section-subtitle">All uploaded media. Click to preview, manage, or schedule.</div>
        <div class="media-grid" id="mediaGrid">
            <div class="empty-state">
                <div class="icon">🎵</div>
                <p>No media uploaded yet</p>
            </div>
        </div>
    </div>

    <!-- ── Schedule Panel ────────────────────── -->
    <div class="panel" id="panel-schedule">
        <div class="section-title">Programme Schedule</div>
        <div class="section-subtitle">Set what plays and when. All listeners hear the same thing at the same time.</div>

        <div class="schedule-form-wrap">
            <h3>Add to Schedule</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Select Media</label>
                    <select id="schedMedia">
                        <option value="">Loading media...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date & Time</label>
                    <input type="datetime-local" id="schedStart">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Schedule Title (optional override)</label>
                    <input type="text" id="schedTitle" placeholder="e.g. Morning Devotional">
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input type="text" id="schedDesc" placeholder="Brief note">
                </div>
            </div>
            <button class="btn btn-primary" onclick="addSchedule()" style="margin-top:0.5rem">Add to Schedule</button>
        </div>

        <div class="section-title" style="margin-top:1rem">Upcoming Schedule</div>
        <div class="schedule-list" id="scheduleList">
            <div class="empty-state"><div class="icon">📅</div><p>No upcoming schedules</p></div>
        </div>
    </div>

    <!-- ── Loop / Filler Panel ───────────────── -->
    <div class="panel" id="panel-loop">
        <div class="section-title">Loop / Filler Playlist</div>
        <div class="section-subtitle">
            These tracks play in order when there's nothing scheduled, and loop back to the start.
            All listeners stay synchronized. Drag to reorder.
        </div>
        <div class="total-duration" id="loopTotal">Total loop duration: —</div>
        <div class="loop-list" id="loopList">
            <div class="empty-state"><div class="icon">🔁</div><p>No filler tracks. Upload media and mark as "loop" to add them here.</p></div>
        </div>
    </div>

    <!-- ── Live Streams Panel ──────────────── -->
    <div class="panel" id="panel-live">
        <div class="section-title">Live Streams</div>
        <div class="section-subtitle">Manage social media accounts for live broadcasting. The system will automatically check if they are live.</div>

        <div class="schedule-form-wrap">
            <h3>Add Live Stream Source</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Platform</label>
                    <select id="livePlatform">
                        <option value="youtube">YouTube</option>
                        <option value="facebook">Facebook</option>
                        <option value="tiktok">TikTok</option>
                        <option value="instagram">Instagram</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Account Name / Channel ID</label>
                    <input type="text" id="liveAccountName" placeholder="e.g. YourChannelName">
                </div>
            </div>
            <button class="btn btn-primary" onclick="addLiveStream()" style="margin-top:0.5rem">Add Live Source</button>
        </div>

        <div class="section-title" style="margin-top:1rem">Managed Live Sources</div>
        <div class="schedule-list" id="liveStreamList">
            <div class="empty-state"><div class="icon">📡</div><p>No live stream sources configured.</p></div>
        </div>
    </div>

    <!-- ── Settings Panel ────────────────── -->
    <div class="panel" id="panel-settings">
        <div class="section-title">Settings</div>
        <div class="section-subtitle">Manage general settings for the radio station.</div>

        <div class="schedule-form-wrap">
            <h3>API Keys</h3>
            <div class="form-group">
                <label>YouTube API Key</label>
                <input type="text" id="settingYoutubeApiKey" placeholder="Enter your YouTube Data API v3 key">
            </div>
            <button class="btn btn-primary" onclick="saveSettings()" style="margin-top:0.5rem">Save Settings</button>
        </div>
    </div>

    <!-- ── Analytics Panel ───────────────── -->
    <div class="panel" id="panel-analytics">
        <div class="section-title">Analytics Dashboard</div>
        <div class="section-subtitle">Insights into your station's listenership.</div>

        <div class="analytics-grid">
            <div class="analytics-card">
                <h3>Total Listens</h3>
                <div class="stat" id="totalListens">...</div>
            </div>
            <div class="analytics-card">
                <h3>Unique Listeners (by IP)</h3>
                <div class="stat" id="uniqueListeners">...</div>
            </div>
        </div>

        <div class="analytics-section">
            <h3>Most Popular Tracks</h3>
            <div id="popularTracksList">
                <div class="empty-state"><div class="icon">📈</div><p>No listening data yet.</p></div>
            </div>
        </div>

        <div class="analytics-section">
            <h3>Peak Listening Times (by hour)</h3>
            <div id="peakTimesList">
                <div class="empty-state"><div class="icon">📈</div><p>No listening data yet.</p></div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE = '<?= $baseUrl ?>';
let selectedFile = null;
let detectedDuration = 0;

// ── Tab Navigation ──────────────────────────────
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
        
        // Refresh data when switching tabs
        const tabName = tab.dataset.tab;
        if (tabName === 'library') loadLibrary();
        if (tabName === 'schedule') { loadSchedule(); loadMediaDropdown(); }
        if (tabName === 'loop') loadLoopMedia();
        if (tabName === 'live') loadLiveStreams();
        if (tabName === 'settings') loadSettings();
        if (tabName === 'analytics') loadAnalytics();
    });
});

// ── Alerts ──────────────────────────────────────
function showAlert(msg, type = 'success') {
    const el = document.getElementById('alerts');
    const div = document.createElement('div');
    div.className = 'alert alert-' + type;
    div.textContent = msg;
    el.prepend(div);
    setTimeout(() => div.remove(), 5000);
}

// ── Upload Logic ────────────────────────────────
const zone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');

zone.addEventListener('click', () => fileInput.click());
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', () => {
    if (fileInput.files.length) handleFile(fileInput.files[0]);
});

function handleFile(file) {
    selectedFile = file;
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('uploadTitle').value = file.name.replace(/\.[^.]+$/, '');
    document.getElementById('uploadForm').classList.add('visible');
    
    // Detect duration via browser
    const url = URL.createObjectURL(file);
    const el = file.type.startsWith('video') ? document.createElement('video') : document.createElement('audio');
    el.preload = 'metadata';
    el.onloadedmetadata = () => {
        detectedDuration = el.duration;
        document.getElementById('fileDuration').textContent = formatDuration(el.duration);
        URL.revokeObjectURL(url);
    };
    el.onerror = () => {
        document.getElementById('fileDuration').textContent = 'Could not detect duration';
        detectedDuration = 0;
    };
    el.src = url;
}

function cancelUpload() {
    selectedFile = null;
    detectedDuration = 0;
    fileInput.value = '';
    document.getElementById('uploadForm').classList.remove('visible');
}

async function submitUpload() {
    if (!selectedFile) return;
    
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    
    const form = new FormData();
    form.append('file', selectedFile);
    form.append('title', document.getElementById('uploadTitle').value);
    form.append('artist', document.getElementById('uploadArtist').value);
    form.append('description', document.getElementById('uploadDesc').value);
    form.append('is_loop', document.getElementById('uploadIsLoop').checked ? 1 : 0);
    form.append('duration', detectedDuration);
    
    const coverFile = document.getElementById('coverInput').files[0];
    if (coverFile) form.append('cover', coverFile);
    
    const progress = document.getElementById('uploadProgress');
    const fill = document.getElementById('progressFill');
    const text = document.getElementById('progressText');
    progress.style.display = 'block';
    
    try {
        const xhr = new XMLHttpRequest();
        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const pct = Math.round(e.loaded / e.total * 100);
                fill.style.width = pct + '%';
                text.textContent = 'Uploading... ' + pct + '%';
            }
        };
        
        const result = await new Promise((resolve, reject) => {
            xhr.onload = () => {
                try { resolve(JSON.parse(xhr.responseText)); }
                catch { reject(new Error('Invalid response')); }
            };
            xhr.onerror = () => reject(new Error('Upload failed'));
            xhr.open('POST', BASE + '/api/upload.php');
            xhr.send(form);
        });
        
        if (result.success) {
            showAlert('Media uploaded: ' + result.media.title);
            cancelUpload();
        } else {
            showAlert(result.error || 'Upload failed', 'error');
        }
    } catch (err) {
        showAlert('Upload error: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        progress.style.display = 'none';
        fill.style.width = '0';
    }
}

// ── Media Library ───────────────────────────────
async function loadLibrary() {
    try {
        const resp = await fetch(BASE + '/api/media.php');
        const data = await resp.json();
        const grid = document.getElementById('mediaGrid');
        
        if (!data.length) {
            grid.innerHTML = '<div class="empty-state"><div class="icon">🎵</div><p>No media uploaded yet</p></div>';
            return;
        }
        
        grid.innerHTML = data.map(m => `
            <div class="media-card" data-id="${m.id}">
                <div class="card-header">
                    <div>
                        <div class="title">${esc(m.title)}</div>
                        ${m.artist ? `<div class="artist">${esc(m.artist)}</div>` : ''}
                    </div>
                    <div style="display:flex;gap:0.3rem">
                        <span class="badge badge-${m.media_type}">${m.media_type}</span>
                        ${m.is_loop == 1 ? '<span class="badge badge-loop">loop</span>' : ''}
                    </div>
                </div>
                <div class="meta">
                    <span>⏱ ${formatDuration(m.duration)}</span>
                    <span>📦 ${formatSize(m.file_size)}</span>
                    <span>📅 ${new Date(m.uploaded_at).toLocaleDateString()}</span>
                </div>
                <div class="card-actions">
                    <button class="btn btn-secondary btn-sm" onclick="previewMedia('${m.url}', '${m.media_type}')">▶ Preview</button>
                    <button class="btn btn-secondary btn-sm" onclick="toggleLoop(${m.id}, ${m.is_loop})">${m.is_loop == 1 ? '✕ Remove from Loop' : '🔁 Add to Loop'}</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMedia(${m.id}, '${esc(m.title)}')">🗑 Delete</button>
                </div>
            </div>
        `).join('');
    } catch (err) {
        showAlert('Failed to load media library', 'error');
    }
}

function previewMedia(url, type) {
    const w = window.open('', '_blank', 'width=500,height=300');
    const tag = type === 'video' ? 'video' : 'audio';
    w.document.write(`
        <html><head><title>Preview</title><style>body{background:#111;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}${tag}{max-width:100%;max-height:100%;}</style></head>
        <body><${tag} controls autoplay src="${url}"></${tag}></body></html>
    `);
}

async function toggleLoop(id, currentLoop) {
    const newLoop = currentLoop == 1 ? 0 : 1;
    await fetch(BASE + '/api/media.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, is_loop: newLoop })
    });
    showAlert(newLoop ? 'Added to loop playlist' : 'Removed from loop playlist');
    loadLibrary();
}

async function deleteMedia(id, title) {
    if (!confirm(`Delete "${title}"? This also removes it from all schedules.`)) return;
    await fetch(BASE + '/api/media.php?id=' + id, { method: 'DELETE' });
    showAlert('Media deleted');
    loadLibrary();
}

// ── Schedule ────────────────────────────────────
async function loadMediaDropdown() {
    const resp = await fetch(BASE + '/api/media.php?is_loop=0');
    const data = await resp.json();
    const select = document.getElementById('schedMedia');
    
    // Also load loop media (they can be scheduled too)
    const resp2 = await fetch(BASE + '/api/media.php');
    const allData = await resp2.json();
    
    select.innerHTML = '<option value="">— Select media —</option>' +
        allData.filter(m => m.duration > 0).map(m =>
            `<option value="${m.id}">${esc(m.title)} (${formatDuration(m.duration)})${m.is_loop == 1 ? ' [loop]' : ''}</option>`
        ).join('');
    
    // Set default start time to next hour
    const now = new Date();
    now.setHours(now.getHours() + 1, 0, 0, 0);
    document.getElementById('schedStart').value = toLocalISO(now);
}

async function addSchedule() {
    const mediaId = document.getElementById('schedMedia').value;
    const startTime = document.getElementById('schedStart').value;
    
    if (!mediaId || !startTime) {
        showAlert('Please select media and a start time', 'warning');
        return;
    }
    
    // Convert datetime-local to MySQL format
    const dt = new Date(startTime);
    const mysqlTime = dt.getFullYear() + '-' +
        String(dt.getMonth()+1).padStart(2,'0') + '-' +
        String(dt.getDate()).padStart(2,'0') + ' ' +
        String(dt.getHours()).padStart(2,'0') + ':' +
        String(dt.getMinutes()).padStart(2,'0') + ':00';
    
    try {
        const resp = await fetch(BASE + '/api/schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                media_id: parseInt(mediaId),
                start_time: mysqlTime,
                title: document.getElementById('schedTitle').value,
                description: document.getElementById('schedDesc').value,
            })
        });
        const result = await resp.json();
        
        if (result.success) {
            showAlert('Scheduled: ' + result.schedule.title);
            if (result.warnings) result.warnings.forEach(w => showAlert(w, 'warning'));
            loadSchedule();
            // Reset form
            document.getElementById('schedTitle').value = '';
            document.getElementById('schedDesc').value = '';
        } else {
            showAlert(result.error, 'error');
        }
    } catch (err) {
        showAlert('Failed to create schedule', 'error');
    }
}

async function loadSchedule() {
    try {
        const resp = await fetch(BASE + '/api/schedule.php?range=upcoming');
        const data = await resp.json();
        const list = document.getElementById('scheduleList');
        
        if (!data.length) {
            list.innerHTML = '<div class="empty-state"><div class="icon">📅</div><p>No upcoming schedules</p></div>';
            return;
        }
        
        const now = new Date();
        list.innerHTML = data.map(s => {
            const start = new Date(s.start_time);
            const end = new Date(s.end_time);
            const isPlaying = now >= start && now < end;
            return `
                <div class="schedule-item ${isPlaying ? 'playing' : ''}">
                    <div class="time">
                        <div class="date">${start.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric'})}</div>
                        ${start.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'})}
                    </div>
                    <div class="info">
                        <div class="title">${esc(s.schedule_title || s.media_title)}${isPlaying ? ' 🔴 ON AIR' : ''}</div>
                        <div class="artist">${esc(s.artist || '')}</div>
                    </div>
                    <div class="duration">${formatDuration(s.duration)}</div>
                    <button class="btn btn-danger btn-sm" onclick="deleteSchedule(${s.id})">✕</button>
                </div>
            `;
        }).join('');
    } catch (err) {
        showAlert('Failed to load schedule', 'error');
    }
}

async function deleteSchedule(id) {
    if (!confirm('Remove this schedule entry?')) return;
    await fetch(BASE + '/api/schedule.php?id=' + id, { method: 'DELETE' });
    showAlert('Schedule entry removed');
    loadSchedule();
}

// ── Loop Media ──────────────────────────────────
async function loadLoopMedia() {
    try {
        const resp = await fetch(BASE + '/api/media.php?is_loop=1');
        const data = await resp.json();
        const list = document.getElementById('loopList');
        const total = document.getElementById('loopTotal');
        
        if (!data.length) {
            list.innerHTML = '<div class="empty-state"><div class="icon">🔁</div><p>No filler tracks yet. Upload media and mark as "loop" to add them here.</p></div>';
            total.textContent = 'Total loop duration: 0:00';
            return;
        }
        
        // Sort by loop_position
        data.sort((a, b) => a.loop_position - b.loop_position);
        
        const totalDur = data.reduce((sum, m) => sum + parseFloat(m.duration), 0);
        total.textContent = 'Total loop duration: ' + formatDuration(totalDur) + ' (' + data.length + ' tracks)';
        
        list.innerHTML = data.map((m, i) => `
            <div class="loop-item" draggable="true" data-id="${m.id}">
                <div class="grip">⠿</div>
                <div class="pos">#${i + 1}</div>
                <div class="loop-info">
                    <div class="title">${esc(m.title)}</div>
                    <div class="duration">${formatDuration(m.duration)} · ${m.media_type}</div>
                </div>
                <button class="btn btn-danger btn-sm" onclick="toggleLoop(${m.id}, 1); setTimeout(loadLoopMedia, 300);">✕ Remove</button>
            </div>
        `).join('');
        
        // Drag-and-drop reordering
        initDragReorder();
    } catch (err) {
        showAlert('Failed to load loop media', 'error');
    }
}

function initDragReorder() {
    const list = document.getElementById('loopList');
    let dragEl = null;
    
    list.querySelectorAll('.loop-item').forEach(item => {
        item.addEventListener('dragstart', e => {
            dragEl = item;
            item.style.opacity = '0.4';
        });
        item.addEventListener('dragend', () => {
            dragEl.style.opacity = '1';
            dragEl = null;
        });
        item.addEventListener('dragover', e => {
            e.preventDefault();
            if (dragEl && dragEl !== item) {
                const rect = item.getBoundingClientRect();
                const mid = rect.top + rect.height / 2;
                if (e.clientY < mid) {
                    list.insertBefore(dragEl, item);
                } else {
                    list.insertBefore(dragEl, item.nextSibling);
                }
            }
        });
    });
    
    list.addEventListener('drop', async e => {
        e.preventDefault();
        const order = [...list.querySelectorAll('.loop-item')].map(el => parseInt(el.dataset.id));
        await fetch(BASE + '/api/loop-reorder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order })
        });
        loadLoopMedia();
    });
}

// ── Live Streams ────────────────────────────────
async function loadLiveStreams() {
    try {
        const resp = await fetch(BASE + '/api/live-streams.php');
        const data = await resp.json();
        const list = document.getElementById('liveStreamList');

        if (!data.length) {
            list.innerHTML = '<div class="empty-state"><div class="icon">📡</div><p>No live stream sources configured.</p></div>';
            return;
        }

        list.innerHTML = data.map(s => `
            <div class="schedule-item">
                <div class="info">
                    <div class="title">${esc(s.account_name)} (${s.platform})</div>
                    <div class="artist">${s.is_live ? '🔴 LIVE' : 'Offline'}</div>
                </div>
                <div class="card-actions">
                    <button class="btn btn-secondary btn-sm" onclick="toggleLiveStreamActive(${s.id}, ${s.is_active})">${s.is_active ? 'Deactivate' : 'Activate'}</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteLiveStream(${s.id})">✕</button>
                </div>
            </div>
        `).join('');
    } catch (err) {
        showAlert('Failed to load live streams', 'error');
    }
}

async function addLiveStream() {
    const platform = document.getElementById('livePlatform').value;
    const accountName = document.getElementById('liveAccountName').value;

    if (!accountName) {
        showAlert('Please enter an account name', 'warning');
        return;
    }

    try {
        const resp = await fetch(BASE + '/api/live-streams.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                platform: platform,
                account_name: accountName,
            })
        });
        const result = await resp.json();

        if (result.success) {
            showAlert('Live stream source added');
            loadLiveStreams();
            document.getElementById('liveAccountName').value = '';
        } else {
            showAlert(result.error, 'error');
        }
    } catch (err) {
        showAlert('Failed to add live stream', 'error');
    }
}

async function deleteLiveStream(id) {
    if (!confirm('Remove this live stream source?')) return;
    await fetch(BASE + '/api/live-streams.php?id=' + id, { method: 'DELETE' });
    showAlert('Live stream source removed');
    loadLiveStreams();
}

async function toggleLiveStreamActive(id, isActive) {
    const newStatus = isActive ? 0 : 1;
    await fetch(BASE + '/api/live-streams.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, is_active: newStatus })
    });
    showAlert(newStatus ? 'Live stream activated' : 'Live stream deactivated');
    loadLiveStreams();
}

// ── Settings ──────────────────────────────────
async function loadSettings() {
    try {
        const resp = await fetch(BASE + '/api/settings.php');
        const data = await resp.json();
        document.getElementById('settingYoutubeApiKey').value = data.youtube_api_key || '';
    } catch (err) {
        showAlert('Failed to load settings', 'error');
    }
}

async function saveSettings() {
    const newSettings = {
        youtube_api_key: document.getElementById('settingYoutubeApiKey').value
    };

    try {
        const resp = await fetch(BASE + '/api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newSettings)
        });
        const result = await resp.json();

        if (result.success) {
            showAlert('Settings saved');
        } else {
            showAlert(result.error, 'error');
        }
    } catch (err) {
        showAlert('Failed to save settings', 'error');
    }
}

// ── Analytics ─────────────────────────────────
async function loadAnalytics() {
    try {
        const resp = await fetch(BASE + '/api/analytics.php');
        const data = await resp.json();

        document.getElementById('totalListens').textContent = data.total_listens;
        document.getElementById('uniqueListeners').textContent = data.unique_listeners;

        const popularTracksList = document.getElementById('popularTracksList');
        if (data.popular_tracks && data.popular_tracks.length) {
            popularTracksList.innerHTML = data.popular_tracks.map(track => `
                <div class="schedule-item">
                    <div class="info">
                        <div class="title">${esc(track.title)}</div>
                        <div class="artist">${esc(track.artist)}</div>
                    </div>
                    <div class="duration">Listens: ${track.listen_count}</div>
                </div>
            `).join('');
        } else {
            popularTracksList.innerHTML = '<div class="empty-state"><div class="icon">📈</div><p>No listening data for tracks yet.</p></div>';
        }

        const peakTimesList = document.getElementById('peakTimesList');
        if (data.peak_times && data.peak_times.length) {
            peakTimesList.innerHTML = data.peak_times.map(hour => `
                <div class="schedule-item">
                    <div class="info">
                        <div class="title">${hour.hour}:00 - ${hour.hour}:59</div>
                    </div>
                    <div class="duration">Listens: ${hour.listen_count}</div>
                </div>
            `).join('');
        } else {
            peakTimesList.innerHTML = '<div class="empty-state"><div class="icon">📈</div><p>No listening data for peak times yet.</p></div>';
        }

    } catch (err) {
        showAlert('Failed to load analytics data', 'error');
    }
}

// ── Utilities ───────────────────────────────────
function formatDuration(secs) {
    secs = Math.round(parseFloat(secs) || 0);
    const h = Math.floor(secs / 3600);
    const m = Math.floor((secs % 3600) / 60);
    const s = secs % 60;
    if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    return `${m}:${String(s).padStart(2,'0')}`;
}

function formatSize(bytes) {
    bytes = parseInt(bytes) || 0;
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

function toLocalISO(date) {
    const offset = date.getTimezoneOffset();
    const local = new Date(date.getTime() - offset * 60000);
    return local.toISOString().slice(0, 16);
}

// ── Init ────────────────────────────────────────
loadLibrary();
</script>

<?php endif; ?>
</body>
</html>
