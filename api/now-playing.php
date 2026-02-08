<?php
/**
 * API: Now Playing
 * 
 * Returns the current media to play, the offset to seek to,
 * and info about what's coming up next.
 * 
 * All listeners calling this endpoint at the same moment will
 * receive the same media + offset, keeping everyone in sync.
 * 
 * GET /api/now-playing.php
 * 
 * Response:
 * {
 *   "status": "scheduled" | "loop" | "offline",
 *   "server_time": 1700000000,
 *   "media": { id, title, artist, filepath, media_type, duration, cover_image },
 *   "offset": 125.4,          // seconds into the media to start
 *   "remaining": 74.6,        // seconds until this media ends
 *   "schedule_title": "...",   // for scheduled items
 *   "next": { ... },           // what's playing next (if known)
 *   "next_check_in": 74.6     // seconds until player should re-fetch
 * }
 */

require_once __DIR__ . '/../config.php';

header('Cache-Control: no-cache, no-store, must-revalidate');

$db = getDB();
$now = time();
$nowDt = date('Y-m-d H:i:s', $now);

// ── 1. Check for an active live stream ─────────
$stmt = $db->query("SELECT id, platform, account_name, stream_url, title FROM radio_live_streams WHERE is_active = 1 AND is_live = 1 LIMIT 1");
$liveStream = $stmt->fetch(PDO::FETCH_ASSOC);

if ($liveStream) {
    jsonResponse([
        'status'        => 'live',
        'server_time'   => $now,
        'media'         => [
            'id'           => (int) $liveStream['id'],
            'title'        => $liveStream['title'] ?: $liveStream['account_name'] . ' (Live)',
            'artist'       => $liveStream['platform'],
            'url'          => $liveStream['stream_url'],
            'media_type'   => 'video', // Assuming live streams are usually video, could be dynamic
            'duration'     => null,    // Live stream has no fixed duration
            'cover_image'  => null,    // No cover image for live stream
        ],
        'offset'        => 0,
        'remaining'     => null, // Live stream has no fixed remaining time
        'next'          => null, // No "next" item for a continuous live stream
        'next_check_in' => 5,    // Check frequently if live stream is still active
    ]);
}

// ── 2. Check for an active scheduled item ─────────

