<?php 
require_once 'header.php'; 

// Fetch Stats
$userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$regionCount = $db->query("SELECT COUNT(DISTINCT region) FROM users WHERE region != ''")->fetchColumn();
$newsCount = $db->query("SELECT COUNT(*) FROM news")->fetchColumn();
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Jami Foydalanuvchilar</h3>
        <div class="value"><?php echo number_format($userCount); ?></div>
    </div>
    <div class="stat-card">
        <h3>Hududlar soni</h3>
        <div class="value"><?php echo number_format($regionCount); ?></div>
    </div>
    <div class="stat-card">
        <h3>Yangiliklar</h3>
        <div class="value"><?php echo number_format($newsCount); ?></div>
    </div>
</div>

<div class="section-container">
    <div class="table-container">
        <h2 style="margin-bottom: 20px; font-size: 1.2rem;">Oxirgi ro'yxatdan o'tganlar</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fullname</th>
                    <th>Telegram ID</th>
                    <th>Telefon</th>
                    <th>Hudud</th>
                    <th>Sana</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("SELECT * FROM users ORDER BY registered_at DESC LIMIT 10");
                while ($user = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>#{$user['id']}</td>";
                    echo "<td>" . htmlspecialchars($user['fullname']) . "</td>";
                    echo "<td>{$user['telegram_id']}</td>";
                    echo "<td>" . ($user['phone'] ?: '---') . "</td>";
                    echo "<td>" . ($user['region'] ?: '---') . "</td>";
                    echo "<td>" . date('d.m.Y H:i', strtotime($user['registered_at'])) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
