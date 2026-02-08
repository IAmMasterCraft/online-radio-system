<?php
require_once __DIR__ . '/../config.php';

// Only admins can access this API
requireAdmin();

header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM radio_live_streams ORDER BY created_at DESC");
        $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($streams);
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['id'])) {
            // Update
            $stmt = $db->prepare("UPDATE radio_live_streams SET is_active = ? WHERE id = ?");
            $stmt->execute([$data['is_active'], $data['id']]);
        } else {
            // Create
            $stmt = $db->prepare("INSERT INTO radio_live_streams (platform, account_name) VALUES (?, ?)");
            $stmt->execute([$data['platform'], $data['account_name']]);
            $data['id'] = $db->lastInsertId();
        }
        echo json_encode(['success' => true, 'data' => $data]);

    } 
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception('ID is required');
        
        $stmt = $db->prepare("DELETE FROM radio_live_streams WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } 
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
