<?php require_once 'header.php'; ?>

<script>document.getElementById('page-title').innerText = 'Foydalanuvchilar';</script>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users" style="margin-right: 8px; color: var(--text-muted);"></i>Barcha foydalanuvchilar</h3>
        <div class="search-input">
            <i class="fas fa-search"></i>
            <input type="text" id="userSearch" placeholder="Qidirish...">
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
                    echo "</tr>";
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
