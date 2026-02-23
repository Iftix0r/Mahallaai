<?php
require_once __DIR__ . '/api/config.php';

try {
    // Admin hisobiga 'system' rolini berish
    $stmt = $db->prepare("UPDATE admins SET role = 'system' WHERE username = 'admin'");
    $stmt->execute();
    
    echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
    echo "<h1 style='color: #059669;'>✅ Muvaffaqiyatli!</h1>";
    echo "<p>'admin' foydalanuvchisi endi 'system' (tizim) administratoriga aylandi.</p>";
    echo "<p>Endi admin paneldan <b>chiqib (Logout)</b>, qaytadan kiring.</p>";
    echo "<br><a href='admin/index.php' style='color: #6366f1; text-decoration: none; font-weight: bold;'>← Dashboardga qaytish</a>";
    echo "</div>";
} catch (Exception $e) {
    echo "<h1 style='color: #dc2626;'>❌ Xatolik yuz berdi:</h1> " . $e->getMessage();
}
