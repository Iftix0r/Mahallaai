<?php 
require_once 'header.php'; 

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    if ($new_pass === $confirm_pass) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->execute([$hash, $_SESSION['admin_user']]);
        $msg = "<div style='color: #10b981; margin-bottom: 20px;'>Parol muvaffaqiyatli yangilandi!</div>";
    } else {
        $msg = "<div style='color: #ef4444; margin-bottom: 20px;'>Parollar mos kelmadi!</div>";
    }
}
?>

<script>document.getElementById('page-title').innerText = 'Sozlamalar';</script>

<div class="table-container" style="max-width: 500px;">
    <h2 style="margin-bottom: 20px; font-size: 1.1rem;">Admin parolini o'zgartirish</h2>
    <?php echo $msg; ?>
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-size: 0.9rem;">Yangi parol</label>
            <input type="password" name="new_password" required style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-size: 0.9rem;">Parolni tasdiqlang</label>
            <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0;">
        </div>
        <button type="submit" style="width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Yangilash</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
