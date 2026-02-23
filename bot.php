<?php
require_once __DIR__ . '/api/config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) exit;

// ====== 1. Handle my_chat_member — Bot guruh/kanalga qo'shilganda DARHOL saqlaydi ======
if (isset($update['my_chat_member'])) {
    $chatMember = $update['my_chat_member'];
    $mcChat = $chatMember['chat'];
    $mcChatId = $mcChat['id'];
    $mcChatType = $mcChat['type'] ?? 'private';
    $mcChatTitle = $mcChat['title'] ?? $mcChat['first_name'] ?? '';
    $newStatus = $chatMember['new_chat_member']['status'] ?? '';
    
    // Bot guruhga qo'shildi yoki admin qilindi — saqlash
    if (in_array($newStatus, ['member', 'administrator'])) {
        try {
            $stmt = $db->prepare("INSERT INTO chats (chat_id, chat_type, chat_title) VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE chat_title = VALUES(chat_title), chat_type = VALUES(chat_type)");
            $stmt->execute([$mcChatId, $mcChatType, $mcChatTitle]);
        } catch (Exception $e) {}
    }
    
    // Bot guruhdan chiqarildi — o'chirish
    if (in_array($newStatus, ['left', 'kicked'])) {
        try {
            $stmt = $db->prepare("DELETE FROM chats WHERE chat_id = ?");
            $stmt->execute([$mcChatId]);
        } catch (Exception $e) {}
    }
}

// ====== 2. Xabarni olish (oddiy, tahrirlangan, kanal posti) ======
$message = $update['message'] ?? $update['channel_post'] ?? $update['edited_message'] ?? $update['edited_channel_post'] ?? null;
$chat_id = $message['chat']['id'] ?? null;
$text = $message['text'] ?? '';
$chat_type = $message['chat']['type'] ?? 'private';
$chat_title = $message['chat']['title'] ?? $message['chat']['first_name'] ?? '';

// ====== 3. Har qanday xabar kelganda chatni saqlash ======
if ($chat_id) {
    try {
        $stmt = $db->prepare("INSERT INTO chats (chat_id, chat_type, chat_title) VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE chat_title = VALUES(chat_title), chat_type = VALUES(chat_type)");
        $stmt->execute([$chat_id, $chat_type, $chat_title]);
    } catch (Exception $e) {
        // Ignore
    }
}


