/* ========================================
   DRIVER PANEL - Haydovchi paneli
   ======================================== */

const tg = window.Telegram.WebApp;
tg.expand();

const API_URL = 'https://mahallaai.bigsaver.ru/api/index.php';
let driverData = null;
let isOnline = false;
let updateInterval = null;

// Initialize
async function init() {
    const userId = tg.initDataUnsafe?.user?.id || localStorage.getItem('test_user_id');
    
    if (!userId) {
        alert('Telegram orqali kiring!');
        return;
    }

    // Get driver info
    const response = await fetch(`${API_URL}?action=get_driver&user_id=${userId}`);
    const data = await response.json();

    if (data.error) {
        // Not registered as driver
        document.querySelector('.container').innerHTML = `
            <div class="card" style="text-align: center; padding: 40px;">
                <i class="fas fa-car" style="font-size: 64px; color: #667eea; margin-bottom: 20px;"></i>
                <h2>Haydovchi sifatida ro'yxatdan o'ting</h2>
                <p style="color: #6c757d; margin: 16px 0;">Mahalla Taxi haydovchisi bo'lish uchun ma'lumotlaringizni kiriting</p>
                <button class="btn btn-accept" onclick="registerDriver()">Ro'yxatdan o'tish</button>
            </div>
        `;
        return;
    }

    driverData = data;
    isOnline = data.is_online == 1;

    // Update UI
    document.getElementById('driver-name').textContent = `ID: ${data.id} | ${data.car_model}`;
    document.getElementById('total-trips').textContent = data.total_trips;
    document.getElementById('rating').textContent = parseFloat(data.rating).toFixed(1);
    document.getElementById('car-type').textContent = data.car_type;
    
    const toggle = document.getElementById('online-toggle');
    const statusText = document.getElementById('status-text');
    
    if (isOnline) {
        toggle.classList.add('active');
        statusText.textContent = 'Online';
        statusText.style.color = '#28a745';
        startLocationTracking();
        startOrderPolling();
    } else {
        statusText.textContent = 'Offline';
        statusText.style.color = '#dc3545';
    }

    // Toggle online/offline
    toggle.addEventListener('click', toggleOnlineStatus);

    // Load orders
    loadMyOrders();
    loadPendingOrders();
}

async function toggleOnlineStatus() {
    isOnline = !isOnline;
    
    const toggle = document.getElementById('online-toggle');
    const statusText = document.getElementById('status-text');

    if (isOnline) {
        toggle.classList.add('active');
        statusText.textContent = 'Online';
        statusText.style.color = '#28a745';
        
        // Get location and update
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (position) => {
                await updateLocation(position.coords.latitude, position.coords.longitude, 1);
                startLocationTracking();
                startOrderPolling();
            });
        }
    } else {
        toggle.classList.remove('active');
        statusText.textContent = 'Offline';
        statusText.style.color = '#dc3545';
        await updateLocation(null, null, 0);
        stopLocationTracking();
        stopOrderPolling();
    }
}

async function updateLocation(lat, lng, online) {
    await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update_driver_location',
            driver_id: driverData.id,
            lat: lat,
            lng: lng,
            is_online: online
        })
    });
}

function startLocationTracking() {
    if (navigator.geolocation) {
        updateInterval = setInterval(() => {
            navigator.geolocation.getCurrentPosition((position) => {
                updateLocation(position.coords.latitude, position.coords.longitude, 1);
            });
        }, 30000); // Update every 30 seconds
    }
}

function stopLocationTracking() {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
}

let orderPollingInterval = null;

function startOrderPolling() {
    loadPendingOrders();
    orderPollingInterval = setInterval(() => {
        loadPendingOrders();
        loadMyOrders();
    }, 5000); // Check every 5 seconds
}

function stopOrderPolling() {
    if (orderPollingInterval) {
        clearInterval(orderPollingInterval);
        orderPollingInterval = null;
    }
}

async function loadMyOrders() {
    const response = await fetch(`${API_URL}?action=get_driver_orders&driver_id=${driverData.id}`);
    const orders = await response.json();

    const container = document.getElementById('my-orders');
    
    if (!orders || orders.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Hozircha faol buyurtmalar yo'q</p>
            </div>
        `;
        return;
    }

    container.innerHTML = orders.map(order => `
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">#${order.id}</span>
                <span class="order-price">${parseInt(order.price).toLocaleString()} so'm</span>
            </div>
            <div class="order-info">
                <i class="fas fa-user"></i> ${order.customer_name} (${order.customer_phone})
            </div>
            <div class="order-info">
                <i class="fas fa-map-marker-alt"></i> ${order.from_address}
            </div>
            <div class="order-info">
                <i class="fas fa-flag-checkered"></i> ${order.to_address}
            </div>
            <button class="btn btn-complete" onclick="completeOrder(${order.id})">
                <i class="fas fa-check"></i> Yakunlash
            </button>
        </div>
    `).join('');
}

async function loadPendingOrders() {
    const response = await fetch(`${API_URL}?action=get_pending_orders&car_type=${driverData.car_type}`);
    const orders = await response.json();

    const container = document.getElementById('pending-orders');
    
    if (!orders || orders.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <p>Yangi buyurtmalar yo'q</p>
            </div>
        `;
        return;
    }

    container.innerHTML = orders.map(order => `
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">#${order.id}</span>
                <span class="order-price">${parseInt(order.price).toLocaleString()} so'm</span>
            </div>
            <div class="order-info">
                <i class="fas fa-user"></i> ${order.customer_name}
            </div>
            <div class="order-info">
                <i class="fas fa-map-marker-alt"></i> ${order.from_address}
            </div>
            <div class="order-info">
                <i class="fas fa-flag-checkered"></i> ${order.to_address}
            </div>
            <button class="btn btn-accept" onclick="acceptOrder(${order.id})">
                <i class="fas fa-check-circle"></i> Qabul qilish
            </button>
        </div>
    `).join('');
}

async function acceptOrder(orderId) {
    if (!confirm('Bu buyurtmani qabul qilasizmi?')) return;

    const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'accept_order',
            driver_id: driverData.id,
            order_id: orderId
        })
    });

    const result = await response.json();
    
    if (result.status === 'success') {
        tg.showAlert('Buyurtma qabul qilindi!');
        loadMyOrders();
        loadPendingOrders();
    } else {
        tg.showAlert(result.message || 'Xatolik yuz berdi');
    }
}

async function completeOrder(orderId) {
    if (!confirm('Safar yakunlandimi?')) return;

    const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'complete_order',
            order_id: orderId,
            driver_id: driverData.id
        })
    });

    const result = await response.json();
    
    if (result.status === 'success') {
        tg.showAlert('Safar yakunlandi! 🎉');
        driverData.total_trips++;
        document.getElementById('total-trips').textContent = driverData.total_trips;
        loadMyOrders();
    } else {
        tg.showAlert(result.message || 'Xatolik yuz berdi');
    }
}

// Initialize on load
init();
