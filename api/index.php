<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/taxi_helpers.php';

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

    // ========== TAXI API ENDPOINTS ==========
    
    if ($action == 'register_driver') {
        $user_id = $data['user_id'] ?? 0;
        $car_type = $data['car_type'] ?? '';
        $car_number = $data['car_number'] ?? '';
        $car_model = $data['car_model'] ?? '';
        $car_color = $data['car_color'] ?? '';

        try {
            $stmt = $db->prepare("INSERT INTO taxi_drivers (user_id, car_type, car_number, car_model, car_color) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $car_type, $car_number, $car_model, $car_color]);
            echo json_encode(['status' => 'success', 'driver_id' => $db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'update_driver_location') {
        $driver_id = $data['driver_id'] ?? 0;
        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;
        $is_online = $data['is_online'] ?? 1;

        try {
            $stmt = $db->prepare("UPDATE taxi_drivers SET current_lat = ?, current_lng = ?, 
                                  is_online = ?, location_updated_at = NOW() WHERE id = ?");
            $stmt->execute([$lat, $lng, $is_online, $driver_id]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'order_taxi') {
        $customer_id = $data['customer_id'] ?? 0;
        $car_type = $data['car_type'] ?? 'Ekonom';
        $from_address = $data['from_address'] ?? '';
        $to_address = $data['to_address'] ?? '';
        $from_lat = $data['from_lat'] ?? null;
        $from_lng = $data['from_lng'] ?? null;
        $to_lat = $data['to_lat'] ?? null;
        $to_lng = $data['to_lng'] ?? null;
        $price = (float)($data['price'] ?? 0);

        try {
            $db->beginTransaction();

            // Check balance
            $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$customer_id]);
            $balance = (float)$stmt->fetchColumn();

            if ($balance < $price) {
                echo json_encode(['status' => 'error', 'message' => 'Balansda mablag\' yetarli emas!']);
                $db->rollBack();
                exit;
            }

            // Create order
            $stmt = $db->prepare("INSERT INTO taxi_orders (customer_id, car_type, from_address, to_address, 
                                  from_lat, from_lng, to_lat, to_lng, price, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$customer_id, $car_type, $from_address, $to_address, 
                           $from_lat, $from_lng, $to_lat, $to_lng, $price]);
            
            $order_id = $db->lastInsertId();

            // Find nearest available driver
            $nearest_driver = null;
            if ($from_lat && $from_lng) {
                $stmt = $db->prepare("SELECT id, current_lat, current_lng, 
                                     (6371 * acos(cos(radians(?)) * cos(radians(current_lat)) * 
                                     cos(radians(current_lng) - radians(?)) + sin(radians(?)) * 
                                     sin(radians(current_lat)))) AS distance 
                                     FROM taxi_drivers 
                                     WHERE car_type = ? AND is_online = 1 AND is_busy = 0 
                                     AND current_lat IS NOT NULL AND current_lng IS NOT NULL
                                     ORDER BY distance ASC LIMIT 1");
                $stmt->execute([$from_lat, $from_lng, $from_lat, $car_type]);
                $nearest_driver = $stmt->fetch();
            }

            if ($nearest_driver) {
                // Assign driver
                $stmt = $db->prepare("UPDATE taxi_orders SET driver_id = ?, status = 'assigned' WHERE id = ?");
                $stmt->execute([$nearest_driver['id'], $order_id]);

                $stmt = $db->prepare("UPDATE taxi_drivers SET is_busy = 1 WHERE id = ?");
                $stmt->execute([$nearest_driver['id']]);

                // Deduct balance
                $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$price, $customer_id]);

                // Get driver telegram_id and notify
                $stmt = $db->prepare("SELECT u.telegram_id, u.fullname FROM users u 
                                     JOIN taxi_drivers d ON d.user_id = u.id WHERE d.id = ?");
                $stmt->execute([$nearest_driver['id']]);
                $driver_user = $stmt->fetch();
                
                if ($driver_user && $driver_user['telegram_id']) {
                    notifyDriver($driver_user['telegram_id'], $order_id, $from_address, $to_address, $price);
                }

                $db->commit();
                echo json_encode([
                    'status' => 'success', 
                    'order_id' => $order_id,
                    'driver_assigned' => true,
                    'driver_distance' => round($nearest_driver['distance'], 2),
                    'new_balance' => $balance - $price
                ]);
            } else {
                // No driver available, keep order pending
                $db->commit();
                echo json_encode([
                    'status' => 'success', 
                    'order_id' => $order_id,
                    'driver_assigned' => false,
                    'message' => 'Buyurtma qabul qilindi. Haydovchi topilmoqda...'
                ]);
            }
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'accept_order') {
        $driver_id = $data['driver_id'] ?? 0;
        $order_id = $data['order_id'] ?? 0;

        try {
            $db->beginTransaction();

            // Check if order is still pending
            $stmt = $db->prepare("SELECT status, customer_id, from_address, to_address FROM taxi_orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order || $order['status'] != 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Buyurtma allaqachon qabul qilingan']);
                $db->rollBack();
                exit;
            }

            // Assign driver
            $stmt = $db->prepare("UPDATE taxi_orders SET driver_id = ?, status = 'accepted', 
                                  accepted_at = NOW() WHERE id = ?");
            $stmt->execute([$driver_id, $order_id]);

            $stmt = $db->prepare("UPDATE taxi_drivers SET is_busy = 1 WHERE id = ?");
            $stmt->execute([$driver_id]);

            // Get driver info and notify customer
            $stmt = $db->prepare("SELECT d.car_model, d.car_number, d.car_color, u.fullname, u.telegram_id 
                                 FROM taxi_drivers d 
                                 JOIN users u ON d.user_id = u.id 
                                 WHERE d.id = ?");
            $stmt->execute([$driver_id]);
            $driver_info = $stmt->fetch();

            // Get customer telegram_id
            $stmt = $db->prepare("SELECT telegram_id FROM users WHERE id = ?");
            $stmt->execute([$order['customer_id']]);
            $customer = $stmt->fetch();

            if ($customer && $customer['telegram_id'] && $driver_info) {
                notifyCustomer($customer['telegram_id'], $driver_info['fullname'], 
                              $driver_info['car_model'], $driver_info['car_number']);
            }

            $db->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'complete_order') {
        $order_id = $data['order_id'] ?? 0;
        $driver_id = $data['driver_id'] ?? 0;

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE taxi_orders SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$order_id]);

            $stmt = $db->prepare("UPDATE taxi_drivers SET is_busy = 0, total_trips = total_trips + 1 WHERE id = ?");
            $stmt->execute([$driver_id]);

            $db->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    if ($action == 'cancel_order') {
        $order_id = $data['order_id'] ?? 0;

        try {
            $db->beginTransaction();

            // Get order details
            $stmt = $db->prepare("SELECT customer_id, driver_id, price FROM taxi_orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if ($order) {
                // Refund customer
                $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$order['price'], $order['customer_id']]);

                // Free driver
                if ($order['driver_id']) {
                    $stmt = $db->prepare("UPDATE taxi_drivers SET is_busy = 0 WHERE id = ?");
                    $stmt->execute([$order['driver_id']]);
                }

                // Update order status
                $stmt = $db->prepare("UPDATE taxi_orders SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$order_id]);
            }

            $db->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}

if ($method == 'GET') {
    // Get driver info
    if ($action == 'get_driver') {
        $user_id = $_GET['user_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM taxi_drivers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch() ?: ['error' => 'Driver not found']);
    }

    // Get pending orders for driver
    if ($action == 'get_pending_orders') {
        $car_type = $_GET['car_type'] ?? '';
        $stmt = $db->prepare("SELECT o.*, u.fullname as customer_name, u.phone as customer_phone 
                             FROM taxi_orders o 
                             JOIN users u ON o.customer_id = u.id 
                             WHERE o.status = 'pending' AND o.car_type = ? 
                             ORDER BY o.created_at ASC");
        $stmt->execute([$car_type]);
        echo json_encode($stmt->fetchAll());
    }

    // Get driver's active orders
    if ($action == 'get_driver_orders') {
        $driver_id = $_GET['driver_id'] ?? 0;
        $stmt = $db->prepare("SELECT o.*, u.fullname as customer_name, u.phone as customer_phone 
                             FROM taxi_orders o 
                             JOIN users u ON o.customer_id = u.id 
                             WHERE o.driver_id = ? AND o.status IN ('accepted', 'assigned') 
                             ORDER BY o.created_at DESC");
        $stmt->execute([$driver_id]);
        echo json_encode($stmt->fetchAll());
    }

    // Get customer's orders
    if ($action == 'get_customer_orders') {
        $customer_id = $_GET['customer_id'] ?? 0;
        $stmt = $db->prepare("SELECT o.*, d.car_number, d.car_model, d.car_color, u.fullname as driver_name 
                             FROM taxi_orders o 
                             LEFT JOIN taxi_drivers d ON o.driver_id = d.id 
                             LEFT JOIN users u ON d.user_id = u.id 
                             WHERE o.customer_id = ? 
                             ORDER BY o.created_at DESC LIMIT 20");
        $stmt->execute([$customer_id]);
        echo json_encode($stmt->fetchAll());
    }

    // Get order status
    if ($action == 'get_order_status') {
        $order_id = $_GET['order_id'] ?? 0;
        $stmt = $db->prepare("SELECT o.*, d.car_number, d.car_model, d.car_color, d.current_lat, d.current_lng,
                             u.fullname as driver_name, u.phone as driver_phone 
                             FROM taxi_orders o 
                             LEFT JOIN taxi_drivers d ON o.driver_id = d.id 
                             LEFT JOIN users u ON d.user_id = u.id 
                             WHERE o.id = ?");
        $stmt->execute([$order_id]);
        echo json_encode($stmt->fetch() ?: ['error' => 'Order not found']);
    }
}
