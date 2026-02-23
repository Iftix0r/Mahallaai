# 👨‍💼 Admin Bot Setup - Qo'llanma

## O'rnatish Bosqichlari

### 1. Admin Telegram ID O'rnatish

**URL:** `https://mahallaai.bigsaver.ru/admin/set_admin_telegram.php`

Bu sahifa avtomatik ravishda sizning Telegram ID'ingizni (2114098498) o'rnatadi.

### 2. Botda /start Buyrug'ini Yuborish

Telegram botingizga o'ting va `/start` buyrug'ini yuboring.

Sizga quyidagi admin panel ko'rinadi:

```
👨‍💼 Admin Panel

Xush kelibsiz, [Ismingiz]!

Quyidagi komandalardan foydalanishingiz mumkin:

[🌐 Web Admin Panel]
[📢 Habar Yuborish]
[📊 Statistika]
[👥 Foydalanuvchilar]
[➕ Admin Qo'shish]
[🚕 Taxi Tizimi]
[📝 Yordam]
```

## Admin Panel Tugmalari

### 🌐 Web Admin Panel
Web admin panelni ochadi (to'liq funksional)

### 📢 Habar Yuborish
Broadcast qilish bo'yicha yo'riqnoma ko'rsatadi

### 📊 Statistika
```
📊 Tizim Statistikasi

👥 Foydalanuvchilar: 150
👨‍👩‍👧‍👦 Guruhlar: 10
📢 Kanallar: 5
🚕 Haydovchilar: 8
📦 Buyurtmalar: 45

📈 Jami: 165
```

### 👥 Foydalanuvchilar
So'nggi 10 ta foydalanuvchini ko'rsatadi:
```
👥 So'nggi Foydalanuvchilar

• Ism Familiya
  📱 +998901234567
  📅 23.02.2026 14:30

• ...
```

### ➕ Admin Qo'shish
Yangi admin qo'shish yo'riqnomasini ko'rsatadi

### 🚕 Taxi Tizimi
```
🚕 Taxi Tizimi

🟢 Online haydovchilar: 5
🔴 Band haydovchilar: 2
⏳ Kutilayotgan buyurtmalar: 3
✅ Bugun yakunlangan: 12
```

### 📝 Yordam
Barcha admin komandalarni ko'rsatadi

## Yangi Admin Qo'shish

### Botda Komanda Yuborish

```
/addadmin [telegram_id] [username] [fullname]
```

**Misol:**
```
/addadmin 123456789 admin2 Admin Ismi
```

**Natija:**
```
✅ Admin qo'shildi!

👤 Ism: Admin Ismi
🆔 Telegram ID: 123456789
👨‍💼 Username: admin2
🔑 Parol: admin123

Admin /start buyrug'ini yuborishi mumkin.
```

Yangi admin ham botda `/start` buyrug'ini yuborishi mumkin va admin panel oladi.

## Yangi Foydalanuvchi Xabarnomasi

Har safar yangi foydalanuvchi ro'yxatdan o'tganda, barcha adminlarga xabar keladi:

```
🆕 Yangi foydalanuvchi!

👤 Ism: Ism Familiya
🆔 Telegram ID: 987654321
📅 Vaqt: 23.02.2026 14:30

Jami foydalanuvchilar: 151

[👥 Foydalanuvchilar] [📊 Statistika]
```

## Admin Komandalar

### /start
Admin panelni ochadi

### /broadcast
Broadcast yo'riqnomasini ko'rsatadi

### /send [target] [message]
Habar yuborish
```
/send all
Yangi xizmatlar haqida e'lon!
```

### /addadmin [id] [user] [name]
Yangi admin qo'shish
```
/addadmin 123456789 admin2 Admin Ismi
```

### /stats
Statistikani ko'rsatadi (tugma orqali ham mumkin)

## Broadcast Qilish

### Oddiy Matn
```
/send all
Assalomu alaykum! Yangi xizmatlar ishga tushdi.
```

### Media + Matn
1. Rasm/video yuboring
2. Caption qismiga:
```
/send users
Bu yangi mahsulotimiz!
```

### Forward Qilish
1. Xabarni botga forward qiling
2. "Ha, barchaga" tugmasini bosing

## Xavfsizlik

✅ Faqat `telegram_id` o'rnatilgan adminlar panelga kiradi
✅ `is_active = 1` bo'lishi kerak
✅ Har bir admin harakati database'ga yoziladi
✅ Yangi admin qo'shilganda xabar keladi

## Tekshirish

### 1. Admin ID To'g'ri O'rnatilganini Tekshirish
```sql
SELECT * FROM admins WHERE telegram_id = 2114098498;
```

### 2. Botda Test Qilish
```
/start
```
Admin panel ko'rinishi kerak.

### 3. Yangi Foydalanuvchi Qo'shish
Boshqa akkauntdan botga `/start` yuboring.
Sizga xabar kelishi kerak.

## Muammolarni Hal Qilish

### Muammo: Admin panel ko'rinmayapti
**Yechim:**
1. `set_admin_telegram.php` sahifasini oching
2. Telegram ID to'g'ri o'rnatilganini tekshiring
3. Botda `/start` qayta yuboring

### Muammo: Yangi foydalanuvchi xabari kelmayapti
**Yechim:**
1. Admin `is_active = 1` ekanligini tekshiring
2. `telegram_id` to'g'ri ekanligini tekshiring
3. Bot webhook ishlayotganini tekshiring

### Muammo: Admin qo'sha olmayapman
**Yechim:**
1. Format to'g'ri ekanligini tekshiring
2. Telegram ID to'g'ri ekanligini tekshiring
3. Username unique bo'lishi kerak

## Database Struktura

### admins jadvali
```sql
CREATE TABLE admins (
    id INT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    telegram_id BIGINT NULL,
    role VARCHAR(20) DEFAULT 'admin',
    fullname VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Qo'shimcha Imkoniyatlar

### Kelajakda qo'shilishi mumkin:
- [ ] Admin permissions (ruxsatlar)
- [ ] Admin activity log
- [ ] Multi-language support
- [ ] Admin notifications settings
- [ ] Scheduled messages
- [ ] Analytics dashboard

## Yordam

Agar muammo bo'lsa:
1. `set_admin_telegram.php` - ID tekshirish
2. Database - `admins` jadvalini tekshirish
3. Bot logs - Xatoliklarni ko'rish
4. Webhook - Ishlayotganini tekshirish

## Test Ssenariy

1. ✅ Admin ID o'rnatish
2. ✅ Botda /start yuborish
3. ✅ Admin panel ko'rinishi
4. ✅ Statistika tugmasini bosish
5. ✅ Yangi admin qo'shish
6. ✅ Yangi foydalanuvchi ro'yxatdan o'tishi
7. ✅ Adminga xabar kelishi
8. ✅ Broadcast yuborish
9. ✅ Forward qilish

## Xulosa

✅ Admin panel bot orqali ishlaydi
✅ Yangi foydalanuvchi xabarnomasi
✅ Admin qo'shish imkoniyati
✅ To'liq statistika
✅ Broadcast va forward
✅ Xavfsiz va ishonchli
