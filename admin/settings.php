<?php 
require_once 'header.php'; 

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    if (strlen($new_pass) < 6) {
        $msg = 'short';
    } elseif ($new_pass === $confirm_pass) {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->execute([$hash, $_SESSION['admin_user']]);
        $msg = 'success';
    } else {
        $msg = 'mismatch';
    }
}
?>

<script>document.getElementById('page-title').innerText = 'Sozlamalar';</script>

<?php if ($msg == 'success'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Parol muvaffaqiyatli yangilandi!</div>
<?php elseif ($msg == 'mismatch'): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Parollar mos kelmadi!</div>
<?php elseif ($msg == 'short'): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Parol kamida 6 ta belgidan iborat bo'lishi kerak!</div>
<?php endif; ?>

<div style="max-width: 480px;">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-lock" style="margin-right: 8px; color: var(--text-muted);"></i>Parolni o'zgartirish</h3>
        </div>
        <div class="card-body padded">
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-key" style="margin-right: 6px;"></i>Yangi parol</label>
                    <input type="password" name="new_password" required class="form-control" placeholder="Kamida 6 ta belgi" minlength="6">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-redo" style="margin-right: 6px;"></i>Parolni tasdiqlang</label>
                    <input type="password" name="confirm_password" required class="form-control" placeholder="Parolni qayta kiriting">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fas fa-save"></i> Yangilash
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
