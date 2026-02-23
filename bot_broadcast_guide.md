# 🤖 Bot Orqali Broadcast - Qo'llanma

## Xususiyatlar

✅ **Telegram bot orqali habar yuborish**
✅ **Media fayllar bilan ishlash** (rasm, video, hujjat)
✅ **Forward qilish** (xabarni barchaga forward)
✅ **Admin komandalar**
✅ **Statistika ko'rish**
✅ **File ID reuse** (tez yuborish)

## Admin Komandalar

### 1. /start
Admin uchun qo'shimcha tugma ko'rsatiladi:
```
⚙️ Admin Panel - Web admin panelga o'tish
```

### 2. /broadcast
Broadcast qilish bo'yicha yo'riqnoma:
```
📢 Habar Yuborish

Habar yuborish uchun quyidagi formatda yuboring:

/send [all|users|groups|channels]
Xabar matni

Yoki media fayl bilan birga caption yozing.

Misol:
/send all
Yangi xizmatlar haqida e'lon!
```

### 3. /send
Habar yuborish komandasi:

#### Format:
```
/send [target] [message]
```

#### Target turlari:
- `all` - Barchaga (users + groups + channels)
- `users` - Faqat foydalanuvchilarga
- `groups` - Faqat guruhlarga
- `channels` - Faqat kanallarga

#### Misollar:

**Oddiy matn:**
```
/send all
Assalomu alaykum! Yangi xizmatlar ishga tushdi.
```

**Ko'p qatorli matn:**
```
/send users
Hurmatli foydalanuvchilar!

Sizga yangi xizmatlarni taqdim etamiz:
🚕 Mahalla Taxi
🍔 Mahalla Ovqatlar
🛒 Mahalla Market
```

**HTML format:**
```
/send all
<b>Muhim e'lon!</b>

<i>Tizim texnik ishlar tufayli</i>
<u>23.02.2026 kuni</u> ishlamaydi.

<a href="https://example.com">Batafsil</a>
```

### 4. Media Yuborish

#### Rasm yuborish:
1. Rasmni botga yuboring
2. Caption qismiga yozing:
```
/send all
Bu yangi mahsulotimiz!
```

#### Video yuborish:
1. Videoni botga yuboring
2. Caption qismiga yozing:
```
/send users
Yangi video dars!
```

#### Hujjat yuborish:
1. Faylni botga yuboring
2. Caption qismiga yozing:
```
/send groups
Yangi qo'llanma PDF formatda
```

### 5. Forward Qilish

Har qanday xabarni forward qilish:

1. Xabarni botga forward qiling
2. Bot so'raydi:
```
📨 Forward Qilish

Bu xabarni barchaga forward qilishni xohlaysizmi?

[✅ Ha, barchaga] [❌ Yo'q]
```
3. "Ha, barchaga" tugmasini bosing
4. Bot barchaga forward qiladi

### 6. /stats
Statistika ko'rish:
```
📊 Statistika

👥 Foydalanuvchilar: 150
👨‍👩‍👧‍👦 Guruhlar: 10
📢 Kanallar: 5
📈 Jami: 165
```

## Ishlash Jarayoni

### Oddiy Matn Yuborish
```
1. Admin botga /send all yozadi
   ↓
2. Xabar matnini yozadi
   ↓
3. Bot "⏳ Habar yuborilmoqda..." deb javob beradi
   ↓
4. Bot barchaga yuboradi
   ↓
5. Bot natijani ko'rsatadi:
   "✅ Habar yuborildi!
    📤 Yuborildi: 148
    ❌ Xatolik: 2
    📊 Jami: 150"
```

### Media Yuborish
```
1. Admin botga rasm/video yuboradi
   ↓
2. Caption qismiga /send all va matn yozadi
   ↓
3. Bot birinchi foydalanuvchiga yuboradi va file_id oladi
   ↓
4. Bot qolgan foydalanuvchilarga file_id orqali yuboradi (tez!)
   ↓
5. Bot natijani ko'rsatadi
```

