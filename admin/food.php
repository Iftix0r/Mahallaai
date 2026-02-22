<?php
require_once 'header.php';

// Pagination va qidiruv uchun parametrlar
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['q']) ? $_GET['q'] : '';

echo "<script>document.getElementById('page-title').textContent = 'Oziq-ovqat va Restoranlar';</script>";
?>

<div class="card">
    <div class="card-header">
        <h3>Fast Food Boshqaruvi</h3>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> Yangi Oshxona Qo'shish</button>
    </div>
    <div class="card-body padded" style="text-align: center; color: var(--text-muted); padding: 50px 20px;">
        <i class="fas fa-burger" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2; color: var(--primary);"></i>
        <h3>Hozircha ma'lumotlar yo'q</h3>
        <p>Mahalla Fast Food tizimi tez orada ishga tushadi. Oshxona va restoranlarni shu yerdan boshqarasiz.</p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
