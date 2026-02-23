<?php
require_once 'header.php';
echo "<script>document.getElementById('page-title').textContent = 'Mahalla Avto';</script>";

// Get statistics
$totalSalons = $db->query("SELECT COUNT(*) FROM auto_salons WHERE is_active = 1")->fetchColumn();
$totalCars = $db->query("SELECT COUNT(*) FROM cars WHERE is_sold = 0")->fetchColumn();
$soldCars = $db->query("SELECT COUNT(*) FROM cars WHERE is_sold = 1")->fetchColumn();
$privateCars = $db->query("SELECT COUNT(*) FROM cars WHERE listing_type = 'private' AND is_sold = 0")->fetchColumn();

// Get salons
$stmt = $db->query("SELECT s.*, u.fullname as owner_name FROM auto_salons s 
                    JOIN users u ON s.owner_id = u.id 
                    ORDER BY s.created_at DESC");
$salons = $stmt->fetchAll();

// Get recent cars
$stmt = $db->query("SELECT c.*, 
                    CASE WHEN c.salon_id IS NOT NULL THEN s.name ELSE 'Shaxsiy' END as seller_name
                    FROM cars c 
                    LEFT JOIN auto_salons s ON c.salon_id = s.id 
                    ORDER BY c.created_at DESC LIMIT 20");
$cars = $stmt->fetchAll();
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}
.stat-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.stat-value {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
}
.stat-label {
    color: var(--text-muted);
    font-size: 14px;
}
.car-card {
    display: flex;
    gap: 16px;
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
}
.car-card:hover {
    background: #fafbfc;
}
.car-image {
    width: 120px;
    height: 90px;
    border-radius: 8px;
    object-fit: cover;
    background: #e9ecef;
}
.car-info {
    flex: 1;
}
.car-title {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
}
.car-details {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.car-price {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
}
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.badge-salon { background: #e0f2fe; color: #0369a1; }
.badge-private { background: #fef3c7; color: #92400e; }
.badge-sold { background: #fee2e2; color: #991b1b; }
</style>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
            <i class="fas fa-store"></i>
        </div>
        <div class="stat-value"><?= number_format($totalSalons) ?></div>
        <div class="stat-label">Avtosalonlar</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #dcfce7; color: #15803d;">
            <i class="fas fa-car"></i>
        </div>
        <div class="stat-value"><?= number_format($totalCars) ?></div>
        <div class="stat-label">Faol E'lonlar</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
            <i class="fas fa-user"></i>
        </div>
        <div class="stat-value"><?= number_format($privateCars) ?></div>
        <div class="stat-label">Shaxsiy E'lonlar</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fee2e2; color: #991b1b;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value"><?= number_format($soldCars) ?></div>
        <div class="stat-label">Sotilgan</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3><i class="fas fa-store" style="margin-right: 8px;"></i>Avtosalonlar</h3>
    </div>
    <div class="card-body padded">
        <?php if (empty($salons)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-store" style="font-size: 3rem; opacity: 0.3;"></i>
                <p>Hozircha avtosalonlar yo'q</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nomi</th>
                        <th>Egasi</th>
                        <th>Telefon</th>
                        <th>Mashinalar</th>
                        <th>Reyting</th>
                        <th>Holat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salons as $salon): ?>
                    <tr>
                        <td><?= $salon['id'] ?></td>
                        <td><strong><?= htmlspecialchars($salon['name']) ?></strong></td>
                        <td><?= htmlspecialchars($salon['owner_name']) ?></td>
                        <td><?= htmlspecialchars($salon['phone']) ?></td>
                        <td><?= $salon['total_cars'] ?></td>
                        <td>⭐ <?= number_format($salon['rating'], 2) ?></td>
                        <td>
                            <?php if ($salon['is_active']): ?>
                                <span style="color: #15803d;">✓ Faol</span>
                            <?php else: ?>
                                <span style="color: #991b1b;">✗ Nofaol</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-car" style="margin-right: 8px;"></i>So'nggi E'lonlar</h3>
    </div>
    <div class="card-body">
        <?php if (empty($cars)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-car" style="font-size: 3rem; opacity: 0.3;"></i>
                <p>Hozircha e'lonlar yo'q</p>
            </div>
        <?php else: ?>
            <?php foreach ($cars as $car): ?>
            <div class="car-card">
                <div class="car-image">
                    <?php if ($car['images']): ?>
                        <img src="<?= htmlspecialchars(explode(',', $car['images'])[0]) ?>" alt="Car" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #e9ecef; border-radius: 8px;">
                            <i class="fas fa-car" style="font-size: 2rem; color: #adb5bd;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="car-info">
                    <div class="car-title"><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></div>
                    <div class="car-details">
                        <?= $car['year'] ?> • <?= number_format($car['mileage']) ?> km • <?= $car['fuel_type'] ?> • <?= $car['transmission'] ?>
                    </div>
                    <div>
                        <span class="badge badge-<?= $car['listing_type'] ?>">
                            <?= $car['listing_type'] == 'salon' ? '🏢 ' . $car['seller_name'] : '👤 Shaxsiy' ?>
                        </span>
                        <?php if ($car['is_sold']): ?>
                            <span class="badge badge-sold">Sotilgan</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="car-price">$<?= number_format($car['price']) ?></div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                        👁 <?= $car['views'] ?> ko'rishlar
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
