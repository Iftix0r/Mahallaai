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
    
    // Create users table if it doesn't exist (MySQL syntax)
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        telegram_id BIGINT UNIQUE,
        fullname VARCHAR(255),
        phone VARCHAR(20),
        region VARCHAR(100),
        mahalla VARCHAR(100),
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Note: In production, you might want to log this instead of dying
    // die("Database error: " . $e->getMessage());
}
