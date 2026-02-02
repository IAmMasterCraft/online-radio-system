<?php
/**
 * TRADITIONAL RADIO SYSTEM - Configuration
 * 
 * Update these values to match your server environment.
 */

// ── Database ──────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_radio');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── Paths ─────────────────────────────────────────
define('BASE_URL', '/radio');                    // URL path to the radio directory
define('UPLOAD_DIR', __DIR__ . '/uploads');       // Absolute path to uploads folder
define('UPLOAD_URL', BASE_URL . '/uploads');      // URL path to uploads folder
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024);    // 500 MB max upload

// ── Media Settings ────────────────────────────────
define('ALLOWED_AUDIO', ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'flac', 'webm']);
define('ALLOWED_VIDEO', ['mp4', 'webm', 'ogg', 'mkv']);
define('ALLOWED_IMAGES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ── ffprobe path (for extracting media duration) ──
// Set to null to use JavaScript-based fallback
define('FFPROBE_PATH', '/usr/bin/ffprobe');

// ── Admin Credentials (change these!) ─────────────
// For production, replace with proper auth system
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'changeme123');

// ── Sync Settings ─────────────────────────────────
// How often the player re-syncs with server (seconds)
define('SYNC_INTERVAL', 30);
// Maximum allowed drift before forcing re-sync (seconds)
define('MAX_DRIFT', 2);

// ── Database Connection ───────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ── Helper: JSON Response ─────────────────────────
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Helper: Get Setting ───────────────────────────
function getSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM radio_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

// ── Helper: Set Setting ───────────────────────────
function setSetting(string $key, string $value): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO radio_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
}

// ── Helper: Extract Duration with ffprobe ─────────
function getMediaDuration(string $filepath): ?float {
    if (FFPROBE_PATH && file_exists(FFPROBE_PATH)) {
        $cmd = escapeshellcmd(FFPROBE_PATH) . ' -v error -show_entries format=duration -of csv=p=0 ' . escapeshellarg($filepath);
        $output = trim(shell_exec($cmd) ?? '');
        if (is_numeric($output)) {
            return (float) $output;
        }
    }
    // Fallback: try with just 'ffprobe' in PATH
    $cmd = 'ffprobe -v error -show_entries format=duration -of csv=p=0 ' . escapeshellarg($filepath) . ' 2>/dev/null';
    $output = trim(shell_exec($cmd) ?? '');
    if (is_numeric($output)) {
        return (float) $output;
    }
    return null; // Duration must be set via JS fallback
}

// ── Simple Auth Check ─────────────────────────────
function requireAdmin(): void {
    session_start();
    if (empty($_SESSION['radio_admin'])) {
        if (basename($_SERVER['SCRIPT_FILENAME']) !== 'index.php') {
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        }
    }
}

function isAdminLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['radio_admin']);
}
