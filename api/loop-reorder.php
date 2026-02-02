<?php
/**
 * API: Reorder Loop Media
 * 
 * POST /api/loop-reorder.php
 * Body: { "order": [5, 3, 1, 8] }  — array of media IDs in desired order
 */

require_once __DIR__ . '/../config.php';

session_start();
if (empty($_SESSION['radio_admin'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['order']) || !is_array($data['order'])) {
    jsonResponse(['error' => 'Order array required'], 400);
}

$db = getDB();
$stmt = $db->prepare("UPDATE radio_media SET loop_position = ? WHERE id = ? AND is_loop = 1");

foreach ($data['order'] as $position => $mediaId) {
    $stmt->execute([(int) $position, (int) $mediaId]);
}

jsonResponse(['success' => true, 'message' => 'Loop order updated']);
