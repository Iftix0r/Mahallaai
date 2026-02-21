<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method == 'GET') {
    if ($action == 'get_user') {
        $telegram_id = $_GET['telegram_id'] ?? 0;
        if (!$telegram_id) {
            echo json_encode(['error' => 'Telegram ID required']);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([$telegram_id]);
        $user = $stmt->fetch();
        echo json_encode($user ?: ['error' => 'User not found']);
    }
}

if ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action == 'register') {
        $stmt = $db->prepare("INSERT INTO users (telegram_id, fullname, phone) VALUES (?, ?, ?)");
        try {
            $stmt->execute([
                $data['telegram_id'],
                $data['fullname'],
                $data['phone']
            ]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'update_profile') {
        $stmt = $db->prepare("UPDATE users SET region = ?, mahalla = ? WHERE telegram_id = ?");
        try {
            $stmt->execute([
                $data['region'] ?? '',
                $data['mahalla'] ?? '',
                $data['telegram_id']
            ]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
