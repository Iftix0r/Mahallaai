# 🚗 Mahalla Avto - To'liq Qo'llanma

## Xususiyatlar

✅ **Avtosalonlar** - Biznes ro'yxatdan o'tish
✅ **Shaxsiy E'lonlar** - Har kim mashina sotishi mumkin
✅ **Filtrlar** - Brend, narx, yil bo'yicha qidirish
✅ **Sevimlilar** - Yoqgan mashinalarni saqlash
✅ **Ko'rishlar** - Har bir e'lon ko'rilgan soni
✅ **Rasm Galereyasi** - Ko'p rasmlar yuklash
✅ **Admin Panel** - Barcha e'lonlarni boshqarish

## Database Struktura

### auto_salons (Avtosalonlar)
```sql
- id
- owner_id (egasi)
- name (nomi)
- description (tavsif)
- logo (logotip)
- address (manzil)
- phone (telefon)
- working_hours (ish vaqti)
- is_active (faol/nofaol)
- rating (reyting)
- total_cars (mashinalar soni)
- created_at
```

### cars (Mashinalar)
```sql
- id
- salon_id (avtosalon ID, NULL = shaxsiy)
- seller_id (sotuvchi ID)
- listing_type (salon/private)
- brand (brend: Toyota, BMW, ...)
- model (model: Camry, X5, ...)
- year (yil)
- price (narx)
- mileage (yurgan masofa)
- fuel_type (yoqilg'i: Benzin, Dizel, ...)
- transmission (uzatma: Avtomat, Mexanik)
- color (rang)
- body_type (kuzov: Sedan, SUV, ...)
- engine_volume (dvigatel hajmi)
- description (tavsif)
- condition_type (new/used)
- location (joylashuv)
- phone (telefon)
- images (rasmlar, vergul bilan)
- is_sold (sotilgan/sotilmagan)
- views (ko'rishlar soni)
- created_at
- updated_at
```

### car_favorites (Sevimlilar)
```sql
- id
- user_id
- car_id
- created_at
```

## API Endpoints

### GET Requests

#### 1. Avtosalonlarni Olish
```
GET /api/avto.php?action=get_salons
```
**Response:**
```json
[
  {
    "id": 1,
    "name": "Premium Auto",
    "total_cars": 25,
    "rating": 4.8,
    "owner_name": "Ism Familiya"
  }
]
```

#### 2. Salon Tafsilotlari
```
GET /api/avto.php?action=get_salon&salon_id=1
```

#### 3. Salon Mashinalari
```
GET /api/avto.php?action=get_salon_cars&salon_id=1
```

#### 4. Barcha Mashinalar (Filtrlar bilan)
```
GET /api/avto.php?action=get_cars&listing_type=salon&brand=Toyota&min_price=10000&max_price=50000&year_from=2015&year_to=2024
```

#### 5. Mashina Tafsilotlari
```
GET /api/avto.php?action=get_car&car_id=1
```

#### 6. Mening Mashinalarim
```
GET /api/avto.php?action=get_my_cars&user_id=1
```

#### 7. Mening Salonim
```
GET /api/avto.php?action=get_my_salon&user_id=1
```

#### 8. Sevimlilar
```
GET /api/avto.php?action=get_favorites&user_id=1
```

#### 9. Brendlar Ro'yxati
```
GET /api/avto.php?action=get_brands
```

### POST Requests

#### 1. Avtosalon Yaratish
```javascript
POST /api/avto.php?action=create_salon
{
  "owner_id": 1,
  "name": "Premium Auto",
  "description": "Eng yaxshi mashinalar",
  "address": "Toshkent, Chilonzor",
  "phone": "+998901234567",
  "working_hours": "9:00 - 18:00"
}
```

#### 2. Mashina Qo'shish
```javascript
POST /api/avto.php?action=add_car
{
  "seller_id": 1,
  "salon_id": null, // null = shaxsiy
  "brand": "Toyota",
  "model": "Camry",
  "year": 2020,
  "price": 25000,
  "mileage": 50000,
  "fuel_type": "Benzin",
  "transmission": "Avtomat",
  "color": "Oq",
  "body_type": "Sedan",
  "engine_volume": 2.5,
  "description": "Ideal holatda",
  "condition_type": "used",
  "location": "Toshkent",
  "phone": "+998901234567",
  "images": "url1.jpg,url2.jpg,url3.jpg"
}
```

#### 3. Sevimlilar (Toggle)
```javascript
POST /api/avto.php?action=toggle_favorite
{
  "user_id": 1,
  "car_id": 5
}
```

#### 4. Sotilgan Deb Belgilash
```javascript
POST /api/avto.php?action=mark_sold
{
  "car_id": 5,
  "seller_id": 1
}
```

#### 5. Mashina O'chirish
```javascript
POST /api/avto.php?action=delete_car
{
  "car_id": 5,
  "seller_id": 1
}
```

## Frontend Integratsiya

### HTML Struktura
```html
<!-- Avtosalonlar -->
<div id="salons-container"></div>

<!-- Mashinalar -->
<div id="cars-container" class="cars-grid"></div>

<!-- Filtrlar -->
<select id="filter-type">
  <option value="">Barchasi</option>
  <option value="salon">Avtosalon</option>
  <option value="private">Shaxsiy</option>
</select>

<select id="filter-brand">
  <option value="">Barcha brendlar</option>
  <option value="Toyota">Toyota</option>
  <option value="BMW">BMW</option>
</select>

<input type="number" id="filter-min-price" placeholder="Min narx">
<input type="number" id="filter-max-price" placeholder="Max narx">

<button onclick="applyFilters()">Qidirish</button>
```

