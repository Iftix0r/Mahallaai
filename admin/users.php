<?php require_once 'header.php'; 

// Handle user deletion
if (isset($_GET['delete_user']) && hasRole($adminRole, 'admin')) {
    $id = intval($_GET['delete_user']);
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: users.php?deleted=1');
    exit;
}
?>

<script>document.getElementById('page-title').innerText = 'Foydalanuvchilar';</script>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger"><i class="fas fa-trash"></i> Foydalanuvchi o'chirildi.</div>
<?php endif; ?>

<!-- Stats -->
<?php
$totalU = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$verifiedU = $db->query("SELECT COUNT(*) FROM users WHERE phone IS NOT NULL AND phone != ''")->fetchColumn();
$todayU = $db->query("SELECT COUNT(*) FROM users WHERE DATE(registered_at) = CURDATE()")->fetchColumn();
?>
<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h4>Jami foydalanuvchilar</h4>
            <div class="value"><?php echo number_format($totalU); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h4>Tasdiqlangan</h4>
            <div class="value"><?php echo number_format($verifiedU); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-user-clock"></i></div>
        <div class="stat-info">
            <h4>Bugun qo'shilgan</h4>
            <div class="value"><?php echo number_format($todayU); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users" style="margin-right: 8px; color: var(--text-muted);"></i>Barcha foydalanuvchilar</h3>
        <div style="display: flex; gap: 10px; align-items: center;">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" id="userSearch" placeholder="Qidirish...">
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Foydalanuvchi</th>
                    <th>Telegram ID</th>
                    <th>Telefon</th>
                    <th>Hudud / Mahalla</th>
                    <th>Holat</th>
                    <th>Ro'yxatdan o'tdi</th>
                    <?php if (hasRole($adminRole, 'admin')): ?>
                    <th>Amal</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("SELECT * FROM users ORDER BY registered_at DESC");
                while ($user = $stmt->fetch()) {
                    $initial = strtoupper(substr($user['fullname'] ?: 'U', 0, 1));
                    $hasPhone = !empty($user['phone']);
                    $location = $user['region'] ? "{$user['region']}, {$user['mahalla']} mah." : '—';
                    echo "<tr>";
                    echo "<td style='color: var(--text-muted); font-weight: 500;'>#{$user['id']}</td>";
                    echo "<td><div class='user-cell'><div class='user-avatar-sm'>{$initial}</div><span style='font-weight: 600;'>" . htmlspecialchars($user['fullname'] ?: 'Noma\'lum') . "</span></div></td>";
                    echo "<td style='font-family: monospace; color: var(--text-light);'>{$user['telegram_id']}</td>";
                    echo "<td>" . ($user['phone'] ?: '<span style="color: var(--text-muted);">—</span>') . "</td>";
                    echo "<td>" . ($location != '—' ? $location : '<span style="color: var(--text-muted);">—</span>') . "</td>";
                    echo "<td>" . ($hasPhone ? '<span class="badge badge-success">Tasdiqlangan</span>' : '<span class="badge badge-warning">Kutilmoqda</span>') . "</td>";
                    echo "<td style='color: var(--text-light);'>" . date('d.m.Y H:i', strtotime($user['registered_at'])) . "</td>";
                    if (hasRole($adminRole, 'admin')) {
                        echo "<td><a href='?delete_user={$user['id']}' class='btn btn-danger' style='font-size: 0.72rem; padding: 5px 10px;' onclick='return confirm(\"O\\\"chirishni tasdiqlaysizmi?\")'><i class='fas fa-trash-alt'></i></a></td>";
                    }
                    echo "</tr>";
                }

                if ($totalU == 0) {
                    $colspan = hasRole($adminRole, 'admin') ? 8 : 7;
                    echo "<tr><td colspan='{$colspan}' style='text-align: center; padding: 40px; color: var(--text-muted);'><i class='fas fa-inbox' style='font-size: 2rem; display: block; margin-bottom: 10px;'></i>Foydalanuvchilar yo'q</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('userSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#usersTable tbody tr');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php require_once 'footer.php'; ?>
