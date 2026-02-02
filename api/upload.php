<?php
/**
 * API: Upload Media
 * 
 * POST /api/upload.php
 * 
 * Accepts multipart form data:
 * - file: The media file (audio or video)
 * - title: Display title
 * - artist: Artist/presenter name (optional)
 * - description: Description (optional)
 * - is_loop: 0 or 1 — mark as loop/filler media
 * - cover: Cover image file (optional)
 * - duration: Duration in seconds (JS fallback if ffprobe unavailable)
 */

require_once __DIR__ . '/../config.php';

// Auth check
session_start();
if (empty($_SESSION['radio_admin'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Validate file
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp directory',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
    ];
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    jsonResponse(['error' => $errorMessages[$code] ?? 'Upload failed'], 400);
}

$file = $_FILES['file'];

// Check file size
if ($file['size'] > MAX_UPLOAD_SIZE) {
    jsonResponse(['error' => 'File too large. Maximum: ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . ' MB'], 400);
}

// Determine media type from extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$mediaType = null;
$mimeType = $file['type'];

if (in_array($ext, ALLOWED_AUDIO)) {
    $mediaType = 'audio';
} elseif (in_array($ext, ALLOWED_VIDEO)) {
    $mediaType = 'video';
} else {
    jsonResponse(['error' => 'File type not allowed. Supported: ' . implode(', ', array_merge(ALLOWED_AUDIO, ALLOWED_VIDEO))], 400);
}

// Generate unique filename
$uniqueName = uniqid('media_', true) . '.' . $ext;
$destPath = UPLOAD_DIR . '/' . $uniqueName;

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    jsonResponse(['error' => 'Failed to save uploaded file'], 500);
}

// Extract duration
$duration = getMediaDuration($destPath);

// If ffprobe didn't work, try the JS-provided duration
if ($duration === null && !empty($_POST['duration'])) {
    $duration = (float) $_POST['duration'];
}

if (!$duration || $duration <= 0) {
    // We'll save with 0 and let admin update, or JS can detect it
    $duration = 0;
}

// Handle cover image upload
$coverName = null;
if (!empty($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
    $coverExt = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
    if (in_array($coverExt, ALLOWED_IMAGES)) {
        $coverName = uniqid('cover_', true) . '.' . $coverExt;
        $coverDir = UPLOAD_DIR . '/covers';
        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
        move_uploaded_file($_FILES['cover']['tmp_name'], $coverDir . '/' . $coverName);
    }
}

// Get form data
$title = trim($_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
$artist = trim($_POST['artist'] ?? '');
$description = trim($_POST['description'] ?? '');
$isLoop = (int) ($_POST['is_loop'] ?? 0);

// Get next loop position if this is a loop track
$loopPosition = 0;
if ($isLoop) {
    $db = getDB();
    $maxPos = $db->query("SELECT COALESCE(MAX(loop_position), -1) + 1 FROM radio_media WHERE is_loop = 1")->fetchColumn();
    $loopPosition = (int) $maxPos;
}

// Insert into database
$db = getDB();
$stmt = $db->prepare("
    INSERT INTO radio_media (title, artist, description, filename, filepath, media_type, mime_type, duration, file_size, is_loop, loop_position, cover_image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $title,
    $artist,
    $description,
    $uniqueName,
    $destPath,
    $mediaType,
    $mimeType,
    $duration,
    $file['size'],
    $isLoop,
    $loopPosition,
    $coverName,
]);

$mediaId = $db->lastInsertId();

jsonResponse([
    'success' => true,
    'media'   => [
        'id'          => (int) $mediaId,
        'title'       => $title,
        'artist'      => $artist,
        'media_type'  => $mediaType,
        'duration'    => $duration,
        'filename'    => $uniqueName,
        'is_loop'     => $isLoop,
        'needs_duration' => ($duration == 0),
    ],
    'message' => $duration > 0 
        ? 'Media uploaded successfully' 
        : 'Media uploaded — duration could not be detected automatically. It will be detected when played in browser.',
]);
