<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT setting_key, setting_value FROM radio_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode($settings);
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        foreach ($data as $key => $value) {
            setSetting($key, $value);
        }

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