### Forward Qilish
```
1. Admin xabarni botga forward qiladi
   ↓
2. Bot tasdiqlash so'raydi
   ↓
3. Admin "Ha, barchaga" tugmasini bosadi
   ↓
4. Bot barchaga forward qiladi
   ↓
5. Bot natijani ko'rsatadi
```

## Admin Tekshiruvi

Bot faqat admin foydalanuvchilarga javob beradi. Admin tekshiruvi:

```php
$stmt = $db->prepare("SELECT * FROM admins WHERE telegram_id = ? AND is_active = 1");
$stmt->execute([$chat_id]);
$admin = $stmt->fetch();

if ($admin) {
    // Admin komandalarini bajarish
}
```

## Admin Qo'shish

Admin panelda yoki database orqali:

```sql
-- Telegram ID orqali admin qo'shish
UPDATE admins SET telegram_id = 123456789 WHERE username = 'admin';

-- Yoki yangi admin yaratish
INSERT INTO admins (username, password, telegram_id, role, fullname) 
VALUES ('admin2', '$2y$10$...', 123456789, 'admin', 'Admin Ismi');
```

## Xavfsizlik

✅ Faqat adminlar broadcast qila oladi
✅ Telegram ID tekshiriladi
✅ is_active = 1 bo'lishi kerak
✅ Har bir xabar database'ga saqlanadi

## Performance

### File ID Reuse
- Birinchi yuborish: 10 soniya (fayl yuklash)
- Keyingi yuborishlar: 0.5 soniya (file_id)
- 100 foydalanuvchi: ~60 soniya

### Rate Limiting
- 50ms delay har bir xabar orasida
- Telegram rate limit: ~30 xabar/soniya
- Optimal tezlik: ~20 xabar/soniya

## Xatolik Boshqaruvi

### Agar foydalanuvchi botni bloklagan bo'lsa:
```
❌ Xatolik: 2
```
Bu normal, bot shunchaki o'tkazib yuboradi.

### Agar media yuborilmasa:
- File ID noto'g'ri bo'lishi mumkin
- Fayl hajmi juda katta (50MB limit)
- Telegram serveri javob bermayapti

## Misollar

### Misol 1: Oddiy E'lon
```
/send all
🎉 Yangi xizmat!

Mahalla Taxi endi ishga tushdi!
Tezkor va arzon taksi xizmati.

/start - Boshlash
```

### Misol 2: HTML Format
```
/send users
<b>⚠️ Muhim xabar!</b>

Tizim <u>bugun kechqurun</u> 
<i>texnik ishlar</i> tufayli 
2 soat ishlamaydi.

<a href="https://t.me/support">Yordam</a>
```

### Misol 3: Rasm + Matn
1. Rasmni yuboring
2. Caption:
```
/send all
🍔 Yangi taom!

Maxsus chegirma: 20%
Bugun faqat!
```

### Misol 4: Video + Matn
1. Videoni yuboring
2. Caption:
```
/send groups
📹 Yangi video qo'llanma

Tizimdan qanday foydalanish haqida
```

## Qo'shimcha Imkoniyatlar

### Kelajakda qo'shilishi mumkin:
- [ ] Scheduled broadcast (vaqt belgilash)
- [ ] Draft messages (qoralama)
- [ ] A/B testing
- [ ] Analytics (ko'rilganlar soni)
- [ ] Reply tracking
- [ ] Inline buttons

## Yordam

Agar muammo bo'lsa:
1. /stats - Statistikani tekshiring
2. Admin panel - Web orqali yuborish
3. Database - broadcast_history jadvalini tekshiring
4. Logs - Server loglarini ko'ring

## Test Qilish

1. O'zingizga test xabar yuboring:
```
/send users
Test xabar
```

2. Kichik guruhga yuboring
3. Keyin barchaga yuboring

## Xulosa

✅ Bot orqali tez va oson broadcast
✅ Media fayllar qo'llab-quvvatlanadi
✅ Forward qilish imkoniyati
✅ Admin komandalar
✅ File ID reuse (20x tezroq)
✅ Xavfsiz va ishonchli
