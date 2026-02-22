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
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_user'] = $admin['username'];
        header('Location: index.php');
        exit;
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 30px; color: #1e293b; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: #64748b; }
        input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1.5px solid #e2e8f0; outline: none; transition: 0.2s; }
        input:focus { border-color: var(--primary); }
        .btn-login { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 10px; transition: 0.3s; }
        .btn-login:hover { background: #1e40af; }
        .error { color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Admin Panel</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Foydalanuvchi nomi</label>
                <input type="text" name="username" required placeholder="Masalan: admin">
            </div>
            <div class="form-group">
                <label>Parol</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-login">Kirish</button>
        </form>
    </div>
</body>
</html>
