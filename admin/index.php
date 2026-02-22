<?php 
require_once 'header.php'; 

// Fetch Stats
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$phoneCount = $db->query("SELECT COUNT(*) FROM users WHERE phone IS NOT NULL AND phone != ''")->fetchColumn();
$regionCount = $db->query("SELECT COUNT(DISTINCT region) FROM users WHERE region IS NOT NULL AND region != ''")->fetchColumn();
$newsCount = $db->query("SELECT COUNT(*) FROM news")->fetchColumn();
?>

<script>document.getElementById('page-title').innerText = 'Dashboard';</script>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h4>Jami foydalanuvchilar</h4>
            <div class="value"><?php echo number_format($userCount); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-phone-alt"></i></div>
        <div class="stat-info">
            <h4>Telefon tasdiqlanganlar</h4>
            <div class="value"><?php echo number_format($phoneCount); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-map-marker-alt"></i></div>
        <div class="stat-info">
            <h4>Hududlar soni</h4>
            <div class="value"><?php echo number_format($regionCount); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-newspaper"></i></div>
        <div class="stat-info">
            <h4>Yangiliklar</h4>
            <div class="value"><?php echo number_format($newsCount); ?></div>
        </div>
    </div>
</div>

<!-- Recent Users Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock" style="margin-right: 8px; color: var(--text-muted);"></i>Oxirgi ro'yxatdan o'tganlar</h3>
        <a href="users.php" class="btn btn-outline" style="font-size: 0.78rem;">
            Barchasini ko'rish <i class="fas fa-arrow-right" style="font-size: 0.7rem;"></i>
        </a>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Foydalanuvchi</th>
                    <th>Telegram ID</th>
                    <th>Telefon</th>
                    <th>Hudud</th>
                    <th>Holat</th>
                    <th>Sana</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("SELECT * FROM users ORDER BY registered_at DESC LIMIT 10");
                while ($user = $stmt->fetch()) {
                    $initial = strtoupper(substr($user['fullname'] ?: 'U', 0, 1));
                    $hasPhone = !empty($user['phone']);
                    echo "<tr>";
                    echo "<td style='color: var(--text-muted); font-weight: 500;'>#{$user['id']}</td>";
                    echo "<td><div class='user-cell'><div class='user-avatar-sm'>{$initial}</div><span style='font-weight: 600;'>" . htmlspecialchars($user['fullname'] ?: 'Noma\'lum') . "</span></div></td>";
                    echo "<td style='color: var(--text-light); font-family: monospace;'>{$user['telegram_id']}</td>";
                    echo "<td>" . ($user['phone'] ?: '<span style="color: var(--text-muted);">—</span>') . "</td>";
                    echo "<td>" . ($user['region'] ?: '<span style="color: var(--text-muted);">—</span>') . "</td>";
                    echo "<td>" . ($hasPhone ? '<span class="badge badge-success">Tasdiqlangan</span>' : '<span class="badge badge-warning">Kutilmoqda</span>') . "</td>";
                    echo "<td style='color: var(--text-light);'>" . date('d.m.Y H:i', strtotime($user['registered_at'])) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
