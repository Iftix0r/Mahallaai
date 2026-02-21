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
        if (!$data || !isset($data['telegram_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing data or Telegram ID']);
            exit;
        }
        
        $tg_id = (string)$data['telegram_id'];
        $region = $data['region'] ?? '';
        $mahalla = $data['mahalla'] ?? '';
        $fullname = $data['fullname'] ?? 'User';

        try {
            // Using INSERT ... ON DUPLICATE KEY UPDATE to ensure it works even if user wasn't in DB
            $stmt = $db->prepare("INSERT INTO users (telegram_id, fullname, region, mahalla) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE 
                                region = VALUES(region), 
                                mahalla = VALUES(mahalla)");
            
            $stmt->execute([$tg_id, $fullname, $region, $mahalla]);
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Profile updated successfully',
                'affected' => $stmt->rowCount()
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
