<?php
// Mahalla AI Configuration
define('BOT_TOKEN', '8379929665:AAHkLCyvqYAIUN2McdW5eRFz7JjRLq1Uut8'); // User should replace this
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBAPP_URL', 'https://mahallaai.bigsaver.ru/index.html'); // Web App URL

// Role levels (kattasi ko'proq huquqga ega)
define('ROLE_USER', 'user');
define('ROLE_MANAGER', 'manager');
define('ROLE_ADMIN', 'admin');
define('ROLE_SYSTEM', 'system');

// Role hierarchy (raqam katta = ko'proq huquq)
define('ROLE_LEVELS', [
    'user' => 1,
    'manager' => 2,
    'admin' => 3,
    'system' => 4
]);

// Check if role has at least required level
function hasRole($currentRole, $requiredRole) {
    $levels = ROLE_LEVELS;
    return ($levels[$currentRole] ?? 0) >= ($levels[$requiredRole] ?? 999);
}

// Role labels for display
function getRoleLabel($role) {
    $labels = [
        'user' => '👤 Foydalanuvchi',
        'manager' => '👔 Manager',
        'admin' => '🛡️ Admin',
        'system' => '⚙️ Tizim'
    ];
    return $labels[$role] ?? '👤 Foydalanuvchi';
}

function getRoleBadgeClass($role) {
    $classes = [
        'user' => 'badge-info',
        'manager' => 'badge-warning',
        'admin' => 'badge-success',
        'system' => 'badge-system'
    ];
    return $classes[$role] ?? 'badge-info';
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mahalla_db');
define('DB_USER', 'mahalla_db');
define('DB_PASS', 'Iftixor2006');

// Database Connection
try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create tables (MySQL syntax)
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        telegram_id BIGINT UNIQUE NULL,
        fullname VARCHAR(255),
        phone VARCHAR(20) UNIQUE,
        password VARCHAR(255),
        region VARCHAR(100),
        mahalla VARCHAR(100),
        balance DECIMAL(15, 2) DEFAULT 0.00,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Businesses Table
    $db->exec("CREATE TABLE IF NOT EXISTS businesses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        logo VARCHAR(255) DEFAULT '',
        is_open TINYINT(1) DEFAULT 1,
        delivery_price DECIMAL(10, 2) DEFAULT 0.00,
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Products (Menu items) Table
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        business_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(15, 2) NOT NULL,
        image VARCHAR(255) DEFAULT '',
        is_available TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Orders Table
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        business_id INT NOT NULL,
        total_amount DECIMAL(15, 2) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        items JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    try {
        $db->exec("ALTER TABLE users ADD COLUMN balance DECIMAL(15, 2) DEFAULT 0.00 AFTER mahalla");
    } catch (PDOException $e) {}

    try {
        $db->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER phone");
    } catch (PDOException $e) {
        // Ignore if column already exists
    }
    
    try {
        $db->exec("ALTER TABLE users ADD UNIQUE(phone)");
    } catch (PDOException $e) {
        // Ignore if constraint already exists
    }
    
    try {
        $db->exec("ALTER TABLE users MODIFY COLUMN telegram_id BIGINT NULL");
    } catch(PDOException $e) {
        // Ignore if logic fails
    }

    $db->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(255),
        role VARCHAR(20) DEFAULT 'manager',
        fullname VARCHAR(255) DEFAULT '',
        telegram_id BIGINT NULL,
        is_active TINYINT(1) DEFAULT 1,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Add role column to admins if not exists
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN role VARCHAR(20) DEFAULT 'manager' AFTER password");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN fullname VARCHAR(255) DEFAULT '' AFTER role");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN telegram_id BIGINT NULL AFTER fullname");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER telegram_id");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN last_login TIMESTAMP NULL AFTER is_active");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN broadcast_mode TINYINT(1) DEFAULT 0 AFTER last_login");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN broadcast_message TEXT NULL AFTER broadcast_mode");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN broadcast_media_type VARCHAR(20) NULL AFTER broadcast_message");
    } catch (PDOException $e) {}
    try {
        $db->exec("ALTER TABLE admins ADD COLUMN broadcast_media_id VARCHAR(255) NULL AFTER broadcast_media_type");
    } catch (PDOException $e) {}

    $db->exec("CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        content TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS chats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_id BIGINT UNIQUE,
        chat_type VARCHAR(20) DEFAULT 'private',
        chat_title VARCHAR(255),
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS broadcast_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_text TEXT,
        media_type VARCHAR(20),
        media_file VARCHAR(500),
        target VARCHAR(50) DEFAULT 'all',
        total_sent INT DEFAULT 0,
        total_failed INT DEFAULT 0,
        sent_by VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert default system admin if none exists (password: admin123)
    $stmt = $db->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO admins (username, password, role, fullname) VALUES ('admin', '$hash', 'system', 'Tizim Administratori')");
    }

    // Update existing admin without role to system
    try {
        $db->exec("UPDATE admins SET role = 'system' WHERE username = 'admin' AND (role IS NULL OR role = '')");
    } catch (PDOException $e) {}

    // Insert default news if none exists
    $stmt = $db->query("SELECT COUNT(*) FROM news");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO news (title, content, image) VALUES 
            ('\"Obod Mahalla\" yangi bosqichda', 'Mahallamizda yangi suv quvurlari o\'tkazish ishlari boshlandi.', 'https://images.unsplash.com/photo-1541872703-74c5e443d1fe?auto=format&fit=crop&w=400&q=80'),
            ('Haftalik sport musobaqasi', 'Kelasi yakshanba kuni yoshlar o\'rtasida mini-futbol turniri bo\'lib o'tadi.', 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=400&q=80')");
    }

    // Taxi Drivers Table
    $db->exec("CREATE TABLE IF NOT EXISTS taxi_drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_type VARCHAR(50) NOT NULL,
        car_number VARCHAR(20) NOT NULL,
        car_model VARCHAR(100),
        car_color VARCHAR(50),
        is_online TINYINT(1) DEFAULT 0,
        is_busy TINYINT(1) DEFAULT 0,
        current_lat DECIMAL(10, 8) NULL,
        current_lng DECIMAL(11, 8) NULL,
        rating DECIMAL(3, 2) DEFAULT 5.00,
        total_trips INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_driver (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Taxi Orders Table
    $db->exec("CREATE TABLE IF NOT EXISTS taxi_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        driver_id INT NULL,
        car_type VARCHAR(50) NOT NULL,
        from_address TEXT NOT NULL,
        to_address TEXT NOT NULL,
        from_lat DECIMAL(10, 8) NULL,
        from_lng DECIMAL(11, 8) NULL,
        to_lat DECIMAL(10, 8) NULL,
        to_lng DECIMAL(11, 8) NULL,
        price DECIMAL(10, 2) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        accepted_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES taxi_drivers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Add location update timestamp
    try {
        $db->exec("ALTER TABLE taxi_drivers ADD COLUMN location_updated_at TIMESTAMP NULL AFTER current_lng");
    } catch (PDOException $e) {}
} catch (PDOException $e) {
    // Note: In production, you might want to log this instead of dying
    // die("Database error: " . $e->getMessage());
}
