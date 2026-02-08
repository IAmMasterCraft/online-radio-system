<?php
require_once __DIR__ . '/../config.php';

// Only admins can access GET/PUT/DELETE
// POST for creating a request can be public
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    requireAdmin(); // Authenticate for management actions
}

header('Content-Type: application/json');

$db = getDB();

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        // If 'id' is present, it's an update (admin action)
        if (isset($data['id'])) {
            // Admin is updating status
            $stmt = $db->prepare("UPDATE radio_requests SET status = ? WHERE id = ?");
            $stmt->execute([$data['status'], $data['id']]);
            echo json_encode(['success' => true, 'message' => 'Request status updated.']);
        } else {
            // New request from public player
            if (!isset($data['media_id'])) {
                throw new Exception('Media ID is required for a new request.');
            }
            $stmt = $db->prepare(
                "INSERT INTO radio_requests (media_id, requester_name, message) VALUES (?, ?, ?)"
            );
            $stmt->execute([$data['media_id'], $data['requester_name'] ?? null, $data['message'] ?? null]);
            echo json_encode(['success' => true, 'message' => 'Request submitted successfully.']);
        }
    } elseif ($method === 'GET') {
        $statusFilter = $_GET['status'] ?? null;
        $query = "
            SELECT r.id, r.requester_name, r.message, r.status, r.requested_at,
                   m.title as media_title, m.artist as media_artist
            FROM radio_requests r
            JOIN radio_media m ON r.media_id = m.id
        ";
        $params = [];

        if ($statusFilter) {
            $query .= " WHERE r.status = ?";
            $params[] = $statusFilter;
        }
        $query .= " ORDER BY r.requested_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($requests);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception('ID is required for deletion.');
        
        $stmt = $db->prepare("DELETE FROM radio_requests WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Request deleted.']);
    } else {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}
