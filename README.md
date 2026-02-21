# 🏦 Mahalla AI - Raqamli Mahalla Platformasi

<div align="center">
  <img src="https://logobank.uz:8005/static/logos_png/Mahalla_vazirligi.png" width="150" alt="Mahalla AI Logo">
  <br />
  <p align="center">
    <strong>Zamonaviy, xavfsiz va qulay mahalla boshqaruv tizimi.</strong>
    <br />
    O'zbekiston fuqarolari uchun mo'ljallangan Telegram Mini App platformasi.
  </p>
  
  [![PHP Version](https://img.shields.io/badge/PHP-8.x-blue.svg)](https://www.php.net/)
  [![MySQL](https://img.shields.io/badge/Database-MySQL-blue.svg)](https://www.mysql.com/)
  [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
  [![Telegram](https://img.shields.io/badge/Telegram-Mini%20App-blue.svg)](https://telegram.org/)
</div>

---

## ✨ Loyiha haqida

**Mahalla AI** — bu mahalla tizimini raqamlashtirish, fuqarolar va mahalla idorasi o'rtasidagi muloqotni osonlashtirish uchun yaratilgan zamonaviy platforma. Tizim orqali fuqarolar uyidan chiqmagan holda turli davlat xizmatlaridan foydalanishlari va sun'iy intellekt yordamchisidan maslahat olishlari mumkin.

## 🚀 Asosiy Imkoniyatlar

- 📱 **Telegram Mini App:** Ilovani o'rnatmasdan to'g'ridan-to'g'ri Telegram ichida foydalanish.
- 💬 **AI Assistant:** Fuqarolarning savollariga 24/7 rejimida javob beruvchi sun'iy intellekt.
- 📄 **Onlayn Arizalar:** Ma'lumotnoma va boshqa hujjatlar uchun masofadan ariza topshirish.
- 🔔 **Tezkor Xabarnomalar:** Mahalla yangiliklari va tadbirlari haqida doimiy xabardorlik.
- 🎨 **Premium UI/UX:** Glassmorphism uslubidagi ko'zni charchatmaydigan, premium dizayn.

## 🛠 Texnologik Stek

- **Backend:** Pure PHP (Slim & Fast)
- **Database:** MySQL (Kuchli va kengayuvchan)
- **Frontend:** HTML5, Modern CSS (Custom Properties), JavaScript (Vanilla)
- **API:** Telegram Bot API & Mini App Webview

## ⚙️ O'rnatish

1.  **Repozitoriyani klonlang:**
    ```bash
    git clone https://github.com/Iftix0r/Mahallaai.git
    cd Mahallaai
    ```

2.  **Konfiguratsiya:**
    `api/config.php` faylini oching va bot tokeningiz hamda MySQL ma'lumotlarini kiriting:
    ```php
    define('BOT_TOKEN', 'SIZNING_BOT_TOKENINGIZ');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'mahalla_db');
    define('DB_USER', 'root');
    define('DB_PASS', 'parol');
    ```

3.  **Webhook-ni sozlash:**
    Botni serveringizga bog'lang:
    `https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://domain.uz/bot.php`

## 🎨 Dizayn Konsepsiyasi

Loyiha dizayni O'zbekiston davlat tashkilotlari uchun tavsiya etilgan ranglar va zamonaviy "Glass" (shisha) effektlari asosida qurilgan. Bu foydalanuvchida ishonch va qulaylik hissini uyg'otadi.

---

<div align="center">
  Loyiha <b>Iftixor</b> tomonidan yaratildi. <br />
  © 2026 Mahalla AI. Barcha huquqlar himoyalanganmi.
</div>
