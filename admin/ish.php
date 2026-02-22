<?php
require_once 'header.php';
echo "<script>document.getElementById('page-title').textContent = 'Bo\'sh Ish O\'rinlari';</script>";
?>

<div class="card">
    <div class="card-header">
        <h3>E'lonlar Boshqaruvi</h3>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> E'lon Qo'shish</button>
    </div>
    <div class="card-body padded" style="text-align: center; color: var(--text-muted); padding: 50px 20px;">
        <i class="fas fa-briefcase" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2; color: var(--primary);"></i>
        <h3>Mahalla Ishlari</h3>
        <p>Barcha bo'sh ish o'rinlari va xususiy vakansiyalar e'lonlarini shu yerda o'chirish yoki ko'rib chiqish mumkin.</p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
