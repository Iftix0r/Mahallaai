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

    if ($action == 'get_news') {
        $stmt = $db->query("SELECT * FROM news ORDER BY created_at DESC");
        $news = $stmt->fetchAll();
        echo json_encode($news);
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

    if ($action == 'web_register') {
        $stmt = $db->prepare("INSERT INTO users (phone, password, fullname) VALUES (?, ?, ?)");
        try {
            // we should set a dummy telegram_id or NULL. Since we modified config, telegram_id is NULLable but UNIQUE. We can leave it NULL.
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->execute([
                $data['phone'],
                $hash,
                "Foydalanuvchi"
            ]);
            
            $user_id = $db->lastInsertId();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            echo json_encode(['status' => 'success', 'user' => $user]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Bu raqam allaqachon ro\'yxatdan o\'tgan!']);
        }
    }

    if ($action == 'web_login') {
        $phone = $data['phone'] ?? '';
        $password = $data['password'] ?? '';
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(['status' => 'success', 'user' => $user]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Telefon raqam yoki parol noto\'g\'ri!']);
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

    if ($action == 'recharge_balance') {
        $user_id = $data['user_id'] ?? 0;
        $amount = (float)($data['amount'] ?? 0);

        if ($user_id && $amount > 0) {
            try {
                $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$amount, $user_id]);
                
                $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $new_balance = $stmt->fetchColumn();
                
                echo json_encode(['status' => 'success', 'new_balance' => $new_balance]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        }
    }
}
