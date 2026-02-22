<?php require_once 'header.php'; ?>

<script>document.getElementById('page-title').innerText = 'Foydalanuvchilar';</script>

<div class="table-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 1.2rem;">Barcha foydalanuvchilar</h2>
        <input type="text" id="userSearch" placeholder="Qidirish..." style="padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; width: 250px;">
    </div>
    <table id="usersTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>To'liq ism</th>
                <th>Telegram ID</th>
                <th>Telefon</th>
                <th>Hudud / Mahalla</th>
                <th>Ro'yxatdan o'tdi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $db->query("SELECT * FROM users ORDER BY registered_at DESC");
            while ($user = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>#{$user['id']}</td>";
                echo "<td>" . htmlspecialchars($user['fullname']) . "</td>";
                echo "<td>{$user['telegram_id']}</td>";
                echo "<td>" . ($user['phone'] ?: '---') . "</td>";
                echo "<td>" . ($user['region'] ? "{$user['region']}, {$user['mahalla']} mah." : '---') . "</td>";
                echo "<td>" . date('d.m.Y H:i', strtotime($user['registered_at'])) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
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
