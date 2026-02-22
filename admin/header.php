<?php
session_start();
if (!isset($_SESSION['admin_auth'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../api/config.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahalla AI - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --primary-bg: rgba(99, 102, 241, 0.08);
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: rgba(99, 102, 241, 0.15);
            --card-bg: #ffffff;
            --bg: #f1f5f9;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-muted: #94a3b8;
            --text-light: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --sidebar-width: 270px;
            --radius: 14px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.04);
            --shadow: 0 4px 20px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.06);
        }

        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* ========== SIDEBAR ========== */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 28px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar-brand .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .sidebar-brand .brand-text h3 {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .sidebar-brand .brand-text span {
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 500;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 14px;
            overflow-y: auto;
        }

        .nav-section-title {
            color: var(--text-muted);
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 0 12px;
            margin-bottom: 10px;
            margin-top: 20px;
        }

        .nav-section-title:first-child { margin-top: 0; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 4px;
            position: relative;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        .nav-link.active {
            background: var(--sidebar-active);
            color: var(--primary-light);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: -14px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 24px;
            background: var(--primary);
            border-radius: 0 4px 4px 0;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .nav-link.active i { opacity: 1; }

        .sidebar-footer {
            padding: 18px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .admin-meta { flex: 1; }
        .admin-meta h4 { color: white; font-size: 0.85rem; font-weight: 600; }
        .admin-meta span { color: var(--text-muted); font-size: 0.72rem; }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
        }

        .top-bar {
            padding: 20px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-bar h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.5px;
        }

        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 9px 18px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
        }

        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }

        .btn-danger {
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .btn-danger:hover { background: #fee2e2; }

        .btn-outline {
            background: transparent;
            color: var(--text-light);
            border: 1px solid var(--border);
        }

        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        .page-content {
            padding: 32px 36px;
        }

        /* ========== STAT CARDS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        .stat-icon.green { background: #ecfdf5; color: #10b981; }
        .stat-icon.purple { background: #f5f3ff; color: #8b5cf6; }
        .stat-icon.orange { background: #fff7ed; color: #f97316; }

        .stat-info h4 {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .stat-info .value {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.5px;
        }

        /* ========== TABLE ========== */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }

        .card-header h3 {
            font-size: 1rem;
            font-weight: 700;
        }

        .card-body { padding: 0; }
        .card-body.padded { padding: 24px; }

        table { width: 100%; border-collapse: collapse; }
        
        thead th {
            text-align: left;
            padding: 14px 24px;
            background: #f8fafc;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        tbody td {
            padding: 14px 24px;
            font-size: 0.88rem;
            border-bottom: 1px solid #f8fafc;
            color: var(--text);
        }

        tbody tr:hover { background: #fafbfc; }
        tbody tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .badge-success { background: #ecfdf5; color: #059669; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-danger { background: #fef2f2; color: #dc2626; }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar-sm {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            color: var(--primary);
        }

        /* ========== FORM ========== */
        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            font-size: 0.88rem;
            outline: none;
            transition: all 0.2s;
            color: var(--text);
        }

        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
        }

        textarea.form-control { resize: vertical; min-height: 100px; }

        .search-input {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
        }

        .search-input i { color: var(--text-muted); font-size: 0.85rem; }

        .search-input input {
            border: none;
            background: none;
            padding: 10px 0;
            width: 200px;
            font-size: 0.85rem;
            outline: none;
        }

        /* ========== GRID LAYOUT ========== */
        .grid-2 { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; }

        /* ========== ALERT ========== */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .page-content { padding: 20px; }
            .top-bar { padding: 16px 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="brand-text">
                <h3>Mahalla AI</h3>
                <span>Admin Panel</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Asosiy</div>
            <a href="index.php" class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="users.php" class="nav-link <?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Foydalanuvchilar
            </a>

            <div class="nav-section-title">Kontent</div>
            <a href="news.php" class="nav-link <?php echo $currentPage == 'news' ? 'active' : ''; ?>">
                <i class="fas fa-newspaper"></i> Yangiliklar
            </a>

            <div class="nav-section-title">Xizmatlar Boshqaruvi</div>
            <a href="food.php" class="nav-link <?php echo $currentPage == 'food' ? 'active' : ''; ?>">
                <i class="fas fa-burger"></i> Fast Food
            </a>
            <a href="taxi.php" class="nav-link <?php echo $currentPage == 'taxi' ? 'active' : ''; ?>">
                <i class="fas fa-taxi"></i> Taxi Xizmati
            </a>
            <a href="market.php" class="nav-link <?php echo $currentPage == 'market' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Marketlar
            </a>
            <a href="ish.php" class="nav-link <?php echo $currentPage == 'ish' ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i> Bo'sh Ish O'rinlari
            </a>
            <a href="biznes.php" class="nav-link <?php echo $currentPage == 'biznes' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i> Biznes va Do'konlar
            </a>

            <div class="nav-section-title">Tizim</div>
            <a href="settings.php" class="nav-link <?php echo $currentPage == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Sozlamalar
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['admin_user'], 0, 1)); ?></div>
                <div class="admin-meta">
                    <h4><?php echo $_SESSION['admin_user']; ?></h4>
                    <span>Administrator</span>
                </div>
                <a href="logout.php" style="color: var(--text-muted); font-size: 0.9rem;" title="Chiqish">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 id="page-title">Dashboard</h1>
            <div class="top-bar-actions">
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Chiqish
                </a>
            </div>
        </div>
        <div class="page-content">
