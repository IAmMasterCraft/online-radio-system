<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

header('Content-Type: application/json');

$db = getDB();

try {
    // 1. Get Total Listens
    $totalListensStmt = $db->query("SELECT COUNT(*) FROM radio_listen_history");
    $totalListens = $totalListensStmt->fetchColumn();

    // 2. Get Unique Listeners
    $uniqueListenersStmt = $db->query("SELECT COUNT(DISTINCT ip_address) FROM radio_listen_history");
    $uniqueListeners = $uniqueListenersStmt->fetchColumn();

    // 3. Get Most Popular Tracks
    $popularTracksStmt = $db->query("
        SELECT m.title, m.artist, COUNT(h.id) as listen_count
        FROM radio_listen_history h
        JOIN radio_media m ON h.media_id = m.id
        WHERE h.media_id IS NOT NULL
        GROUP BY h.media_id
        ORDER BY listen_count DESC
        LIMIT 10
    ");
    $popularTracks = $popularTracksStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get Peak Listening Times
    $peakTimesStmt = $db->query("
        SELECT HOUR(listen_time) as hour, COUNT(*) as listen_count
        FROM radio_listen_history
        GROUP BY hour
        ORDER BY listen_count DESC
        LIMIT 10
    ");
    $peakTimes = $peakTimesStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'total_listens' => (int) $totalListens,
        'unique_listeners' => (int) $uniqueListeners,
        'popular_tracks' => $popularTracks,
        'peak_times' => $peakTimes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
