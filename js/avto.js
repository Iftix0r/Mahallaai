/* ========================================
   MAHALLA AVTO - JavaScript
   ======================================== */

const AVTO_API = 'https://mahallaai.bigsaver.ru/api/avto.php';

// Load salons
async function loadSalons() {
    try {
        const response = await fetch(`${AVTO_API}?action=get_salons`);
        const salons = await response.json();
        
        const container = document.getElementById('salons-container');
        if (!container) return;
        
        if (salons.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-store"></i>
                    <p>Hozircha avtosalonlar yo'q</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = salons.map(salon => `
            <div class="salon-card" onclick="viewSalon(${salon.id})">
                <div class="salon-header">
                    <div class="salon-logo">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="salon-info">
                        <div class="salon-name">${salon.name}</div>
                        <div class="salon-stats">
                            <span><i class="fas fa-car"></i> ${salon.total_cars} ta mashina</span>
                            <span><i class="fas fa-star"></i> ${parseFloat(salon.rating).toFixed(1)}</span>
                        </div>
                    </div>
                </div>
                ${salon.description ? `<p style="color: var(--text-muted); font-size: 14px;">${salon.description}</p>` : ''}
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading salons:', error);
    }
}

// Load cars
async function loadCars(filters = {}) {
    try {
        const params = new URLSearchParams({
            action: 'get_cars',
            ...filters
        });
        
        const response = await fetch(`${AVTO_API}?${params}`);
        const cars = await response.json();
        
        const container = document.getElementById('cars-container');
        if (!container) return;
        
        if (cars.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-car"></i>
                    <p>E'lonlar topilmadi</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = cars.map(car => `
            <div class="car-card" onclick="viewCar(${car.id})">
                <div class="car-image-container">
                    ${car.images ? 
                        `<img src="${car.images.split(',')[0]}" class="car-image" alt="${car.brand} ${car.model}">` :
                        `<div class="car-image" style="display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-car" style="font-size: 3rem; color: #adb5bd;"></i>
                        </div>`
                    }
                    <div class="car-badge">${car.listing_type === 'salon' ? '🏢 Salon' : '👤 Shaxsiy'}</div>
                    <button class="favorite-btn" onclick="event.stopPropagation(); toggleFavorite(${car.id})">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                <div class="car-content">
                    <div class="car-title">${car.brand} ${car.model}</div>
                    <div class="car-specs">
                        <span class="car-spec"><i class="fas fa-calendar"></i> ${car.year}</span>
                        <span class="car-spec"><i class="fas fa-tachometer-alt"></i> ${parseInt(car.mileage).toLocaleString()} km</span>
                        <span class="car-spec"><i class="fas fa-gas-pump"></i> ${car.fuel_type}</span>
                    </div>
                    <div class="car-price">$${parseInt(car.price).toLocaleString()}</div>
                    <div class="car-seller">
                        <i class="fas fa-${car.listing_type === 'salon' ? 'store' : 'user'}"></i>
                        ${car.seller_name}
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading cars:', error);
    }
}

// View salon
async function viewSalon(salonId) {
    try {
        const response = await fetch(`${AVTO_API}?action=get_salon&salon_id=${salonId}`);
        const salon = await response.json();
        
        const carsResponse = await fetch(`${AVTO_API}?action=get_salon_cars&salon_id=${salonId}`);
        const cars = await carsResponse.json();
        
        // Show salon details modal or navigate to salon page
        tg.showAlert(`${salon.name}\n\n${cars.length} ta mashina\n${salon.address || ''}`);
    } catch (error) {
        console.error('Error viewing salon:', error);
    }
}

// View car
async function viewCar(carId) {
    try {
        const response = await fetch(`${AVTO_API}?action=get_car&car_id=${carId}`);
        const car = await response.json();
        
        // Show car details
        let message = `${car.brand} ${car.model}\n\n`;
        message += `💰 Narx: $${parseInt(car.price).toLocaleString()}\n`;
        message += `📅 Yil: ${car.year}\n`;
        message += `🛣 Yurgan: ${parseInt(car.mileage).toLocaleString()} km\n`;
        message += `⛽ Yoqilg'i: ${car.fuel_type}\n`;
        message += `⚙️ Uzatma: ${car.transmission}\n`;
        message += `🎨 Rang: ${car.color}\n\n`;
        message += `📞 Telefon: ${car.seller_phone}`;
        
        tg.showAlert(message);
    } catch (error) {
        console.error('Error viewing car:', error);
    }
}

// Toggle favorite
async function toggleFavorite(carId) {
    if (!window.currentUser) {
        tg.showAlert('Iltimos, tizimga kiring!');
        return;
    }
    
    try {
        const response = await fetch(AVTO_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_favorite',
                user_id: window.currentUser.id,
                car_id: carId
            })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            tg.showAlert(result.action === 'added' ? '❤️ Sevimlilar ro\'yxatiga qo\'shildi' : '💔 Sevimlilardan o\'chirildi');
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

// Add car
async function addCar(carData) {
    if (!window.currentUser) {
        tg.showAlert('Iltimos, tizimga kiring!');
        return;
    }
    
    try {
        const response = await fetch(AVTO_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_car',
                seller_id: window.currentUser.id,
                ...carData
            })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            tg.showAlert('✅ E\'lon muvaffaqiyatli qo\'shildi!');
            loadCars();
        } else {
            tg.showAlert('❌ Xatolik: ' + result.message);
        }
    } catch (error) {
        console.error('Error adding car:', error);
        tg.showAlert('❌ Xatolik yuz berdi!');
    }
}

// Create salon
async function createSalon(salonData) {
    if (!window.currentUser) {
        tg.showAlert('Iltimos, tizimga kiring!');
        return;
    }
    
    try {
        const response = await fetch(AVTO_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_salon',
                owner_id: window.currentUser.id,
                ...salonData
            })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            tg.showAlert('✅ Avtosalon muvaffaqiyatli yaratildi!');
            loadSalons();
        } else {
            tg.showAlert('❌ Xatolik: ' + result.message);
        }
    } catch (error) {
        console.error('Error creating salon:', error);
        tg.showAlert('❌ Xatolik yuz berdi!');
    }
}

// Apply filters
function applyFilters() {
    const filters = {
        listing_type: document.getElementById('filter-type')?.value || '',
        brand: document.getElementById('filter-brand')?.value || '',
        min_price: document.getElementById('filter-min-price')?.value || 0,
        max_price: document.getElementById('filter-max-price')?.value || 999999999,
        year_from: document.getElementById('filter-year-from')?.value || 1900,
        year_to: document.getElementById('filter-year-to')?.value || new Date().getFullYear()
    };
    
    loadCars(filters);
}

// Initialize
if (typeof tg !== 'undefined') {
    tg.ready();
}
