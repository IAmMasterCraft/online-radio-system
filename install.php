<?php
/**
 * TRADITIONAL RADIO - Installation Script
 * 
 * Run this once to set up the database tables.
 * DELETE THIS FILE after installation for security.
 */

require_once __DIR__ . '/config.php';

$messages = [];

try {
    $db = getDB();
    
    // Read and execute schema
    $sql = file_get_contents(__DIR__ . '/database.sql');
    $db->exec($sql);
    $messages[] = ['success', 'Database tables created successfully.'];
    
    // Create uploads directory
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
        $messages[] = ['success', 'Uploads directory created.'];
    } else {
        $messages[] = ['info', 'Uploads directory already exists.'];
    }
    
    // Create covers subdirectory
    $coversDir = UPLOAD_DIR . '/covers';
    if (!is_dir($coversDir)) {
        mkdir($coversDir, 0755, true);
        $messages[] = ['success', 'Covers directory created.'];
    }
    
    // Check ffprobe availability
    $ffprobeAvailable = false;
    if (FFPROBE_PATH && file_exists(FFPROBE_PATH)) {
        $ffprobeAvailable = true;
    } else {
        $output = shell_exec('which ffprobe 2>/dev/null');
        $ffprobeAvailable = !empty(trim($output ?? ''));
    }
    
    if ($ffprobeAvailable) {
        $messages[] = ['success', 'ffprobe detected — media duration will be extracted automatically.'];
    } else {
        $messages[] = ['warning', 'ffprobe not found. Media duration will be detected via browser JavaScript. Install ffmpeg/ffprobe for server-side detection.'];
    }
    
    // Write .htaccess for uploads security
    $htaccess = UPLOAD_DIR . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Options -Indexes\n");
        $messages[] = ['success', '.htaccess created to disable directory listing in uploads.'];
    }
    
    $messages[] = ['success', '✅ Installation complete! You can now access the admin panel.'];
    $messages[] = ['warning', '⚠️ DELETE this install.php file for security reasons.'];
    
} catch (Exception $e) {
    $messages[] = ['error', 'Installation failed: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Online Radio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f0f0f; color: #e0e0e0; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .container { max-width: 600px; width: 100%; }
        h1 { font-size: 1.8rem; margin-bottom: 1.5rem; color: #f5a623; }
        .msg { padding: 0.8rem 1rem; margin-bottom: 0.5rem; border-radius: 8px; font-size: 0.95rem; }
        .msg.success { background: #1a3a1a; border: 1px solid #2d5a2d; color: #7bc67b; }
        .msg.info { background: #1a2a3a; border: 1px solid #2d4a6d; color: #7baed6; }
        .msg.warning { background: #3a3a1a; border: 1px solid #5a5a2d; color: #d6c67b; }
        .msg.error { background: #3a1a1a; border: 1px solid #5a2d2d; color: #d67b7b; }
        .links { margin-top: 2rem; display: flex; gap: 1rem; }
        .links a { color: #f5a623; text-decoration: none; padding: 0.6rem 1.2rem; border: 1px solid #f5a623; border-radius: 6px; transition: all 0.2s; }
        .links a:hover { background: #f5a623; color: #0f0f0f; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📻 Online Radio — Installation</h1>
        <?php foreach ($messages as [$type, $text]): ?>
            <div class="msg <?= $type ?>"><?= htmlspecialchars($text) ?></div>
        <?php endforeach; ?>
        <div class="links">
            <a href="admin/index.php">Admin Panel →</a>
            <a href="player.php">Radio Player →</a>
        </div>
    </div>
</body>
</html>
