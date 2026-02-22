<?php
// Mahalla AI Configuration
define('BOT_TOKEN', '8379929665:AAHkLCyvqYAIUN2McdW5eRFz7JjRLq1Uut8'); // User should replace this
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBAPP_URL', 'https://mahallaai.bigsaver.ru/index.html'); // Web App URL

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
        telegram_id BIGINT UNIQUE,
        fullname VARCHAR(255),
        phone VARCHAR(20),
        region VARCHAR(100),
        mahalla VARCHAR(100),
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        content TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert default admin if none exists (password: admin123)
    $stmt = $db->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO admins (username, password) VALUES ('admin', '$hash')");
    }

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
