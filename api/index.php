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

    if ($action == 'get_businesses') {
        $cat = $_GET['category'] ?? '';
        if ($cat) {
            $stmt = $db->prepare("SELECT * FROM businesses WHERE category = ? AND is_open = 1");
            $stmt->execute([$cat]);
        } else {
            $stmt = $db->query("SELECT * FROM businesses WHERE is_open = 1");
        }
        echo json_encode($stmt->fetchAll());
    }

    if ($action == 'get_products') {
        $biz_id = $_GET['business_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM products WHERE business_id = ? AND is_available = 1");
        $stmt->execute([$biz_id]);
        echo json_encode($stmt->fetchAll());
    }

    if ($action == 'get_my_business') {
        $owner_id = $_GET['owner_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM businesses WHERE owner_id = ?");
        $stmt->execute([$owner_id]);
        echo json_encode($stmt->fetch());
    }

    if ($action == 'get_business_orders') {
        $biz_id = $_GET['business_id'] ?? 0;
        $stmt = $db->prepare("SELECT o.*, u.fullname as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.business_id = ? ORDER BY o.created_at DESC");
        $stmt->execute([$biz_id]);
        echo json_encode($stmt->fetchAll());
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

    if ($action == 'process_payment') {
        $user_id = $data['user_id'] ?? 0;
        $amount = (float)($data['amount'] ?? 0);

        if ($user_id && $amount > 0) {
            try {
                $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_balance = (float)$stmt->fetchColumn();

                if ($current_balance >= $amount) {
                    $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                    $stmt->execute([$amount, $user_id]);
                    
                    $new_balance = $current_balance - $amount;
                    echo json_encode(['status' => 'success', 'new_balance' => $new_balance]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Balansda mablag\' yetarli emas!']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        }
    }

    if ($action == 'create_business') {
        $owner_id = $data['owner_id'] ?? 0;
        $name = $data['name'] ?? '';
        $category = $data['category'] ?? '';

        if (!$owner_id || !$name || !$category) {
            echo json_encode(['status' => 'error', 'message' => 'To\'liq ma\'lumot kiriting']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO businesses (owner_id, name, category) VALUES (?, ?, ?)");
            $stmt->execute([$owner_id, $name, $category]);
            echo json_encode(['status' => 'success', 'business_id' => $db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'add_product') {
        $biz_id = $data['business_id'] ?? 0;
        $stmt = $db->prepare("INSERT INTO products (business_id, name, price, description, image) VALUES (?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$biz_id, $data['name'], $data['price'], $data['description'] ?? '', $data['image'] ?? '']);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'place_order') {
        $cust_id = $data['customer_id'] ?? 0;
        $biz_id = $data['business_id'] ?? 0;
        $amount = (float)$data['total_amount'];
        $items = json_encode($data['items']);

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$cust_id]);
            $bal = (float)$stmt->fetchColumn();

            if ($bal < $amount) {
                echo json_encode(['status' => 'error', 'message' => 'Balans yetarli emas']);
                $db->rollBack();
                exit;
            }

            $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $cust_id]);
            $db->prepare("INSERT INTO orders (customer_id, business_id, total_amount, items) VALUES (?, ?, ?, ?)")->execute([$cust_id, $biz_id, $amount, $items]);

            $db->commit();
            echo json_encode(['status' => 'success', 'new_balance' => $bal - $amount]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