### JavaScript Ishlatish
```javascript
// Avtosalonlarni yuklash
loadSalons();

// Mashinalarni yuklash
loadCars();

// Filtrlar bilan qidirish
applyFilters();

// Mashina ko'rish
viewCar(carId);

// Sevimlilar
toggleFavorite(carId);

// Mashina qo'shish
addCar({
  brand: 'Toyota',
  model: 'Camry',
  year: 2020,
  price: 25000,
  // ...
});

// Avtosalon yaratish
createSalon({
  name: 'Premium Auto',
  description: 'Eng yaxshi mashinalar',
  // ...
});
```

## Biznes Ro'yxatdan O'tish

### Bosqich 1: Avtosalon Yaratish
1. Mahalla Biznes bo'limiga o'ting
2. "Avtosalon" kategoriyasini tanlang
3. Ma'lumotlarni kiriting:
   - Salon nomi
   - Tavsif
   - Manzil
   - Telefon
   - Ish vaqti
4. "Yaratish" tugmasini bosing

### Bosqich 2: Mashinalar Qo'shish
1. Salon paneliga o'ting
2. "Mashina Qo'shish" tugmasini bosing
3. Ma'lumotlarni kiriting:
   - Brend va model
   - Yil
   - Narx
   - Yurgan masofa
   - Texnik xususiyatlar
   - Rasmlar
4. "E'lon Berish" tugmasini bosing

## Shaxsiy E'lon Berish

### Oddiy Foydalanuvchi
1. Mahalla Avto bo'limiga o'ting
2. "E'lon Berish" tugmasini bosing
3. Ma'lumotlarni kiriting
4. Rasmlar yuklang
5. "E'lon Berish" tugmasini bosing

## Admin Panel

### URL
```
https://mahallaai.bigsaver.ru/admin/avto.php
```

### Statistika
- Avtosalonlar soni
- Faol e'lonlar
- Shaxsiy e'lonlar
- Sotilgan mashinalar

### Boshqarish
- Barcha avtosalonlarni ko'rish
- Barcha e'lonlarni ko'rish
- E'lonlarni o'chirish
- Statistika

## Xususiyatlar

### Filtrlar
- Turi (Avtosalon/Shaxsiy)
- Brend
- Narx oralig'i
- Yil oralig'i
- Yoqilg'i turi
- Uzatma turi

### Sevimlilar
- Yoqgan mashinalarni saqlash
- Sevimlilar ro'yxatini ko'rish
- Bir tugma bilan qo'shish/o'chirish

### Ko'rishlar
- Har bir e'lon ko'rilgan soni
- Avtomatik hisoblanadi

### Rasm Galereyasi
- Ko'p rasmlar yuklash
- Vergul bilan ajratilgan URL'lar
- Birinchi rasm asosiy

## Misol: To'liq E'lon

```javascript
const car = {
  // Asosiy ma'lumotlar
  brand: "Toyota",
  model: "Camry",
  year: 2020,
  price: 25000,
  
  // Texnik xususiyatlar
  mileage: 50000,
  fuel_type: "Benzin",
  transmission: "Avtomat",
  color: "Oq",
  body_type: "Sedan",
  engine_volume: 2.5,
  condition_type: "used",
  
  // Aloqa
  location: "Toshkent, Chilonzor",
  phone: "+998901234567",
  
  // Tavsif
  description: "Ideal holatda, birinchi egasidan, to'liq xizmat tarixi, kafolat bilan",
  
  // Rasmlar
  images: "https://example.com/1.jpg,https://example.com/2.jpg,https://example.com/3.jpg",
  
  // Sotuvchi
  seller_id: 1,
  salon_id: null // null = shaxsiy
};

addCar(car);
```

## Test Qilish

### 1. Avtosalon Yaratish
```bash
curl -X POST https://mahallaai.bigsaver.ru/api/avto.php?action=create_salon \
  -H "Content-Type: application/json" \
  -d '{
    "owner_id": 1,
    "name": "Test Auto",
    "phone": "+998901234567"
  }'
```

### 2. Mashina Qo'shish
```bash
curl -X POST https://mahallaai.bigsaver.ru/api/avto.php?action=add_car \
  -H "Content-Type: application/json" \
  -d '{
    "seller_id": 1,
    "brand": "Toyota",
    "model": "Camry",
    "year": 2020,
    "price": 25000
  }'
```

### 3. Mashinalarni Olish
```bash
curl https://mahallaai.bigsaver.ru/api/avto.php?action=get_cars
```

## Kelajak Rejalar

- [ ] Kredit kalkulyatori
- [ ] Mashina taqqoslash
- [ ] Video qo'llab-quvvatlash
- [ ] Chat tizimi
- [ ] Push bildirishnomalar
- [ ] Geolokatsiya xaritada
- [ ] VIN checker
- [ ] Narx tarixi

## Xulosa

✅ To'liq avtosalon tizimi
✅ Shaxsiy e'lonlar
✅ Filtrlar va qidiruv
✅ Sevimlilar
✅ Admin panel
✅ Biznes ro'yxatdan o'tish
✅ Ko'rishlar statistikasi
✅ Rasm galereyasi

Mahalla Avto tayyor! 🚗
