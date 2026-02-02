<?php
/**
 * API: Schedule Management
 * 
 * GET    /api/schedule.php              — List schedules (upcoming by default)
 * GET    /api/schedule.php?range=week   — Schedules for this week
 * GET    /api/schedule.php?range=all    — All schedules
 * POST   /api/schedule.php              — Create a schedule entry
 * DELETE /api/schedule.php?id=X         — Delete a schedule entry
 */

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

session_start();
if ($method !== 'GET' && empty($_SESSION['radio_admin'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();

switch ($method) {
    // ── List Schedules ────────────────────────────
    case 'GET':
        $range = $_GET['range'] ?? 'upcoming';
        $now = date('Y-m-d H:i:s');
        
        $where = 's.active = 1 AND m.active = 1';
        $params = [];
        
        switch ($range) {
            case 'upcoming':
                $where .= ' AND s.end_time > ?';
                $params[] = $now;
                break;
            case 'week':
                $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $weekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                $where .= ' AND s.start_time >= ? AND s.start_time <= ?';
                $params[] = $weekStart;
                $params[] = $weekEnd;
                break;
            case 'today':
                $dayStart = date('Y-m-d 00:00:00');
                $dayEnd = date('Y-m-d 23:59:59');
                $where .= ' AND s.start_time >= ? AND s.start_time <= ?';
                $params[] = $dayStart;
                $params[] = $dayEnd;
                break;
            case 'past':
                $where .= ' AND s.end_time <= ?';
                $params[] = $now;
                break;
            case 'all':
                // No additional filter
                break;
        }
        
        $stmt = $db->prepare("
            SELECT s.id, s.media_id, s.title AS schedule_title, s.start_time, s.end_time,
                   s.description AS schedule_description, s.created_at,
                   m.title AS media_title, m.artist, m.media_type, m.duration, m.filename,
                   CONCAT('" . UPLOAD_URL . "/', m.filename) AS media_url,
                   CASE WHEN m.cover_image IS NOT NULL 
                        THEN CONCAT('" . UPLOAD_URL . "/covers/', m.cover_image) 
                        ELSE NULL END AS cover_url
            FROM radio_schedule s
            JOIN radio_media m ON s.media_id = m.id
            WHERE $where
            ORDER BY s.start_time ASC
        ");
        $stmt->execute($params);
        jsonResponse($stmt->fetchAll());
        break;
    
    // ── Create Schedule ───────────────────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        // Validate required fields
        if (empty($data['media_id']) || empty($data['start_time'])) {
            jsonResponse(['error' => 'media_id and start_time are required'], 400);
        }
        
        $mediaId = (int) $data['media_id'];
        $startTime = $data['start_time'];
        
        // Validate media exists and get duration
        $stmt = $db->prepare("SELECT id, title, duration FROM radio_media WHERE id = ? AND active = 1");
        $stmt->execute([$mediaId]);
        $media = $stmt->fetch();
        
        if (!$media) {
            jsonResponse(['error' => 'Media not found'], 404);
        }
        
        if ($media['duration'] <= 0) {
            jsonResponse(['error' => 'Media duration is not set. Please play the media in browser first to detect duration.'], 400);
        }
        
        // Calculate end time
        $startTs = strtotime($startTime);
        if (!$startTs) {
            jsonResponse(['error' => 'Invalid start_time format. Use: YYYY-MM-DD HH:MM:SS'], 400);
        }
        $endTime = date('Y-m-d H:i:s', $startTs + (int) ceil($media['duration']));
        
        // Check for overlapping schedules
        $stmt = $db->prepare("
            SELECT s.id, s.start_time, s.end_time, m.title
            FROM radio_schedule s
            JOIN radio_media m ON s.media_id = m.id
            WHERE s.active = 1
              AND s.start_time < ?
              AND s.end_time > ?
        ");
        $stmt->execute([$endTime, $startTime]);
        $conflicts = $stmt->fetchAll();
        
        $hasConflicts = !empty($conflicts);
        
        // Allow scheduling even with conflicts (latest wins in playback),
        // but warn the admin
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        
        $stmt = $db->prepare("
            INSERT INTO radio_schedule (media_id, title, start_time, end_time, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$mediaId, $title ?: null, $startTime, $endTime, $description]);
        
        $scheduleId = $db->lastInsertId();
        
        $response = [
            'success'  => true,
            'schedule' => [
                'id'          => (int) $scheduleId,
                'media_id'    => $mediaId,
                'media_title' => $media['title'],
                'title'       => $title ?: $media['title'],
                'start_time'  => $startTime,
                'end_time'    => $endTime,
                'duration'    => $media['duration'],
            ],
            'message' => 'Schedule created successfully',
        ];
        
        if ($hasConflicts) {
            $response['warnings'] = ['This schedule overlaps with existing entries. The later-starting item takes priority during playback.'];
            $response['conflicts'] = $conflicts;
        }
        
        jsonResponse($response);
        break;
    
    // ── Delete Schedule ───────────────────────────
    case 'DELETE':
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Schedule ID required'], 400);
        }
        
        $stmt = $db->prepare("DELETE FROM radio_schedule WHERE id = ?");
        $stmt->execute([(int) $_GET['id']]);
        
        jsonResponse(['success' => true, 'message' => 'Schedule entry deleted']);
        break;
    
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
