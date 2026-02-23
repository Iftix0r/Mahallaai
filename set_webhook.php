<?php
/**
 * Webhook sozlamalarini yangilash uchun bir martalik skript.
 * Brauzerda ochilganda webhook-ni my_chat_member, chat_member va boshqa eventlarni 
 * qabul qiladigan qilib qayta o'rnatadi.
 * 
 * Faqat bir marta ishlatish kerak, keyin o'chirib tashlash mumkin.
 */

require_once __DIR__ . '/api/config.php';

// Webhook URL — bot.php ga yo'naltiriladi
$webhookUrl = 'https://mahallaai.bigsaver.ru/bot.php';

// Barcha kerakli update turlarini qo'shish
$allowedUpdates = json_encode([
    'message',
    'edited_message', 
    'channel_post',
    'edited_channel_post',
    'my_chat_member',      // Bot guruh/kanalga qo'shilganda/chiqarilganda
    'chat_member',         // Guruh a'zolari o'zgarganda
    'callback_query'       // Inline tugmalar bosilganda
]);

$url = API_URL . "setWebhook?url=" . urlencode($webhookUrl) . "&allowed_updates=" . urlencode($allowedUpdates);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

echo "<html><head><meta charset='utf-8'><style>
body { font-family: 'Inter', sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
.card { background: white; border-radius: 16px; padding: 40px; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); text-align: center; }
.success { color: #059669; }
.error { color: #dc2626; }
h2 { margin-bottom: 16px; }
pre { background: #f8fafc; padding: 16px; border-radius: 10px; text-align: left; font-size: 13px; overflow-x: auto; }
a { color: #6366f1; text-decoration: none; font-weight: 600; }
</style></head><body><div class='card'>";

if ($result && $result['ok']) {
    echo "<h2 class='success'>✅ Webhook muvaffaqiyatli yangilandi!</h2>";
    echo "<p>Endi bot quyidagi eventlarni qabul qiladi:</p>";
    echo "<ul style='text-align: left; line-height: 2;'>";
    echo "<li>📨 <strong>message</strong> — oddiy xabarlar</li>";
    echo "<li>✏️ <strong>edited_message</strong> — tahrirlangan xabarlar</li>";
    echo "<li>📢 <strong>channel_post</strong> — kanal postlari</li>";
    echo "<li>🤖 <strong>my_chat_member</strong> — bot qo'shilish/chiqarish</li>";
    echo "<li>👥 <strong>chat_member</strong> — a'zolar o'zgarishi</li>";
    echo "<li>🔘 <strong>callback_query</strong> — inline tugmalar</li>";
    echo "</ul>";
} else {
    echo "<h2 class='error'>❌ Xatolik yuz berdi</h2>";
}

echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
echo "<br><a href='admin/'>← Admin panelga qaytish</a>";
echo "</div></body></html>";
