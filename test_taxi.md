# 🚕 Mahalla Taxi - Test Qo'llanma

## Tizim Arxitekturasi

### Database Jadvallar

1. **taxi_drivers** - Haydovchilar ma'lumotlari
   - Mashina turi, raqami, modeli, rangi
   - Online/Offline holati
   - Joriy lokatsiya (lat/lng)
   - Reyting va umumiy safarlar soni

2. **taxi_orders** - Buyurtmalar
   - Yo'lovchi va haydovchi ID
   - Qayerdan/qayerga manzillar va koordinatalar
   - Narx va status (pending/assigned/accepted/completed/cancelled)

### API Endpoints

#### Haydovchi uchun:
- `POST /api/index.php?action=register_driver` - Haydovchi ro'yxatdan o'tish
- `POST /api/index.php?action=update_driver_location` - Lokatsiyani yangilash
- `GET /api/index.php?action=get_driver&user_id=X` - Haydovchi ma'lumotlari
- `GET /api/index.php?action=get_pending_orders&car_type=X` - Yangi buyurtmalar
- `GET /api/index.php?action=get_driver_orders&driver_id=X` - Faol buyurtmalar
- `POST /api/index.php?action=accept_order` - Buyurtmani qabul qilish
- `POST /api/index.php?action=complete_order` - Safarni yakunlash

#### Yo'lovchi uchun:
- `POST /api/index.php?action=order_taxi` - Taxi buyurtma qilish
- `GET /api/index.php?action=get_order_status&order_id=X` - Buyurtma holati
- `GET /api/index.php?action=get_customer_orders&customer_id=X` - Buyurtmalar tarixi
- `POST /api/index.php?action=cancel_order` - Buyurtmani bekor qilish

## Test Qilish Bosqichlari

### 1. Haydovchi Ro'yxatdan O'tish
```
URL: https://mahallaai.bigsaver.ru/register_driver.html
```
- Mashina turini tanlang (Ekonom/Komfort/Business)
- Mashina modelini kiriting
- Mashina raqamini kiriting
- Mashina rangini kiriting
- "Ro'yxatdan O'tish" tugmasini bosing

### 2. Haydovchi Paneli
```
URL: https://mahallaai.bigsaver.ru/driver.html
```
- Online/Offline rejimini yoqing
- Lokatsiya avtomatik yangilanadi (har 30 soniya)
- Yangi buyurtmalar real vaqtda ko'rinadi
- Buyurtmani qabul qiling
- Safarni yakunlang

### 3. Yo'lovchi Paneli
```
URL: https://mahallaai.bigsaver.ru/index.html?tab=taxi
```
- Qayerdan va qayerga manzillarini kiriting
- Mashina turini tanlang
- "Taxi Chaqirish" tugmasini bosing
- Tizim avtomatik eng yaqin haydovchini topadi
- Buyurtma statusini real vaqtda kuzating

## Real Vaqt Xususiyatlari

### Eng Yaqin Haydovchini Topish
Tizim Haversine formulasidan foydalanib, yo'lovchi lokatsiyasiga eng yaqin bo'sh haydovchini topadi:

```sql
SELECT id, current_lat, current_lng, 
(6371 * acos(cos(radians(?)) * cos(radians(current_lat)) * 
cos(radians(current_lng) - radians(?)) + sin(radians(?)) * 
sin(radians(current_lat)))) AS distance 
FROM taxi_drivers 
WHERE car_type = ? AND is_online = 1 AND is_busy = 0 
ORDER BY distance ASC LIMIT 1
```

### Avtomatik Yangilanishlar
- **Haydovchi lokatsiyasi:** Har 30 soniyada
- **Buyurtma statuslari:** Har 5 soniyada
- **Yangi buyurtmalar:** Har 5 soniyada

### Telegram Bildirishnomalar
- Haydovchiga yangi buyurtma kelganda
- Yo'lovchiga haydovchi tayinlanganda
- Safar yakunlanganda

## Status O'zgarishlari

```
pending → assigned → accepted → completed
                  ↓
              cancelled
```

- **pending:** Buyurtma yaratildi, haydovchi kutilmoqda
- **assigned:** Tizim avtomatik haydovchini tayinladi
- **accepted:** Haydovchi buyurtmani qabul qildi
- **completed:** Safar yakunlandi
- **cancelled:** Buyurtma bekor qilindi

## Xavfsizlik

- Barcha pul operatsiyalari transaction bilan amalga oshiriladi
- Buyurtma bekor qilinganda pul avtomatik qaytariladi
- Haydovchi band bo'lsa, boshqa buyurtma olmaydi
- Lokatsiya ma'lumotlari faqat online haydovchilar uchun saqlanadi

## Admin Panel

```
URL: https://mahallaai.bigsaver.ru/admin/taxi.php
```
- Barcha haydovchilar ro'yxati
- Buyurtmalar tarixi
- Statistika va hisobotlar
