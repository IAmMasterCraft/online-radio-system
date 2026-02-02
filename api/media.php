<?php
/**
 * API: Media Management
 * 
 * GET    /api/media.php           — List all media
 * GET    /api/media.php?id=X      — Get single media
 * POST   /api/media.php           — Update media (id, title, artist, etc.)
 * DELETE /api/media.php?id=X      — Delete media
 * PUT    /api/media.php           — Update duration (for JS fallback)
 */

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Auth required for all except GET
if ($method !== 'GET') {
    session_start();
    if (empty($_SESSION['radio_admin'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

$db = getDB();

switch ($method) {
    // ── List / Get Media ──────────────────────────
    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM radio_media WHERE id = ?");
            $stmt->execute([(int) $_GET['id']]);
            $media = $stmt->fetch();
            if (!$media) jsonResponse(['error' => 'Media not found'], 404);
            $media['url'] = UPLOAD_URL . '/' . $media['filename'];
            jsonResponse($media);
        }
        
        // List with optional filters
        $where = ['1=1'];
        $params = [];
        
        if (isset($_GET['is_loop'])) {
            $where[] = 'is_loop = ?';
            $params[] = (int) $_GET['is_loop'];
        }
        if (isset($_GET['media_type'])) {
            $where[] = 'media_type = ?';
            $params[] = $_GET['media_type'];
        }
        if (isset($_GET['active'])) {
            $where[] = 'active = ?';
            $params[] = (int) $_GET['active'];
        }
        
        $sql = "SELECT *, 
                CONCAT('" . UPLOAD_URL . "/', filename) AS url,
                CASE WHEN cover_image IS NOT NULL 
                     THEN CONCAT('" . UPLOAD_URL . "/covers/', cover_image) 
                     ELSE NULL END AS cover_url
                FROM radio_media 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY is_loop ASC, uploaded_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll());
        break;
    
    // ── Update Media ──────────────────────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        if (empty($data['id'])) {
            jsonResponse(['error' => 'Media ID required'], 400);
        }
        
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'artist', 'description', 'is_loop', 'loop_position', 'active', 'duration'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }
        
        $params[] = (int) $data['id'];
        $stmt = $db->prepare("UPDATE radio_media SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        
        // If loop status changed, reorder loop positions
        if (isset($data['is_loop'])) {
            reorderLoopPositions($db);
        }
        
        jsonResponse(['success' => true, 'message' => 'Media updated']);
        break;
    
    // ── Update Duration (PUT) ─────────────────────
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id']) || !isset($data['duration'])) {
            jsonResponse(['error' => 'Media ID and duration required'], 400);
        }
        
        $duration = (float) $data['duration'];
        if ($duration <= 0) {
            jsonResponse(['error' => 'Invalid duration'], 400);
        }
        
        $stmt = $db->prepare("UPDATE radio_media SET duration = ? WHERE id = ? AND duration = 0");
        $stmt->execute([$duration, (int) $data['id']]);
        
        // Also update any schedule end_times that reference this media
        $stmt2 = $db->prepare("
            UPDATE radio_schedule s
            JOIN radio_media m ON s.media_id = m.id
            SET s.end_time = DATE_ADD(s.start_time, INTERVAL ? SECOND)
            WHERE s.media_id = ?
        ");
        $stmt2->execute([$duration, (int) $data['id']]);
        
        jsonResponse(['success' => true, 'duration' => $duration]);
        break;
    
    // ── Delete Media ──────────────────────────────
    case 'DELETE':
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Media ID required'], 400);
        }
        
        $id = (int) $_GET['id'];
        
        // Get file info before deleting
        $stmt = $db->prepare("SELECT filename, cover_image FROM radio_media WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch();
        
        if (!$media) {
            jsonResponse(['error' => 'Media not found'], 404);
        }
        
        // Delete file
        $filePath = UPLOAD_DIR . '/' . $media['filename'];
        if (file_exists($filePath)) unlink($filePath);
        
        // Delete cover
        if ($media['cover_image']) {
            $coverPath = UPLOAD_DIR . '/covers/' . $media['cover_image'];
            if (file_exists($coverPath)) unlink($coverPath);
        }
        
        // Delete from DB (cascades to schedules)
        $stmt = $db->prepare("DELETE FROM radio_media WHERE id = ?");
        $stmt->execute([$id]);
        
        reorderLoopPositions($db);
        
        jsonResponse(['success' => true, 'message' => 'Media deleted']);
        break;
    
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

// ── Reorder loop positions after changes ──────────
function reorderLoopPositions(PDO $db): void {
    $loops = $db->query("SELECT id FROM radio_media WHERE is_loop = 1 ORDER BY loop_position ASC, id ASC")->fetchAll();
    $stmt = $db->prepare("UPDATE radio_media SET loop_position = ? WHERE id = ?");
    foreach ($loops as $i => $row) {
        $stmt->execute([$i, $row['id']]);
    }
}
