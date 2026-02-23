# 📢 Step-by-Step Broadcast - Qo'llanma

## Yangi Broadcast Tizimi

Endi broadcast qilish juda oson va tushunarli!

## Ishlash Jarayoni

### Bosqich 1: "Habar Yuborish" Tugmasini Bosish

Admin panelda `/start` buyrug'ini yuboring va "📢 Habar Yuborish" tugmasini bosing.

Bot javob beradi:
```
📢 Habar Yuborish

Iltimos, yubormoqchi bo'lgan xabar yoki media faylni yuboring.

• Oddiy matn
• Rasm + caption
• Video + caption
• Hujjat + caption

Xabarni yuborganingizdan keyin, kimga yuborishni tanlaysiz.
```

### Bosqich 2: Xabar yoki Media Yuborish

Endi xabaringizni yuboring:

#### Variant 1: Oddiy Matn
```
Assalomu alaykum!

Yangi xizmatlar ishga tushdi:
🚕 Mahalla Taxi
🍔 Mahalla Ovqatlar
🛒 Mahalla Market
```

#### Variant 2: Rasm + Caption
1. Rasmni yuboring
2. Caption qismiga matn yozing:
```
Bu yangi mahsulotimiz!
Maxsus chegirma: 20%
```

#### Variant 3: Video + Caption
1. Videoni yuboring
2. Caption qismiga matn yozing:
```
Yangi video qo'llanma
Tizimdan qanday foydalanish
```

#### Variant 4: Hujjat + Caption
1. PDF/DOC faylni yuboring
2. Caption qismiga matn yozing:
```
Yangi qo'llanma
Yuklab oling va o'qing
```

### Bosqich 3: Target Tanlash

Xabarni yuborganingizdan keyin, bot so'raydi:

```
✅ Xabar qabul qilindi!

Endi kimga yuborishni tanlang:

[🌐 Barchaga]
[👥 Foydalanuvchilarga]
[👨‍👩‍👧‍👦 Guruhlarga]
[📢 Kanallarga]
[❌ Bekor qilish]
```

Kerakli tugmani bosing.

### Bosqich 4: Yuborish

Bot yuborishni boshlaydi:
```
⏳ Habar yuborilmoqda...

Iltimos kuting...
```

### Bosqich 5: Natija

Yuborish tugagandan keyin:
```
✅ Habar yuborildi!

📤 Kimga: Barchaga
✓ Yuborildi: 148
✗ Xatolik: 2
📊 Jami: 150
```

## To'liq Misol

### Misol 1: Oddiy E'lon

1. Admin panelda "📢 Habar Yuborish" bosing
2. Matn yuboring:
```
🎉 Yangi xizmat!

Mahalla Taxi endi ishga tushdi!
Tezkor va arzon taksi xizmati.

/start - Boshlash
```
3. "🌐 Barchaga" tugmasini bosing
4. Kutib turing
5. Natijani ko'ring

### Misol 2: Rasm bilan E'lon

1. Admin panelda "📢 Habar Yuborish" bosing
2. Rasmni yuboring va caption yozing:
```
🍔 Yangi taom!

Maxsus chegirma: 20%
Bugun faqat!
```
3. "👥 Foydalanuvchilarga" tugmasini bosing
4. Kutib turing
5. Natijani ko'ring

### Misol 3: Video bilan Qo'llanma

1. Admin panelda "📢 Habar Yuborish" bosing
2. Videoni yuboring va caption yozing:
```
📹 Yangi video qo'llanma

Tizimdan qanday foydalanish haqida
Barcha xususiyatlar ko'rsatilgan
```
3. "👨‍👩‍👧‍👦 Guruhlarga" tugmasini bosing
4. Kutib turing
5. Natijani ko'ring

## Target Turlari

### 🌐 Barchaga
- Barcha foydalanuvchilar
- Barcha guruhlar
- Barcha kanallar

### 👥 Foydalanuvchilarga
- Faqat shaxsiy chatlar
- Botni ishlatgan foydalanuvchilar

