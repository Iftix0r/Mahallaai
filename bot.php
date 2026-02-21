<?php
require_once __DIR__ . '/api/config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

$message = $update['message'] ?? null;
$chat_id = $message['chat']['id'] ?? null;
$text = $message['text'] ?? '';

if ($chat_id) {
    if ($text == '/start') {
        $stmt = $db->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([$chat_id]);
        $user = $stmt->fetch();

        if ($user && $user['fullname'] && $user['phone']) {
            sendMessage($chat_id, "<b>Xush kelibsiz, " . htmlspecialchars($user['fullname']) . "!</b>\n\nMahalla AI tizimi sizga xizmat ko'rsatishga tayyor. Quyidagi tugmani bosib Web App-ni ishga tushiring:", [
                'inline_keyboard' => [[
                    ['text' => "🚀 Tizimga kirish", 'web_app' => ['url' => WEBAPP_URL]]
                ]]
            ]);
        } else {
            // Get name from Telegram account
            $first_name = $message['from']['first_name'] ?? '';
            $last_name = $message['from']['last_name'] ?? '';
            $fullname = trim($first_name . ' ' . $last_name);
            
            if (!$user) {
                $stmt = $db->prepare("INSERT INTO users (telegram_id, fullname) VALUES (?, ?)");
                $stmt->execute([$chat_id, $fullname]);
            } elseif (!$user['fullname']) {
                $stmt = $db->prepare("UPDATE users SET fullname = ? WHERE telegram_id = ?");
                $stmt->execute([$fullname, $chat_id]);
            }

            sendMessage($chat_id, "<b>Assalomu alaykum, " . htmlspecialchars($fullname) . "!</b>\n\nMahalla AI - raqamli mahalla tizimiga xush kelibsiz. Tizimdan foydalanish uchun telefon raqamingizni yuboring:", [
                'keyboard' => [[['text' => "📱 Raqamni yuborish", 'request_contact' => true]]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
        }
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([$chat_id]);
        $user = $stmt->fetch();

        if ($user && !$user['phone'] && isset($message['contact'])) {
            $phone = $message['contact']['phone_number'];
            $stmt = $db->prepare("UPDATE users SET phone = ? WHERE telegram_id = ?");
            $stmt->execute([$phone, $chat_id]);
            
            sendMessage($chat_id, "Tabriklaymiz! Ro'yxatdan o'tish muvaffaqiyatli yakunlandi. 🎉", [
                'remove_keyboard' => true
            ]);
            
            sendMessage($chat_id, "Quyidagi tugma orqali Web App-dan foydalanishingiz mumkin:", [
                'inline_keyboard' => [[
                    ['text' => "📱 Web App-ni ochish", 'web_app' => ['url' => WEBAPP_URL]]
                ]]
            ]);
        }
    }
}

function sendMessage($chat_id, $text, $reply_markup = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }
    
    $ch = curl_init(API_URL . 'sendMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_exec($ch);
    curl_close($ch);
}
