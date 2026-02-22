<?php
session_start();
if (!isset($_SESSION['admin_auth'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../api/config.php';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahalla AI - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --sidebar-width: 260px;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg); margin: 0; display: flex; }
        
        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: #1e293b; height: 100vh; position: fixed; color: white; padding: 20px; }
        .sidebar h2 { font-size: 1.5rem; margin-bottom: 40px; text-align: center; color: #3b82f6; }
        .nav-links { list-style: none; padding: 0; }
        .nav-links li { margin-bottom: 15px; }
        .nav-links a { color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 12px 15px; border-radius: 12px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(59, 130, 246, 0.1); color: white; }
        
        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; min-height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .user-info { display: flex; align-items: center; gap: 10px; font-weight: 600; }
        
        /* Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .stat-card h3 { font-size: 0.9rem; color: #64748b; margin-bottom: 10px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: #1e293b; }
        
        /* Tables */
        .table-container { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 0.85rem; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        
        .btn-logout { background: #fee2e2; color: #ef4444; text-decoration: none; padding: 8px 15px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Mahalla AI</h2>
        <ul class="nav-links">
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Foydalanuvchilar</a></li>
            <li><a href="news.php">📰 Yangiliklar</a></li>
            <li><a href="settings.php">⚙️ Sozlamalar</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h1 id="page-title">Boshqaruv Paneli</h1>
            <div class="user-info">
                <span><?php echo $_SESSION['admin_user']; ?></span>
                <a href="logout.php" class="btn-logout">Chiqish</a>
            </div>
        </div>
