<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ========== GET REQUESTS ==========
if ($method == 'GET') {
    
    // Get all auto salons
    if ($action == 'get_salons') {
        $stmt = $db->query("SELECT s.*, u.fullname as owner_name FROM auto_salons s 
                           JOIN users u ON s.owner_id = u.id 
                           WHERE s.is_active = 1 
                           ORDER BY s.created_at DESC");
        echo json_encode($stmt->fetchAll());
    }
    
    // Get salon details
    if ($action == 'get_salon') {
        $salon_id = $_GET['salon_id'] ?? 0;
        $stmt = $db->prepare("SELECT s.*, u.fullname as owner_name, u.phone as owner_phone 
                             FROM auto_salons s 
                             JOIN users u ON s.owner_id = u.id 
                             WHERE s.id = ?");
        $stmt->execute([$salon_id]);
        echo json_encode($stmt->fetch());
    }
    
    // Get cars by salon
    if ($action == 'get_salon_cars') {
        $salon_id = $_GET['salon_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM cars WHERE salon_id = ? AND is_sold = 0 ORDER BY created_at DESC");
        $stmt->execute([$salon_id]);
        echo json_encode($stmt->fetchAll());
    }
    
    // Get all cars (with filters)
    if ($action == 'get_cars') {
        $listing_type = $_GET['listing_type'] ?? '';
        $brand = $_GET['brand'] ?? '';
        $min_price = $_GET['min_price'] ?? 0;
        $max_price = $_GET['max_price'] ?? 999999999;
        $year_from = $_GET['year_from'] ?? 1900;
        $year_to = $_GET['year_to'] ?? date('Y');
        
        $query = "SELECT c.*, 
                  CASE WHEN c.salon_id IS NOT NULL THEN s.name ELSE 'Shaxsiy' END as seller_name,
                  (SELECT COUNT(*) FROM car_favorites WHERE car_id = c.id) as favorites_count
                  FROM cars c 
                  LEFT JOIN auto_salons s ON c.salon_id = s.id 
                  WHERE c.is_sold = 0";
        
        $params = [];
        
        if ($listing_type) {
            $query .= " AND c.listing_type = ?";
            $params[] = $listing_type;
        }
        
        if ($brand) {
            $query .= " AND c.brand = ?";
            $params[] = $brand;
        }
        
        $query .= " AND c.price BETWEEN ? AND ?";
        $params[] = $min_price;
        $params[] = $max_price;
        
        $query .= " AND c.year BETWEEN ? AND ?";
        $params[] = $year_from;
        $params[] = $year_to;
        
        $query .= " ORDER BY c.created_at DESC LIMIT 50";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
    }
    
    // Get car details
    if ($action == 'get_car') {
        $car_id = $_GET['car_id'] ?? 0;
        
        // Increment views
        $db->prepare("UPDATE cars SET views = views + 1 WHERE id = ?")->execute([$car_id]);
        
        $stmt = $db->prepare("SELECT c.*, 
                             CASE WHEN c.salon_id IS NOT NULL THEN s.name ELSE u.fullname END as seller_name,
                             CASE WHEN c.salon_id IS NOT NULL THEN s.phone ELSE c.phone END as seller_phone,
                             (SELECT COUNT(*) FROM car_favorites WHERE car_id = c.id) as favorites_count
                             FROM cars c 
                             LEFT JOIN auto_salons s ON c.salon_id = s.id 
                             LEFT JOIN users u ON c.seller_id = u.id
                             WHERE c.id = ?");
        $stmt->execute([$car_id]);
        echo json_encode($stmt->fetch());
    }
    
    // Get user's cars
    if ($action == 'get_my_cars') {
        $user_id = $_GET['user_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM cars WHERE seller_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll());
    }
    
    // Get user's salon
    if ($action == 'get_my_salon') {
        $user_id = $_GET['user_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM auto_salons WHERE owner_id = ?");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch() ?: ['error' => 'Salon not found']);
    }
    
    // Get favorites
    if ($action == 'get_favorites') {
        $user_id = $_GET['user_id'] ?? 0;
        $stmt = $db->prepare("SELECT c.*, 
                             CASE WHEN c.salon_id IS NOT NULL THEN s.name ELSE 'Shaxsiy' END as seller_name
                             FROM car_favorites f
                             JOIN cars c ON f.car_id = c.id
                             LEFT JOIN auto_salons s ON c.salon_id = s.id
                             WHERE f.user_id = ? AND c.is_sold = 0
                             ORDER BY f.created_at DESC");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll());
    }
    
    // Get brands
    if ($action == 'get_brands') {
        $stmt = $db->query("SELECT DISTINCT brand FROM cars ORDER BY brand");
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

// ========== POST REQUESTS ==========
if ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Create auto salon
    if ($action == 'create_salon') {
        $owner_id = $data['owner_id'] ?? 0;
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $address = $data['address'] ?? '';
        $phone = $data['phone'] ?? '';
        $working_hours = $data['working_hours'] ?? '';
        
        try {
            $stmt = $db->prepare("INSERT INTO auto_salons (owner_id, name, description, address, phone, working_hours) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$owner_id, $name, $description, $address, $phone, $working_hours]);
            echo json_encode(['status' => 'success', 'salon_id' => $db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    // Add car
    if ($action == 'add_car') {
        $seller_id = $data['seller_id'] ?? 0;
        $salon_id = $data['salon_id'] ?? null;
        $listing_type = $salon_id ? 'salon' : 'private';
        
        try {
            $stmt = $db->prepare("INSERT INTO cars (salon_id, seller_id, listing_type, brand, model, year, price, 
                                 mileage, fuel_type, transmission, color, body_type, engine_volume, 
                                 description, condition_type, location, phone, images) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $salon_id,
                $seller_id,
                $listing_type,
                $data['brand'],
                $data['model'],
                $data['year'],
                $data['price'],
                $data['mileage'] ?? 0,
                $data['fuel_type'] ?? '',
                $data['transmission'] ?? '',
                $data['color'] ?? '',
                $data['body_type'] ?? '',
                $data['engine_volume'] ?? 0,
                $data['description'] ?? '',
                $data['condition_type'] ?? 'used',
                $data['location'] ?? '',
                $data['phone'] ?? '',
                $data['images'] ?? ''
            ]);
            
            // Update salon total_cars
            if ($salon_id) {
                $db->prepare("UPDATE auto_salons SET total_cars = total_cars + 1 WHERE id = ?")->execute([$salon_id]);
            }
            
            echo json_encode(['status' => 'success', 'car_id' => $db->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    // Toggle favorite
    if ($action == 'toggle_favorite') {
        $user_id = $data['user_id'] ?? 0;
        $car_id = $data['car_id'] ?? 0;
        
        try {
            // Check if already favorited
            $stmt = $db->prepare("SELECT id FROM car_favorites WHERE user_id = ? AND car_id = ?");
            $stmt->execute([$user_id, $car_id]);
            
            if ($stmt->fetch()) {
                // Remove from favorites
                $db->prepare("DELETE FROM car_favorites WHERE user_id = ? AND car_id = ?")->execute([$user_id, $car_id]);
                echo json_encode(['status' => 'success', 'action' => 'removed']);
            } else {
                // Add to favorites
                $db->prepare("INSERT INTO car_favorites (user_id, car_id) VALUES (?, ?)")->execute([$user_id, $car_id]);
                echo json_encode(['status' => 'success', 'action' => 'added']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    // Mark as sold
    if ($action == 'mark_sold') {
        $car_id = $data['car_id'] ?? 0;
        $seller_id = $data['seller_id'] ?? 0;
        
        try {
            $stmt = $db->prepare("UPDATE cars SET is_sold = 1 WHERE id = ? AND seller_id = ?");
            $stmt->execute([$car_id, $seller_id]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    // Delete car
    if ($action == 'delete_car') {
        $car_id = $data['car_id'] ?? 0;
        $seller_id = $data['seller_id'] ?? 0;
        
        try {
            $stmt = $db->prepare("DELETE FROM cars WHERE id = ? AND seller_id = ?");
            $stmt->execute([$car_id, $seller_id]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
