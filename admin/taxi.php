<?php
require_once 'header.php';
echo "<script>document.getElementById('page-title').textContent = 'Taxi Xizmati';</script>";

// Get all drivers
$stmt = $db->query("SELECT d.*, u.fullname, u.phone FROM taxi_drivers d 
                    JOIN users u ON d.user_id = u.id 
                    ORDER BY d.created_at DESC");
$drivers = $stmt->fetchAll();

// Get all orders
$stmt = $db->query("SELECT o.*, u.fullname as customer_name, d.car_number, d.car_model 
                    FROM taxi_orders o 
                    JOIN users u ON o.customer_id = u.id 
                    LEFT JOIN taxi_drivers d ON o.driver_id = d.id 
                    ORDER BY o.created_at DESC LIMIT 50");
$orders = $stmt->fetchAll();
?>

<style>
.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-assigned { background: #cfe2ff; color: #084298; }
.status-accepted { background: #d1e7dd; color: #0f5132; }
.status-completed { background: #d3d3d3; color: #495057; }
.status-cancelled { background: #f8d7da; color: #842029; }
.driver-online { color: #28a745; }
.driver-offline { color: #dc3545; }
</style>

<div class="card">
    <div class="card-header">
        <h3>Mahalla Taxi Haydovchilari</h3>
    </div>
    <div class="card-body padded">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Haydovchi</th>
                    <th>Telefon</th>
                    <th>Mashina</th>
                    <th>Turi</th>
                    <th>Holat</th>
                    <th>Reytingi</th>
                    <th>Safarlar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drivers)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                        <i class="fas fa-taxi" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p>Hozircha haydovchilar yo'q</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($drivers as $driver): ?>
                    <tr>
                        <td><?= $driver['id'] ?></td>
                        <td><?= htmlspecialchars($driver['fullname']) ?></td>
                        <td><?= htmlspecialchars($driver['phone']) ?></td>
                        <td><?= htmlspecialchars($driver['car_model']) ?> (<?= htmlspecialchars($driver['car_number']) ?>)</td>
                        <td><?= htmlspecialchars($driver['car_type']) ?></td>
                        <td>
                            <?php if ($driver['is_online']): ?>
                                <i class="fas fa-circle driver-online"></i> Online
                                <?php if ($driver['is_busy']): ?>
                                    <span style="color: #ffc107;">(Band)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <i class="fas fa-circle driver-offline"></i> Offline
                            <?php endif; ?>
                        </td>
                        <td>⭐ <?= number_format($driver['rating'], 2) ?></td>
                        <td><?= $driver['total_trips'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3>Buyurtmalar Tarixi</h3>
    </div>
    <div class="card-body padded">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Yo'lovchi</th>
                    <th>Qayerdan</th>
                    <th>Qayerga</th>
                    <th>Mashina</th>
                    <th>Narx</th>
                    <th>Holat</th>
                    <th>Vaqt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                        <i class="fas fa-receipt" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p>Hozircha buyurtmalar yo'q</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars(substr($order['from_address'], 0, 30)) ?>...</td>
                        <td><?= htmlspecialchars(substr($order['to_address'], 0, 30)) ?>...</td>
                        <td><?= $order['car_number'] ? htmlspecialchars($order['car_model']) : '-' ?></td>
                        <td><?= number_format($order['price'], 0) ?> so'm</td>
                        <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?php
                                $statuses = [
                                    'pending' => 'Kutilmoqda',
                                    'assigned' => 'Tayinlangan',
                                    'accepted' => 'Qabul qilindi',
                                    'completed' => 'Yakunlandi',
                                    'cancelled' => 'Bekor qilindi'
                                ];
                                echo $statuses[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