$stmt = $db->prepare("
    SELECT s.id AS schedule_id, s.title AS schedule_title, s.description AS schedule_desc,
           s.start_time, s.end_time,
           m.id, m.title, m.artist, m.description, m.filepath, m.filename,
           m.media_type, m.mime_type, m.duration, m.cover_image
    FROM radio_schedule s
    JOIN radio_media m ON s.media_id = m.id
    WHERE s.active = 1 
      AND m.active = 1
      AND s.start_time <= ?
      AND s.end_time > ?
    ORDER BY s.start_time DESC
    LIMIT 1
");
$stmt->execute([$nowDt, $nowDt]);
$scheduled = $stmt->fetch();

if ($scheduled) {
    $startTs = strtotime($scheduled['start_time']);
    $offset = $now - $startTs;
    $remaining = $scheduled['duration'] - $offset;
    
    // Sanity check
    if ($offset < 0) $offset = 0;
    if ($offset > $scheduled['duration']) {
        // Shouldn't happen but handle gracefully — fall through to loop
        $scheduled = null;
    }
}

if ($scheduled) {
    // Find what's next after this scheduled item
    $next = getNextScheduled($db, $scheduled['end_time']);
    
    jsonResponse([
        'status'         => 'scheduled',
        'server_time'    => $now,
        'media'          => formatMedia($scheduled),
        'schedule_title' => $scheduled['schedule_title'] ?: $scheduled['title'],
        'schedule_desc'  => $scheduled['schedule_desc'] ?: '',
        'offset'         => round($offset, 2),
        'remaining'      => round(max($remaining, 0), 2),
        'next'           => $next,
        'next_check_in'  => round(max($remaining, 1), 2),
    ]);
}

// ── 2. No active schedule — use loop media ────────

$loopMedia = $db->query("
    SELECT id, title, artist, description, filepath, filename,
           media_type, mime_type, duration, cover_image
    FROM radio_media
    WHERE is_loop = 1 AND active = 1 AND duration > 0
    ORDER BY loop_position ASC, id ASC
")->fetchAll();

if (empty($loopMedia)) {
    // Find next upcoming schedule
    $next = getNextScheduled($db, $nowDt);
    
    jsonResponse([
        'status'        => 'offline',
        'server_time'   => $now,
        'message'       => 'No media currently available',
        'media'         => null,
        'offset'        => 0,
        'remaining'     => 0,
        'next'          => $next,
        'next_check_in' => $next ? max(strtotime($next['start_time']) - $now, 5) : 60,
    ]);
}

// Calculate position in the loop cycle
// Using a fixed epoch ensures all listeners are perfectly synced
$epoch = strtotime(getSetting('loop_epoch', '2024-01-01 00:00:00'));
$totalLoopDuration = array_sum(array_column($loopMedia, 'duration'));
$elapsed = $now - $epoch;

// Handle edge case: elapsed could be negative if epoch is in the future
if ($elapsed < 0) $elapsed = 0;

$posInCycle = fmod($elapsed, $totalLoopDuration);

// Walk through loop tracks to find current one
$accumulated = 0;
$currentTrack = null;
$trackOffset = 0;
$trackRemaining = 0;
$nextTrackIndex = 0;

foreach ($loopMedia as $i => $track) {
    if ($accumulated + $track['duration'] > $posInCycle) {
        $currentTrack = $track;
        $trackOffset = $posInCycle - $accumulated;
        $trackRemaining = $track['duration'] - $trackOffset;
        $nextTrackIndex = ($i + 1) % count($loopMedia);
        break;
    }
    $accumulated += $track['duration'];
}

// Fallback (shouldn't happen but just in case of floating point issues)
if (!$currentTrack) {
    $currentTrack = $loopMedia[0];
    $trackOffset = 0;
    $trackRemaining = $currentTrack['duration'];
    $nextTrackIndex = count($loopMedia) > 1 ? 1 : 0;
}

// Check if a schedule starts before this loop track ends
$nextSchedule = getNextScheduled($db, $nowDt);
$nextCheckIn = $trackRemaining;

if ($nextSchedule) {
    $scheduleStartsIn = strtotime($nextSchedule['start_time']) - $now;
    if ($scheduleStartsIn > 0 && $scheduleStartsIn < $trackRemaining) {
        // A schedule starts during this track — player should check sooner
        $nextCheckIn = $scheduleStartsIn;
    }
}

// Build "next" info: either next schedule or next loop track
$next = $nextSchedule;
if (!$next || (strtotime($nextSchedule['start_time']) - $now) > $trackRemaining) {
    // Next loop track is more immediate
    $nextLoop = $loopMedia[$nextTrackIndex];
    $next = [
        'type'       => 'loop',
        'title'      => $nextLoop['title'],
        'artist'     => $nextLoop['artist'],
        'starts_in'  => round($trackRemaining, 2),
    ];
}

jsonResponse([
    'status'        => 'loop',
    'server_time'   => $now,
    'media'         => formatMedia($currentTrack),
    'offset'        => round($trackOffset, 2),
    'remaining'     => round(max($trackRemaining, 0), 2),
    'next'          => $next,
    'next_check_in' => round(max($nextCheckIn, 1), 2),
]);

// ── Helper Functions ──────────────────────────────

function formatMedia(array $row): array {
    return [
        'id'          => (int) $row['id'],
        'title'       => $row['title'],
        'artist'      => $row['artist'] ?? '',
        'description' => $row['description'] ?? '',
        'url'         => UPLOAD_URL . '/' . $row['filename'],
        'media_type'  => $row['media_type'],
        'mime_type'   => $row['mime_type'] ?? '',
        'duration'    => (float) $row['duration'],
        'cover_image' => $row['cover_image'] 
            ? UPLOAD_URL . '/covers/' . $row['cover_image'] 
            : null,
    ];
}

function getNextScheduled(PDO $db, string $afterTime): ?array {
    $stmt = $db->prepare("
        SELECT s.start_time, s.title AS schedule_title,
               m.title, m.artist, m.duration, m.cover_image
        FROM radio_schedule s
        JOIN radio_media m ON s.media_id = m.id
        WHERE s.active = 1 AND m.active = 1
          AND s.start_time > ?
        ORDER BY s.start_time ASC
        LIMIT 1
    ");
    $stmt->execute([$afterTime]);
    $row = $stmt->fetch();
    
    if (!$row) return null;
    
    return [
        'type'       => 'scheduled',
        'title'      => $row['schedule_title'] ?: $row['title'],
        'artist'     => $row['artist'],
        'start_time' => $row['start_time'],
        'duration'   => (float) $row['duration'],
        'cover_image' => $row['cover_image']
            ? UPLOAD_URL . '/covers/' . $row['cover_image']
            : null,
    ];
}