if ($chat_id) {
    // Check if user is admin
    $stmt = $db->prepare("SELECT * FROM admins WHERE telegram_id = ? AND is_active = 1");
    $stmt->execute([$chat_id]);
    $admin = $stmt->fetch();
    
    if ($text == '/start') {
        $stmt = $db->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([$chat_id]);
        $user = $stmt->fetch();

        // Admin panel for admins
        if ($admin) {
            sendMessage($chat_id, "👨‍💼 <b>Admin Panel</b>\n\nXush kelibsiz, " . htmlspecialchars($admin['fullname']) . "!\n\nQuyidagi komandalardan foydalanishingiz mumkin:", [
                'inline_keyboard' => [
                    [['text' => "🌐 Web Admin Panel", 'web_app' => ['url' => WEBAPP_URL . '/admin/']]],
                    [['text' => "📢 Habar Yuborish", 'callback_data' => 'broadcast_menu']],
                    [['text' => "📊 Statistika", 'callback_data' => 'show_stats']],
                    [['text' => "👥 Foydalanuvchilar", 'callback_data' => 'show_users']],
                    [['text' => "➕ Admin Qo'shish", 'callback_data' => 'add_admin']],
                    [['text' => "🚕 Taxi Tizimi", 'callback_data' => 'taxi_stats']],
                    [['text' => "📝 Yordam", 'callback_data' => 'help_admin']]
                ]
            ]);
            exit;
        }

        if ($user && $user['fullname'] && $user['phone']) {
            $buttons = [
                [['text' => "🏢 Tizimga kirish", 'web_app' => ['url' => WEBAPP_URL . "?tab=system"]]],
                [['text' => "🍔 Mahalla tezkor ovqatlar", 'web_app' => ['url' => WEBAPP_URL . "?tab=food"]]],
                [['text' => "🚕 Mahalla Taxi", 'web_app' => ['url' => WEBAPP_URL . "?tab=taxi"]]],
                [['text' => "🛒 Mahalla Market", 'web_app' => ['url' => WEBAPP_URL . "?tab=market"]]],
                [['text' => "💼 Mahalla Ishlar", 'web_app' => ['url' => WEBAPP_URL . "?tab=ish"]]],
                [['text' => "🎓 AB Education", 'web_app' => ['url' => WEBAPP_URL . "?tab=abedu"]]],
                [['text' => "🏦 Mahalla Bank", 'web_app' => ['url' => WEBAPP_URL . "?tab=bank"]]]
            ];
            
            sendMessage($chat_id, "<b>Xush kelibsiz, " . htmlspecialchars($user['fullname']) . "!</b>\n\nMahalla AI tizimi sizga xizmat ko'rsatishga tayyor. Kerakli bo'limni tanlang:", [
                'inline_keyboard' => $buttons
            ]);
        } else {
            // Get name from Telegram account
            $first_name = $message['from']['first_name'] ?? '';
            $last_name = $message['from']['last_name'] ?? '';
            $fullname = trim($first_name . ' ' . $last_name);
            
            if (!$user) {
                $stmt = $db->prepare("INSERT INTO users (telegram_id, fullname) VALUES (?, ?)");
                $stmt->execute([$chat_id, $fullname]);
                
                // Notify admin about new user
                notifyAdminNewUser($chat_id, $fullname);
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
    }
    // Admin commands
    elseif ($admin && $text == '/broadcast') {
        sendMessage($chat_id, "📢 <b>Habar Yuborish</b>\n\nHabar yuborish uchun quyidagi formatda yuboring:\n\n<code>/send [all|users|groups|channels]\nXabar matni</code>\n\nYoki media fayl bilan birga caption yozing.\n\nMisol:\n<code>/send all\nYangi xizmatlar haqida e'lon!</code>", [
            'inline_keyboard' => [
                [['text' => "🌐 Admin Panel", 'web_app' => ['url' => WEBAPP_URL . '/admin/broadcast.php']]]
            ]
        ]);
    }
    elseif ($admin && preg_match('/^\/addadmin\s+(\d+)\s+(\S+)\s+(.+)$/s', $text, $matches)) {
        $new_telegram_id = $matches[1];
        $new_username = $matches[2];
        $new_fullname = trim($matches[3]);
        
        try {
            // Check if admin already exists
            $stmt = $db->prepare("SELECT * FROM admins WHERE telegram_id = ? OR username = ?");
            $stmt->execute([$new_telegram_id, $new_username]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                sendMessage($chat_id, "❌ Bu admin allaqachon mavjud!");
            } else {
                // Create default password
                $default_password = password_hash('admin123', PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO admins (username, password, telegram_id, role, fullname, is_active) VALUES (?, ?, ?, 'admin', ?, 1)");
                $stmt->execute([$new_username, $default_password, $new_telegram_id, $new_fullname]);
                
                sendMessage($chat_id, "✅ <b>Admin qo'shildi!</b>\n\n" .
                    "👤 Ism: " . htmlspecialchars($new_fullname) . "\n" .
                    "🆔 Telegram ID: " . $new_telegram_id . "\n" .
                    "👨‍💼 Username: " . $new_username . "\n" .
                    "🔑 Parol: admin123\n\n" .
                    "Admin /start buyrug'ini yuborishi mumkin.");
                
                // Notify new admin
                sendMessage($new_telegram_id, "🎉 <b>Tabriklaymiz!</b>\n\n" .
                    "Siz Mahalla AI tizimida admin sifatida qo'shildingiz.\n\n" .
                    "/start - Admin panelni ochish");
            }
        } catch (Exception $e) {
            sendMessage($chat_id, "❌ Xatolik: " . $e->getMessage());
        }
    }
    elseif ($admin && preg_match('/^\/send\s+(all|users|groups|channels)\s+(.+)/s', $text, $matches)) {
        $target = $matches[1];
        $messageText = trim($matches[2]);
        
        // Get media if exists
        $mediaType = 'text';
        $mediaFileId = null;
        
        if (isset($message['photo'])) {
            $mediaType = 'photo';
            $photos = $message['photo'];
            $mediaFileId = end($photos)['file_id'];
        } elseif (isset($message['video'])) {
            $mediaType = 'video';
            $mediaFileId = $message['video']['file_id'];
        } elseif (isset($message['document'])) {
            $mediaType = 'document';
            $mediaFileId = $message['document']['file_id'];
        }
        
        // Start broadcasting
        sendMessage($chat_id, "⏳ Habar yuborilmoqda...");
        
        $result = broadcastMessage($target, $messageText, $mediaType, $mediaFileId, $admin['username']);
        
        sendMessage($chat_id, "✅ <b>Habar yuborildi!</b>\n\n" .
            "📤 Yuborildi: {$result['sent']}\n" .
            "❌ Xatolik: {$result['failed']}\n" .
            "📊 Jami: {$result['total']}");
    }
    elseif ($admin && isset($message['forward_from']) || isset($message['forward_from_chat'])) {
        // Forward message to all users
        sendMessage($chat_id, "📨 <b>Forward Qilish</b>\n\nBu xabarni barchaga forward qilishni xohlaysizmi?", [
            'inline_keyboard' => [
                [
                    ['text' => "✅ Ha, barchaga", 'callback_data' => 'forward_all_' . $message['message_id']],
                    ['text' => "❌ Yo'q", 'callback_data' => 'cancel']
                ]
            ]
        ]);
    }
    elseif ($admin && $text == '/stats') {
        $totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL")->fetchColumn();
        $totalGroups = $db->query("SELECT COUNT(*) FROM chats WHERE chat_type IN ('group', 'supergroup')")->fetchColumn();
        $totalChannels = $db->query("SELECT COUNT(*) FROM chats WHERE chat_type = 'channel'")->fetchColumn();
        
        sendMessage($chat_id, "📊 <b>Statistika</b>\n\n" .
            "👥 Foydalanuvchilar: " . number_format($totalUsers) . "\n" .
            "👨‍👩‍👧‍👦 Guruhlar: " . number_format($totalGroups) . "\n" .
            "📢 Kanallar: " . number_format($totalChannels) . "\n" .
            "📈 Jami: " . number_format($totalUsers + $totalGroups + $totalChannels));
    }
    else {
        $stmt = $db->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([$chat_id]);
        $user = $stmt->fetch();

        // Check if admin is in broadcast mode
        if ($admin && $admin['broadcast_mode'] == 1) {
            // Save message/media
            $messageText = $text;
            $mediaType = 'text';
            $mediaFileId = null;
            
            if (isset($message['photo'])) {
                $mediaType = 'photo';
                $photos = $message['photo'];
                $mediaFileId = end($photos)['file_id'];
                $messageText = $message['caption'] ?? '';
            } elseif (isset($message['video'])) {
                $mediaType = 'video';
                $mediaFileId = $message['video']['file_id'];
                $messageText = $message['caption'] ?? '';
            } elseif (isset($message['document'])) {
                $mediaType = 'document';
                $mediaFileId = $message['document']['file_id'];
                $messageText = $message['caption'] ?? '';
            }
            
            // Save to admin record
            $stmt = $db->prepare("UPDATE admins SET broadcast_message = ?, broadcast_media_type = ?, broadcast_media_id = ? WHERE telegram_id = ?");
            $stmt->execute([$messageText, $mediaType, $mediaFileId, $chat_id]);
            
            // Ask target
            sendMessage($chat_id, "✅ <b>Xabar qabul qilindi!</b>\n\nEndi kimga yuborishni tanlang:", [
                'inline_keyboard' => [
                    [['text' => "🌐 Barchaga", 'callback_data' => 'broadcast_target_all']],
                    [['text' => "👥 Foydalanuvchilarga", 'callback_data' => 'broadcast_target_users']],
                    [['text' => "👨‍👩‍👧‍👦 Guruhlarga", 'callback_data' => 'broadcast_target_groups']],
                    [['text' => "📢 Kanallarga", 'callback_data' => 'broadcast_target_channels']],
                    [['text' => "❌ Bekor qilish", 'callback_data' => 'broadcast_cancel']]
                ]
            ]);
            exit;
        }

        if ($user && !$user['phone'] && isset($message['contact'])) {
            $phone = $message['contact']['phone_number'];
            $stmt = $db->prepare("UPDATE users SET phone = ? WHERE telegram_id = ?");
            $stmt->execute([$phone, $chat_id]);
            
            sendMessage($chat_id, "Tabriklaymiz! Ro'yxatdan o'tish muvaffaqiyatli yakunlandi. 🎉", [
                'remove_keyboard' => true
            ]);
            
            sendMessage($chat_id, "Quyidagi tugmalar orqali xizmatlardan foydalanishingiz mumkin:", [
                'inline_keyboard' => [
                    [['text' => "🏢 Tizimga kirish", 'web_app' => ['url' => WEBAPP_URL . "?tab=system"]]],
                    [['text' => "🍔 Mahalla tezkor ovqatlar", 'web_app' => ['url' => WEBAPP_URL . "?tab=food"]]],
                    [['text' => "🚕 Mahalla Taxi", 'web_app' => ['url' => WEBAPP_URL . "?tab=taxi"]]],
                    [['text' => "🛒 Mahalla Market", 'web_app' => ['url' => WEBAPP_URL . "?tab=market"]]],
                    [['text' => "💼 Mahalla Ish", 'web_app' => ['url' => WEBAPP_URL . "?tab=ish"]]],
                    [['text' => "🎓 AB Education", 'web_app' => ['url' => WEBAPP_URL . "?tab=abedu"]]],
                    [['text' => "🏦 Mahalla Bank", 'web_app' => ['url' => WEBAPP_URL . "?tab=bank"]]]
                ]
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

// Notify driver about new order
function notifyDriver($driver_telegram_id, $order_id, $from, $to, $price) {
    if (!$driver_telegram_id) return;
    
    $message = "🔔 <b>Yangi buyurtma!</b>\n\n";
    $message .= "📍 Qayerdan: $from\n";
    $message .= "🎯 Qayerga: $to\n";
    $message .= "💰 Narx: " . number_format($price, 0) . " so'm\n\n";
    $message .= "Buyurtmani haydovchi panelidan qabul qiling!";
    
    sendMessage($driver_telegram_id, $message, [
        'inline_keyboard' => [
            [['text' => "🚕 Haydovchi paneli", 'web_app' => ['url' => WEBAPP_URL . '/driver.html']]]
        ]
    ]);
}

// Notify customer about driver assignment
function notifyCustomer($customer_telegram_id, $driver_name, $car_model, $car_number) {
    if (!$customer_telegram_id) return;
    
    $message = "✅ <b>Haydovchi tayinlandi!</b>\n\n";
    $message .= "👤 Haydovchi: $driver_name\n";
    $message .= "🚗 Mashina: $car_model\n";
    $message .= "🔢 Raqam: $car_number\n\n";
    $message .= "Haydovchi yo'lda! 3-5 daqiqada yetib keladi.";
    
    sendMessage($customer_telegram_id, $message);
}

// Notify admin about new user
function notifyAdminNewUser($user_telegram_id, $fullname) {
    global $db;
    
    // Get all admins
    $stmt = $db->query("SELECT telegram_id FROM admins WHERE telegram_id IS NOT NULL AND is_active = 1");
    $admins = $stmt->fetchAll();
    
    $message = "🆕 <b>Yangi foydalanuvchi!</b>\n\n";
    $message .= "👤 Ism: " . htmlspecialchars($fullname) . "\n";
    $message .= "🆔 Telegram ID: " . $user_telegram_id . "\n";
    $message .= "📅 Vaqt: " . date('d.m.Y H:i') . "\n\n";
    $message .= "Jami foydalanuvchilar: " . $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    foreach ($admins as $admin) {
        sendMessage($admin['telegram_id'], $message, [
            'inline_keyboard' => [
                [['text' => "👥 Foydalanuvchilar", 'callback_data' => 'show_users']],
                [['text' => "📊 Statistika", 'callback_data' => 'show_stats']]
            ]
        ]);
    }
}



// Handle callback queries
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callback_id = $callback['id'];
    $callback_data = $callback['data'];
    $callback_chat_id = $callback['message']['chat']['id'];
    $callback_message_id = $callback['message']['message_id'];
    
    // Check if admin
    $stmt = $db->prepare("SELECT * FROM admins WHERE telegram_id = ? AND is_active = 1");
    $stmt->execute([$callback_chat_id]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        if ($callback_data == 'broadcast_menu') {
            answerCallback($callback_id, "📢 Habar yuborish");
            editMessage($callback_chat_id, $callback_message_id, 
                "📢 <b>Habar Yuborish</b>\n\n" .
                "Iltimos, yubormoqchi bo'lgan xabar yoki media faylni yuboring.\n\n" .
                "• Oddiy matn\n" .
                "• Rasm + caption\n" .
                "• Video + caption\n" .
                "• Hujjat + caption\n\n" .
                "Xabarni yuborganingizdan keyin, kimga yuborishni tanlaysiz.");
            
            // Set broadcast mode for this admin
            $stmt = $db->prepare("UPDATE admins SET broadcast_mode = 1 WHERE telegram_id = ?");
            $stmt->execute([$callback_chat_id]);
        }
        elseif ($callback_data == 'show_stats') {
            $totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL")->fetchColumn();
            $totalGroups = $db->query("SELECT COUNT(*) FROM chats WHERE chat_type IN ('group', 'supergroup')")->fetchColumn();
            $totalChannels = $db->query("SELECT COUNT(*) FROM chats WHERE chat_type = 'channel'")->fetchColumn();
            $totalDrivers = $db->query("SELECT COUNT(*) FROM taxi_drivers")->fetchColumn();
            $totalOrders = $db->query("SELECT COUNT(*) FROM taxi_orders")->fetchColumn();
            
            answerCallback($callback_id, "📊 Statistika");
            editMessage($callback_chat_id, $callback_message_id,
                "📊 <b>Tizim Statistikasi</b>\n\n" .
                "👥 Foydalanuvchilar: " . number_format($totalUsers) . "\n" .
                "👨‍👩‍👧‍👦 Guruhlar: " . number_format($totalGroups) . "\n" .
                "📢 Kanallar: " . number_format($totalChannels) . "\n" .
                "🚕 Haydovchilar: " . number_format($totalDrivers) . "\n" .
                "📦 Buyurtmalar: " . number_format($totalOrders) . "\n\n" .
                "📈 Jami: " . number_format($totalUsers + $totalGroups + $totalChannels));
        }
        elseif ($callback_data == 'show_users') {
            $stmt = $db->query("SELECT * FROM users WHERE telegram_id IS NOT NULL ORDER BY registered_at DESC LIMIT 10");
            $users = $stmt->fetchAll();
            
            $text = "👥 <b>So'nggi Foydalanuvchilar</b>\n\n";
            foreach ($users as $u) {
                $text .= "• " . htmlspecialchars($u['fullname']) . "\n";
                $text .= "  📱 " . ($u['phone'] ?: 'Telefon yo\'q') . "\n";
                $text .= "  📅 " . date('d.m.Y H:i', strtotime($u['registered_at'])) . "\n\n";
            }
            
            answerCallback($callback_id, "👥 Foydalanuvchilar");
            editMessage($callback_chat_id, $callback_message_id, $text);
        }
        elseif ($callback_data == 'add_admin') {
            answerCallback($callback_id, "➕ Admin qo'shish");
            editMessage($callback_chat_id, $callback_message_id,
                "➕ <b>Admin Qo'shish</b>\n\n" .
                "Yangi admin qo'shish uchun quyidagi formatda yuboring:\n\n" .
                "<code>/addadmin [telegram_id] [username] [fullname]</code>\n\n" .
                "<b>Misol:</b>\n" .
                "<code>/addadmin 123456789 admin2 Admin Ismi</code>\n\n" .
                "Yoki web admin paneldan qo'shing.");
        }
        elseif ($callback_data == 'taxi_stats') {
            $onlineDrivers = $db->query("SELECT COUNT(*) FROM taxi_drivers WHERE is_online = 1")->fetchColumn();
            $busyDrivers = $db->query("SELECT COUNT(*) FROM taxi_drivers WHERE is_busy = 1")->fetchColumn();
            $pendingOrders = $db->query("SELECT COUNT(*) FROM taxi_orders WHERE status = 'pending'")->fetchColumn();
            $completedToday = $db->query("SELECT COUNT(*) FROM taxi_orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn();
            
            answerCallback($callback_id, "🚕 Taxi statistika");
            editMessage($callback_chat_id, $callback_message_id,
                "🚕 <b>Taxi Tizimi</b>\n\n" .
                "🟢 Online haydovchilar: " . $onlineDrivers . "\n" .
                "🔴 Band haydovchilar: " . $busyDrivers . "\n" .
                "⏳ Kutilayotgan buyurtmalar: " . $pendingOrders . "\n" .
                "✅ Bugun yakunlangan: " . $completedToday);
        }
        elseif ($callback_data == 'help_admin') {
            answerCallback($callback_id, "📝 Yordam");
            editMessage($callback_chat_id, $callback_message_id,
                "📝 <b>Admin Komandalar</b>\n\n" .
                "/start - Admin panel\n" .
                "/send [target] [text] - Habar yuborish\n" .
                "/addadmin [id] [user] [name] - Admin qo'shish\n" .
                "/stats - Statistika\n" .
                "/broadcast - Broadcast yo'riqnoma\n\n" .
                "<b>Media yuborish:</b>\n" .
                "Rasm/video yuboring va caption qismiga:\n" .
                "<code>/send all\nXabar matni</code>\n\n" .
                "<b>Forward qilish:</b>\n" .
                "Xabarni botga forward qiling");
        }
        elseif (strpos($callback_data, 'broadcast_target_') === 0) {
            $target = str_replace('broadcast_target_', '', $callback_data);
            
            // Get saved message
            $stmt = $db->prepare("SELECT broadcast_message, broadcast_media_type, broadcast_media_id FROM admins WHERE telegram_id = ?");
            $stmt->execute([$callback_chat_id]);
            $adminData = $stmt->fetch();
            
            if (!$adminData || (!$adminData['broadcast_message'] && !$adminData['broadcast_media_id'])) {
                answerCallback($callback_id, "❌ Xabar topilmadi");
                return;
            }
            
            answerCallback($callback_id, "⏳ Yuborilmoqda...");
            deleteMessage($callback_chat_id, $callback_message_id);
            
            sendMessage($callback_chat_id, "⏳ <b>Habar yuborilmoqda...</b>\n\nIltimos kuting...");
            
            // Broadcast message
            $result = broadcastMessage(
                $target, 
                $adminData['broadcast_message'], 
                $adminData['broadcast_media_type'], 
                $adminData['broadcast_media_id'], 
                $admin['username']
            );
            
            // Clear broadcast mode
            $stmt = $db->prepare("UPDATE admins SET broadcast_mode = 0, broadcast_message = NULL, broadcast_media_type = NULL, broadcast_media_id = NULL WHERE telegram_id = ?");
            $stmt->execute([$callback_chat_id]);
            
            // Send result
            $targetLabels = [
                'all' => 'Barchaga',
                'users' => 'Foydalanuvchilarga',
                'groups' => 'Guruhlarga',
                'channels' => 'Kanallarga'
            ];
            
            sendMessage($callback_chat_id, 
                "✅ <b>Habar yuborildi!</b>\n\n" .
                "📤 Kimga: " . $targetLabels[$target] . "\n" .
                "✓ Yuborildi: {$result['sent']}\n" .
                "✗ Xatolik: {$result['failed']}\n" .
                "📊 Jami: {$result['total']}");
        }
        elseif ($callback_data == 'broadcast_cancel') {
            answerCallback($callback_id, "❌ Bekor qilindi");
            
            // Clear broadcast mode
            $stmt = $db->prepare("UPDATE admins SET broadcast_mode = 0, broadcast_message = NULL, broadcast_media_type = NULL, broadcast_media_id = NULL WHERE telegram_id = ?");
            $stmt->execute([$callback_chat_id]);
            
            deleteMessage($callback_chat_id, $callback_message_id);
            sendMessage($callback_chat_id, "❌ Habar yuborish bekor qilindi.");
        }
    }
    
    if ($admin && strpos($callback_data, 'forward_all_') === 0) {
        $message_id = str_replace('forward_all_', '', $callback_data);
        
        answerCallback($callback_id, "⏳ Forward qilinmoqda...");
        
        // Get all chats
        $query = "SELECT chat_id FROM chats UNION SELECT telegram_id as chat_id FROM users WHERE telegram_id IS NOT NULL";
        $stmt = $db->query($query);
        $chats = $stmt->fetchAll();
        
        $sent = 0;
        $failed = 0;
        
        foreach ($chats as $chat) {
            $result = forwardMessage($chat['chat_id'], $callback_chat_id, $message_id);
            if ($result) {
                $sent++;
            } else {
                $failed++;
            }
            usleep(50000); // 50ms delay
        }
        
        editMessage($callback_chat_id, $callback_message_id, 
            "✅ <b>Forward qilindi!</b>\n\n" .
            "📤 Yuborildi: $sent\n" .
            "❌ Xatolik: $failed\n" .
            "📊 Jami: " . ($sent + $failed));
    } elseif ($callback_data == 'cancel') {
        answerCallback($callback_id, "❌ Bekor qilindi");
        deleteMessage($callback_chat_id, $callback_message_id);
    }
}

function broadcastMessage($target, $messageText, $mediaType, $mediaFileId, $sentBy) {
    global $db;
    
    // Get target chats
    $query = "SELECT chat_id, chat_type FROM chats";
    if ($target === 'users') {
        $query .= " WHERE chat_type = 'private'";
    } elseif ($target === 'groups') {
        $query .= " WHERE chat_type IN ('group', 'supergroup')";
    } elseif ($target === 'channels') {
        $query .= " WHERE chat_type = 'channel'";
    }
    
    $chatIds = [];
    $stmt = $db->query($query);
    while ($row = $stmt->fetch()) {
        $chatIds[$row['chat_id']] = true;
    }
    
    // Add users from users table
    if ($target === 'all' || $target === 'users') {
        $stmt2 = $db->query("SELECT telegram_id FROM users WHERE telegram_id IS NOT NULL");
        while ($row = $stmt2->fetch()) {
            $chatIds[$row['telegram_id']] = true;
        }
    }
    
    $totalSent = 0;
    $totalFailed = 0;
    
    foreach ($chatIds as $chatId => $v) {
        $result = false;
        
        if ($mediaType === 'photo' && $mediaFileId) {
            $result = sendPhoto($chatId, $mediaFileId, $messageText);
        } elseif ($mediaType === 'video' && $mediaFileId) {
            $result = sendVideo($chatId, $mediaFileId, $messageText);
        } elseif ($mediaType === 'document' && $mediaFileId) {
            $result = sendDocument($chatId, $mediaFileId, $messageText);
        } else {
            $result = sendMessage($chatId, $messageText);
        }
        
        if ($result) {
            $totalSent++;
        } else {
            $totalFailed++;
        }
        
        usleep(50000); // 50ms
    }
    
    // Save to history
    $stmt = $db->prepare("INSERT INTO broadcast_history (message_text, media_type, media_file, target, total_sent, total_failed, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$messageText, $mediaType, $mediaFileId ?: '', $target, $totalSent, $totalFailed, $sentBy]);
    
    return [
        'sent' => $totalSent,
        'failed' => $totalFailed,
        'total' => $totalSent + $totalFailed
    ];
}

function sendPhoto($chat_id, $fileId, $caption = '') {
    $data = [
        'chat_id' => $chat_id,
        'photo' => $fileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendPhoto');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

function sendVideo($chat_id, $fileId, $caption = '') {
    $data = [
        'chat_id' => $chat_id,
        'video' => $fileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendVideo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

function sendDocument($chat_id, $fileId, $caption = '') {
    $data = [
        'chat_id' => $chat_id,
        'document' => $fileId,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'sendDocument');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

function forwardMessage($chat_id, $from_chat_id, $message_id) {
    $data = [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ];
    
    $ch = curl_init(API_URL . 'forwardMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

function answerCallback($callback_id, $text) {
    $data = [
        'callback_query_id' => $callback_id,
        'text' => $text
    ];
    
    $ch = curl_init(API_URL . 'answerCallbackQuery');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_exec($ch);
    curl_close($ch);
}

function editMessage($chat_id, $message_id, $text) {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init(API_URL . 'editMessageText');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_exec($ch);
    curl_close($ch);
}

function deleteMessage($chat_id, $message_id) {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];
    
    $ch = curl_init(API_URL . 'deleteMessage');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_exec($ch);
    curl_close($ch);
}