### 👨‍👩‍👧‍👦 Guruhlarga
- Faqat guruhlar
- Bot qo'shilgan guruhlar

### 📢 Kanallarga
- Faqat kanallar
- Bot admin bo'lgan kanallar

## Bekor Qilish

Agar xato xabar yuborsangiz yoki fikringizni o'zgartirsangiz:

1. "❌ Bekor qilish" tugmasini bosing
2. Bot javob beradi:
```
❌ Habar yuborish bekor qilindi.
```

## Xususiyatlar

✅ **Oson va tushunarli** - 3 bosqichda
✅ **Vizual tugmalar** - Hech narsa yozish shart emas
✅ **Media qo'llab-quvvatlash** - Rasm, video, hujjat
✅ **Target tanlash** - Kimga yuborishni tanlang
✅ **Bekor qilish** - Istalgan vaqtda bekor qilish
✅ **Natija ko'rsatish** - Qancha yuborilgani
✅ **File ID reuse** - Tez yuborish

## Texnik Tafsilotlar

### Database Ustunlar
```sql
ALTER TABLE admins ADD COLUMN broadcast_mode TINYINT(1) DEFAULT 0;
ALTER TABLE admins ADD COLUMN broadcast_message TEXT NULL;
ALTER TABLE admins ADD COLUMN broadcast_media_type VARCHAR(20) NULL;
ALTER TABLE admins ADD COLUMN broadcast_media_id VARCHAR(255) NULL;
```

### Broadcast Mode
Admin "Habar Yuborish" tugmasini bosganda:
```php
broadcast_mode = 1
```

Xabar yuborilganda yoki bekor qilinganda:
```php
broadcast_mode = 0
broadcast_message = NULL
broadcast_media_type = NULL
broadcast_media_id = NULL
```

### Xabar Saqlash
```php
if ($admin['broadcast_mode'] == 1) {
    // Save message/media
    $stmt->execute([$messageText, $mediaType, $mediaFileId, $chat_id]);
    
    // Ask target
    sendMessage($chat_id, "Kimga yuborishni tanlang:", [...]);
}
```

### Target Tanlash
```php
if ($callback_data == 'broadcast_target_all') {
    broadcastMessage('all', $message, $mediaType, $mediaFileId, $username);
}
```

## Xavfsizlik

✅ Faqat adminlar broadcast qila oladi
✅ Xabar vaqtinchalik database'da saqlanadi
✅ Yuborilgandan keyin o'chiriladi
✅ Bekor qilish imkoniyati

## Performance

### Tezlik
- Oddiy matn: ~20 xabar/soniya
- Media (file_id): ~20 xabar/soniya
- 100 foydalanuvchi: ~5-10 soniya

### Optimizatsiya
- File ID reuse
- 50ms delay
- Batch processing

## Muammolarni Hal Qilish

### Muammo: "Xabar qabul qilindi" ko'rinmayapti
**Yechim:** 
- Broadcast mode yoqilganini tekshiring
- Qaytadan "Habar Yuborish" tugmasini bosing

### Muammo: Target tugmalari ko'rinmayapti
**Yechim:**
- Xabar to'g'ri yuborilganini tekshiring
- Database'da broadcast_message saqlanganini tekshiring

### Muammo: Yuborish juda sekin
**Yechim:**
- Bu normal, Telegram rate limit bor
- 100 foydalanuvchi ~5-10 soniya

## Kelajak Rejalar

- [ ] Preview (ko'rib chiqish)
- [ ] Scheduled broadcast (vaqt belgilash)
- [ ] Draft messages (qoralama)
- [ ] A/B testing
- [ ] Analytics

## Xulosa

✅ 3 bosqichda broadcast
✅ Oson va tushunarli
✅ Media qo'llab-quvvatlash
✅ Target tanlash
✅ Bekor qilish imkoniyati
✅ Tez va ishonchli

Endi broadcast qilish juda oson! 🎉
