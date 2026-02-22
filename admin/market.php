<?php
require_once 'header.php';
echo "<script>document.getElementById('page-title').textContent = 'Marketlar va Do\'konlar';</script>";
?>

<div class="card">
    <div class="card-header">
        <h3>Barcha Do'konlar ro'yxati</h3>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> Do'kon Qo'shish</button>
    </div>
    <div class="card-body padded" style="text-align: center; color: var(--text-muted); padding: 50px 20px;">
        <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2; color: var(--primary);"></i>
        <h3>Do'kon ma'lumotlari</h3>
        <p>Aholi uchun oziq-ovqat yetkazib beruvchi barcha tayanch nuqtalar (do'konlar) bazasi shu yerda joylashadi.</p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
