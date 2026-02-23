# 🔧 Media Yuklash Muammolarini Hal Qilish

## Muammo: "Yuborilmoqda..." holatida qotib qoladi

### Tuzatishlar

#### 1. JavaScript FormData To'g'ri Yaratildi
```javascript
// Yangi usul - har bir fieldni alohida qo'shamiz
const formData = new FormData();
formData.append('send_broadcast', '1');
formData.append('message_text', text);
formData.append('target', target);

// Faylni qo'shamiz
if (file) {
    formData.append('media_file', file);
    console.log('File added:', file.name, file.size, file.type);
}
```

#### 2. XHR Timeout Qo'shildi
```javascript
xhr.timeout = 300000; // 5 daqiqa (katta fayllar uchun)
```

#### 3. Console Logging
```javascript
console.log('Response status:', xhr.status);
console.log('Response text:', xhr.responseText);
```

#### 4. PHP Xatolik Boshqaruvi
```php
$uploadErrors = [
    UPLOAD_ERR_INI_SIZE => 'Fayl hajmi juda katta (php.ini)',
    UPLOAD_ERR_FORM_SIZE => 'Fayl hajmi juda katta (form)',
    UPLOAD_ERR_PARTIAL => 'Fayl qisman yuklandi',
    // ...
];
```

## Tekshirish Bosqichlari

### 1. PHP Konfiguratsiyasini Tekshirish
```
URL: https://mahallaai.bigsaver.ru/admin/check_upload.php
```

Bu sahifa quyidagilarni ko'rsatadi:
- ✅ upload_max_filesize (kamida 50M bo'lishi kerak)
- ✅ post_max_size (kamida 50M bo'lishi kerak)
- ✅ file_uploads (Enabled bo'lishi kerak)
- ✅ uploads/ papkasi mavjudligi va yozish huquqi

### 2. Test Yuklash
check_upload.php sahifasida test fayl yuklang va natijani ko'ring.

### 3. Browser Console Tekshirish
1. F12 bosing (Developer Tools)
2. Console tabiga o'ting
3. Broadcast sahifasida fayl yuklang
4. Console'da xatoliklarni ko'ring

### 4. Network Tab Tekshirish
1. F12 > Network tab
2. Fayl yuklang
3. POST so'rovini toping
4. Response'ni tekshiring

## Keng Tarqalgan Muammolar va Yechimlar

### Muammo 1: "Fayl hajmi juda katta"
**Yechim:** PHP konfiguratsiyasini o'zgartiring
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

### Muammo 2: "Vaqtinchalik papka topilmadi"
**Yechim:** PHP temp dirni tekshiring
```ini
upload_tmp_dir = /tmp
```

### Muammo 3: "Faylni yozib bo'lmadi"
**Yechim:** Papka ruxsatlarini tekshiring
```bash
chmod 755 admin/uploads/
chown www-data:www-data admin/uploads/
```

### Muammo 4: "Progress bar 100% bo'ldi lekin sahifa yangilanmadi"
**Yechim:** PHP response'ni tekshiring
- Console'da xhr.responseText ni ko'ring
- PHP xatoliklari bo'lishi mumkin

### Muammo 5: "Faqat matn yuboriladi, video yo'q"
**Yechim:** 
- FormData to'g'ri yaratilganini tekshiring
- File input elementining value'si saqlanganini tekshiring
- Console'da "File added" xabarini ko'ring

## Debug Qilish

### JavaScript Console'da
```javascript
// Faylni tekshirish
const file = document.getElementById('mediaFile').files[0];
console.log('File:', file);
console.log('Name:', file.name);
console.log('Size:', file.size);
console.log('Type:', file.type);

// FormData'ni tekshirish
for (let pair of formData.entries()) {
    console.log(pair[0], pair[1]);
}
```

### PHP'da
```php
// $_FILES'ni tekshirish
error_log(print_r($_FILES, true));

// Fayl yuklangandan keyin
error_log("File uploaded: $mediaFile, Type: $mediaType");
```

## Server Talablari

### Minimal Konfiguratsiya
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
file_uploads = On
```

### Apache .htaccess
```apache
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value max_input_time 300
```

### Nginx
```nginx
client_max_body_size 50M;
```

## Test Qilish

1. **Kichik fayl (< 1MB):** Rasm yuklang
2. **O'rta fayl (5-10MB):** Video yuklang
3. **Katta fayl (20-30MB):** Uzun video yuklang

Har bir test uchun:
- ✅ Progress bar to'g'ri ishlaydi
- ✅ Foiz ko'rsatkichi yangilanadi
- ✅ Muvaffaqiyatli xabar ko'rinadi
- ✅ Sahifa avtomatik yangilanadi
- ✅ Fayl Telegram'ga yuboriladi

## Xatolik Xabarlari

| Xabar | Sabab | Yechim |
|-------|-------|--------|
| "Fayl hajmi juda katta" | PHP limit | php.ini'ni o'zgartiring |
| "Vaqt tugadi" | Timeout | max_execution_time oshiring |
| "Tarmoq xatosi" | Internet | Aloqani tekshiring |
| "Faylni yozib bo'lmadi" | Ruxsat | chmod 755 qiling |

## Qo'shimcha Yordam

Agar muammo hal bo'lmasa:
1. check_upload.php natijalarini ko'ring
2. Browser console'dagi xatoliklarni ko'ring
3. Server error log'larini tekshiring
4. PHP version'ni tekshiring (7.4+ tavsiya etiladi)
