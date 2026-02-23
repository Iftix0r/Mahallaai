/* ========================================
   TAXI APP - Mahalla Taxi logikasi
   ======================================== */

let currentLocation = null;
let currentOrderId = null;
let orderCheckInterval = null;

// Get user location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((position) => {
        currentLocation = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
        };
    });
}

// Taxi car selection
document.querySelectorAll('.car-type').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.car-type').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
    });
});

window.orderTaxi = async function () {
    const from = document.getElementById('taxi-from')?.value.trim() || 'Joriy manzil';
    const to = document.getElementById('taxi-to').value.trim();
    
    if (!to) {
        tg.showAlert("Iltimos, boradigan manzilingizni kiriting!");
        return;
    }

    const activeType = document.querySelector('.car-type.active h5').textContent;
    const priceText = document.querySelector('.car-type.active .car-price').textContent;
    const price = parseInt(priceText.replace(/\s/g, ''));

    // Get location if available
    let fromLat = currentLocation?.lat;
    let fromLng = currentLocation?.lng;

    if (!fromLat && navigator.geolocation) {
        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject);
            });
            fromLat = position.coords.latitude;
            fromLng = position.coords.longitude;
        } catch (e) {
            console.log('Location not available');
        }
    }

    // Create order
    const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'order_taxi',
            customer_id: window.currentUser.id,
            car_type: activeType,
            from_address: from,
            to_address: to,
            from_lat: fromLat,
            from_lng: fromLng,
            to_lat: null,
            to_lng: null,
            price: price
        })
    });

    const result = await response.json();

    if (result.status === 'success') {
        currentOrderId = result.order_id;
        
        if (result.driver_assigned) {
            tg.showAlert(`✅ Taxi topildi!\n\nMashina: ${activeType}\nMasofa: ~${result.driver_distance} km\nNarx: ${price.toLocaleString()} so'm\n\nHaydovchi 3-5 daqiqada yetib keladi.`);
        } else {
            tg.showAlert(`⏳ Buyurtma qabul qilindi!\n\nEng yaqin haydovchi topilmoqda...\nNarx: ${price.toLocaleString()} so'm`);
        }

        // Update balance
        window.currentUser.balance = result.new_balance || (window.currentUser.balance - price);
        updateBalanceDisplay();

        // Start checking order status
        startOrderStatusCheck();
    } else {
        tg.showAlert(result.message || 'Xatolik yuz berdi!');
    }
};

function startOrderStatusCheck() {
    if (orderCheckInterval) {
        clearInterval(orderCheckInterval);
    }

    orderCheckInterval = setInterval(async () => {
        if (!currentOrderId) {
            clearInterval(orderCheckInterval);
            return;
        }

        const response = await fetch(`${API_URL}?action=get_order_status&order_id=${currentOrderId}`);
        const order = await response.json();

        if (order.error) {
            clearInterval(orderCheckInterval);
            return;
        }

        // Check if driver assigned
        if (order.status === 'assigned' || order.status === 'accepted') {
            clearInterval(orderCheckInterval);
            
            tg.showAlert(`🚕 Haydovchi tayinlandi!\n\nIsmi: ${order.driver_name || 'Haydovchi'}\nMashina: ${order.car_model} (${order.car_number})\nRang: ${order.car_color}\nTelefon: ${order.driver_phone || '-'}\n\nHaydovchi yo'lda!`);
        }

        // Check if completed
        if (order.status === 'completed') {
            clearInterval(orderCheckInterval);
            currentOrderId = null;
            tg.showAlert('✅ Safar yakunlandi! Xaridingiz uchun rahmat!');
        }

        // Check if cancelled
        if (order.status === 'cancelled') {
            clearInterval(orderCheckInterval);
            currentOrderId = null;
            tg.showAlert('❌ Buyurtma bekor qilindi. Pul hisobingizga qaytarildi.');
        }
    }, 5000); // Check every 5 seconds
}

// Cancel order function
window.cancelTaxiOrder = async function() {
    if (!currentOrderId) {
        tg.showAlert('Faol buyurtma yo\'q');
        return;
    }

    if (!confirm('Buyurtmani bekor qilasizmi? Pul hisobingizga qaytariladi.')) {
        return;
    }

    const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'cancel_order',
            order_id: currentOrderId
        })
    });

    const result = await response.json();

    if (result.status === 'success') {
        tg.showAlert('Buyurtma bekor qilindi. Pul qaytarildi.');
        currentOrderId = null;
        if (orderCheckInterval) {
            clearInterval(orderCheckInterval);
        }
        // Reload user data to update balance
        await loadUserData();
    } else {
        tg.showAlert(result.message || 'Xatolik yuz berdi');
    }
};

// Show order history
window.showTaxiHistory = async function() {
    if (!window.currentUser) return;

    const response = await fetch(`${API_URL}?action=get_customer_orders&customer_id=${window.currentUser.id}`);
    const orders = await response.json();

    if (!orders || orders.length === 0) {
        tg.showAlert('Hozircha buyurtmalar yo\'q');
        return;
    }

    const statuses = {
        'pending': '⏳ Kutilmoqda',
        'assigned': '🚕 Tayinlangan',
        'accepted': '✅ Qabul qilindi',
        'completed': '✔️ Yakunlandi',
        'cancelled': '❌ Bekor qilindi'
    };

    let message = '📋 Buyurtmalar tarixi:\n\n';
    orders.slice(0, 5).forEach(order => {
        message += `#${order.id} - ${statuses[order.status] || order.status}\n`;
        message += `${order.from_address} → ${order.to_address}\n`;
        message += `${parseInt(order.price).toLocaleString()} so'm\n`;
        message += `${new Date(order.created_at).toLocaleString('uz-UZ')}\n\n`;
    });

    tg.showAlert(message);
};
