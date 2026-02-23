<?php
require_once 'header.php';

// Set admin telegram ID
$admin_telegram_id = 2114098498;

try {
    // Update existing admin
    $stmt = $db->prepare("UPDATE admins SET telegram_id = ? WHERE username = 'admin'");
    $stmt->execute([$admin_telegram_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> Admin Telegram ID muvaffaqiyatli o'rnatildi!<br>";
        echo "Telegram ID: <strong>$admin_telegram_id</strong><br>";
        echo "Endi botda /start buyrug'ini yuboring.";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<i class='fas fa-exclamation-triangle'></i> Admin topilmadi yoki allaqachon o'rnatilgan.";
        echo "</div>";
    }
    
    // Show all admins
    $stmt = $db->query("SELECT * FROM admins");
    $admins = $stmt->fetchAll();
    
    echo "<div class='card'>";
    echo "<div class='card-header'><h3>Barcha Adminlar</h3></div>";
    echo "<div class='card-body padded'>";
    echo "<table class='data-table'>";
    echo "<thead><tr><th>ID</th><th>Username</th><th>Ism</th><th>Telegram ID</th><th>Role</th><th>Status</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td>" . htmlspecialchars($admin['fullname']) . "</td>";
        echo "<td>" . ($admin['telegram_id'] ?: '<span style="color: #999;">Yo\'q</span>') . "</td>";
        echo "<td>" . getRoleLabel($admin['role']) . "</td>";
        echo "<td>" . ($admin['is_active'] ? '<span style="color: #28a745;">✓ Faol</span>' : '<span style="color: #dc3545;">✗ Nofaol</span>') . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div></div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<i class='fas fa-times-circle'></i> Xatolik: " . $e->getMessage();
    echo "</div>";
}

echo "<br><div class='card'>";
echo "<div class='card-header'><h3>📝 Yo'riqnoma</h3></div>";
echo "<div class='card-body padded'>";
echo "<h4>1. Telegram botga o'ting</h4>";
echo "<p>Botingizni Telegram'da oching va <code>/start</code> buyrug'ini yuboring.</p>";
echo "<h4>2. Admin Panel</h4>";
echo "<p>Agar Telegram ID to'g'ri o'rnatilgan bo'lsa, sizga admin panel ko'rinadi.</p>";
echo "<h4>3. Yangi Admin Qo'shish</h4>";
echo "<p>Botda quyidagi buyruqni yuboring:</p>";
echo "<code>/addadmin [telegram_id] [username] [fullname]</code><br><br>";
echo "<strong>Misol:</strong><br>";
echo "<code>/addadmin 123456789 admin2 Admin Ismi</code>";
echo "</div></div>";

require_once 'footer.php';
?>
