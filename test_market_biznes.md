# Mahalla Market & Biznes - Test Guide

## ✅ Yangi Funksiyalar

### Backend API (api/index.php):

1. **get_products** - Kategoriya bo'yicha filtrlash qo'shildi
   - `?action=get_products&business_id=X` - Bitta biznes mahsulotlari
   - `?action=get_products&category=market` - Kategoriya bo'yicha
   
2. **get_business_stats** - Biznes statistikasi
   - Bugungi daromad
   - Bugungi buyurtmalar soni
   - Kutilayotgan buyurtmalar
   - Jami mahsulotlar

3. **update_order_status** - Buyurtma holatini yangilash
   - pending → preparing → ready → delivering → completed
   - cancelled (bekor qilish)

4. **delete_product** - Mahsulotni o'chirish

5. **toggle_product_availability** - Mahsulot mavjudligini o'zgartirish

6. **update_business_settings** - Biznes sozlamalarini yangilash
   - is_open (ochiq/yopiq)
   - delivery_price (yetkazib berish narxi)

### Frontend Yaxshilanishlar:

#### Mahalla Market (js/market.js):
- ✅ Kategoriya bo'yicha filtrlash
- ✅ Mahsulot emoji ko'rsatish
- ✅ Biznes nomi ko'rsatish
- ✅ Real vaqtda kategoriya filtri

#### Mahalla Biznes (js/biznes.js):
- ✅ Real vaqt statistika
- ✅ Buyurtma holati boshqaruvi (6 ta holat)
- ✅ Mahsulot boshqaruvi (qo'shish, o'chirish, mavjudlik)
- ✅ Biznes sozlamalari (ochiq/yopiq, yetkazib berish)
- ✅ Ranglar bilan holat ko'rsatish

## Test Qilish:

### 1. Biznes Yaratish:
```
1. Tizimga kiring
2. "O'z biznesingizni qo'shing" tugmasini bosing
3. Biznes nomini kiriting (masalan: "Oq-tepa Market")
4. Kategoriyani tanlang (Market)
5. "Biznesni Yaratish" tugmasini bosing
```

### 2. Mahsulot Qo'shish:
```
1. Biznes panelida "Mahsulotlar" tabiga o'ting
2. "Yangi mahsulot qo'shish" tugmasini bosing
3. Ma'lumotlarni kiriting:
   - Nomi: Olma
   - Narxi: 12000
   - Kategoriya: mevalar
   - Tavsif: Yangi kelgan
4. Saqlang
```

### 3. Buyurtma Qabul Qilish:
```
1. Mijoz Market'dan buyurtma beradi
2. Biznes panelida "Buyurtmalar" tabida ko'rinadi
3. Holat: "Yangi" (sariq rang)
4. "Qabul qilish" tugmasini bosing → "Tayyorlanmoqda" (ko'k)
5. "Tayyor" tugmasini bosing → "Tayyor" (yashil)
6. "Yetkazishga yuborish" → "Yetkazilmoqda" (binafsha)
7. "Yetkazildi" → "Yetkazildi" (kulrang)
```

### 4. Mahsulot Boshqarish:
```
1. "Mahsulotlar" tabida har bir mahsulot:
   - 👁️ tugma - Mavjud/Mavjud emas
   - 🗑️ tugma - O'chirish
2. Kategoriya ko'rsatiladi
3. Narx va tavsif ko'rinadi
```

### 5. Market'da Xarid Qilish:
```
1. Mahalla Market'ga kiring
2. Kategoriyalarni tanlang (Mevalar, Sabzavotlar, va h.k.)
3. Mahsulotlar filtrlangan holda ko'rinadi
4. Savatga qo'shing
5. "Buyurtma berish" tugmasini bosing
6. Balansdan yechiladi
```

## Buyurtma Holatlari:

| Holat | Rang | Tavsif |
|-------|------|--------|
| pending | 🟡 Sariq | Yangi buyurtma |
| preparing | 🔵 Ko'k | Tayyorlanmoqda |
| ready | 🟢 Yashil | Tayyor |
| delivering | 🟣 Binafsha | Yetkazilmoqda |
| completed | ⚪ Kulrang | Yetkazildi |
| cancelled | 🔴 Qizil | Bekor qilindi |

## Kategoriyalar:

- 🍎 Mevalar (mevalar)
- 🥦 Sabzavotlar (sabzavotlar)
- 🥛 Sut mahsulotlari (sut)
- 🥩 Go'sht (gosht)
- 🍞 Non (non)
- 🧃 Ichimliklar (ichimliklar)
- 🧽 Tozalik (tozalik)
- 📦 Boshqa (boshqa)

## API Endpoints:

### GET:
- `/api/index.php?action=get_businesses&category=market`
- `/api/index.php?action=get_products&business_id=X`
- `/api/index.php?action=get_business_orders&business_id=X`
- `/api/index.php?action=get_business_stats&business_id=X`

### POST:
- `action=create_business` - Biznes yaratish
- `action=add_product` - Mahsulot qo'shish
- `action=update_product` - Mahsulot tahrirlash
- `action=delete_product` - Mahsulot o'chirish
- `action=toggle_product_availability` - Mavjudlikni o'zgartirish
- `action=update_order_status` - Buyurtma holatini yangilash
- `action=update_business_settings` - Sozlamalarni yangilash
- `action=place_order` - Buyurtma berish

## Database:

### businesses jadval:
- owner_id, name, category, is_open, delivery_price, address

### products jadval:
- business_id, name, price, description, image, category, is_available

### orders jadval:
- customer_id, business_id, total_amount, status, items (JSON)

## Status: ✅ COMPLETE

Mahalla Market va Mahalla Biznes to'liq ishlaydigan holatda!
