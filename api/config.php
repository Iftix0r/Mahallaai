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
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

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
} catch (PDOException $e) {
    // Note: In production, you might want to log this instead of dying
    // die("Database error: " . $e->getMessage());
}
