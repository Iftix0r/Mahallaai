<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (isset($_SESSION['admin_auth'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Check if account is active
        if (isset($admin['is_active']) && $admin['is_active'] == 0) {
            $error = "Hisobingiz bloklangan! Tizim administratoriga murojaat qiling.";
        } else {
            $_SESSION['admin_auth'] = true;
            $_SESSION['admin_user'] = $admin['username'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'manager';
            $_SESSION['admin_fullname'] = $admin['fullname'] ?? $admin['username'];
            
            // Update last login
            try {
                $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            } catch (Exception $e) {}
            
            header('Location: index.php');
            exit;
        }
    } else {
        $error = "Xato foydalanuvchi nomi yoki parol!";
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mahalla AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; }
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            top: -100px;
            right: -100px;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            bottom: -100px;
            left: -100px;
        }
        .login-card { 
            background: rgba(255, 255, 255, 0.03); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 50px 40px; 
            border-radius: 24px; 
            border: 1px solid rgba(255, 255, 255, 0.08);
            width: 100%; 
            max-width: 420px; 
            position: relative;
            z-index: 10;
        }
        .brand {
            text-align: center;
            margin-bottom: 40px;
        }
        .brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.4rem;
            margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }
        .brand h2 { color: white; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.5px; }
        .brand p { color: #94a3b8; font-size: 0.88rem; margin-top: 6px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.82rem; color: #94a3b8; }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-wrapper i {
            position: absolute;
            left: 16px;
            color: #64748b;
            font-size: 0.9rem;
        }
        input { 
            width: 100%; 
            padding: 13px 16px 13px 44px; 
            border-radius: 12px; 
            border: 1.5px solid rgba(255, 255, 255, 0.08); 
            background: rgba(255, 255, 255, 0.04); 
            outline: none; 
            transition: 0.2s; 
            color: white;
            font-size: 0.9rem;
        }
        input::placeholder { color: #475569; }
        input:focus { border-color: #6366f1; background: rgba(99, 102, 241, 0.05); }
        .btn-login { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #6366f1, #4f46e5); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 700; 
            font-size: 0.9rem;
            cursor: pointer; 
            margin-top: 10px; 
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4); }
        .error { 
            color: #fca5a5; 
            background: rgba(239, 68, 68, 0.1); 
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 12px 16px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-building"></i></div>
            <h2>Mahalla AI</h2>
            <p>Admin Paneliga kirish</p>
        </div>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Foydalanuvchi nomi</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" required placeholder="admin">
                </div>
            </div>
            <div class="form-group">
                <label>Parol</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Kirish</button>
        </form>
    </div>
</body>
</html>
