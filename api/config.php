<?php
// Mahalla AI Configuration
define('BOT_TOKEN', '8379929665:AAHkLCyvqYAIUN2McdW5eRFz7JjRLq1Uut8'); // User should replace this
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('WEBAPP_URL', 'https://yourdomain.com/index.html'); // Web App URL

// Database (Using SQLite for portable dev)
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../mahalla.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        telegram_id INTEGER UNIQUE,
        fullname TEXT,
        phone TEXT,
        region TEXT,
        mahalla TEXT,
        registered_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
