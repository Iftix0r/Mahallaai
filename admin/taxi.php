<?php
require_once 'header.php';
echo "<script>document.getElementById('page-title').textContent = 'Taxi Xizmati';</script>";
?>

<div class="card">
    <div class="card-header">
        <h3>Mahalla Taxi Haydovchilari</h3>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> Haydovchi Qo'shish</button>
    </div>
    <div class="card-body padded" style="text-align: center; color: var(--text-muted); padding: 50px 20px;">
        <i class="fas fa-taxi" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2; color: var(--primary);"></i>
        <h3>Taksi tizimi ro'yxati </h3>
        <p>Barcha taksi haydovchilari va yo'lovchi buyurtmalari ro'yxati shu yerda boshqariladi.</p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
