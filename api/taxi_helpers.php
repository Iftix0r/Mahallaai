<?php
// Taxi notification helpers

function notifyDriver($driver_telegram_id, $order_id, $from, $to, $price) {
    if (!$driver_telegram_id) return;
    
    $message = "🔔 <b>Yangi buyurtma!</b>\n\n";
    $message .= "📍 Qayerdan: $from\n";
    $message .= "🎯 Qayerga: $to\n";
    $message .= "💰 Narx: " . number_format($price, 0) . " so'm\n\n";
    $message .= "Buyurtmani haydovchi panelidan qabul qiling!";
    
    sendTelegramMessage($driver_telegram_id, $message, [
        'inline_keyboard' => [
            [['text' => "🚕 Haydovchi paneli", 'web_app' => ['url' => WEBAPP_URL . '/driver.html']]]
        ]
    ]);
}

function notifyCustomer($customer_telegram_id, $driver_name, $car_model, $car_number) {
    if (!$customer_telegram_id) return;
    
    $message = "✅ <b>Haydovchi tayinlandi!</b>\n\n";
    $message .= "👤 Haydovchi: $driver_name\n";
    $message .= "🚗 Mashina: $car_model\n";
    $message .= "🔢 Raqam: $car_number\n\n";
    $message .= "Haydovchi yo'lda! 3-5 daqiqada yetib keladi.";
    
    sendTelegramMessage($customer_telegram_id, $message);
}

function sendTelegramMessage($chat_id, $text, $reply_markup = null) {
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
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
