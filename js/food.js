/* ========================================
   FOOD APP - Mahalla Fast Food logikasi
   ======================================== */

let currentBusiness = null;
let foodCart = [];

async function loadFoodBusinesses() {
    const list = document.getElementById('restaurants-list');
    list.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:20px;">Yuklanmoqda...</div>';

    try {
        const response = await fetch(`${API_BASE}?action=get_businesses&category=food`);
        const businesses = await response.json();

        if (businesses && businesses.length > 0) {
            list.innerHTML = '';
            businesses.forEach(biz => {
                const card = document.createElement('div');
                card.className = 'rest-card';
                card.onclick = () => openBusinessMenu(biz);
                card.innerHTML = `
                    <div class="rest-img" style="background-image: url('${biz.logo || 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=400&q=80'}')">
                        <span class="rating">⭐ 5.0</span>
                    </div>
                    <div class="rest-info">
                        <h4>${biz.name}</h4>
                        <p>Fast Food • ${biz.address || 'Mahalla'} • 15-20 min</p>
                    </div>
                `;
                list.appendChild(card);
            });
        } else {
            list.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:20px;">Hozircha ochiq oshxonalar yo\'q</div>';
        }
    } catch (e) {
        list.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:20px;">Xatolik yuz berdi</div>';
    }
}

async function openBusinessMenu(biz) {
    currentBusiness = biz;
    foodCart = [];
    updateFoodCartUI();

    // Show menu view (I need to add this to index.html or reuse a section)
    // For now, let's just populate the popular list and title
    document.querySelector('.food-popular .section-title h3').textContent = `${biz.name} - Menu`;

    const productList = document.querySelector('.popular-list');
    productList.innerHTML = '<p style="text-align:center;">Yuklanmoqda...</p>';

    try {
        const response = await fetch(`${API_BASE}?action=get_products&business_id=${biz.id}`);
        const products = await response.json();

        if (products && products.length > 0) {
            productList.innerHTML = '';
            products.forEach(p => {
                const item = document.createElement('div');
                item.className = 'food-item-horizontal';
                item.innerHTML = `
                    <div class="food-thumb" style="background-image: url('${p.image || 'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?auto=format&fit=crop&w=200&q=80'}')"></div>
                    <div class="food-details">
                        <h5>${p.name}</h5>
                        <p>${p.description || ''}</p>
                        <div class="price-row">
                            <span class="price">${parseFloat(p.price).toLocaleString()} so'm</span>
                            <button class="add-to-cart" onclick="addToFoodCart(${p.id}, '${p.name}', ${p.price}, this)">+</button>
                        </div>
                    </div>
                `;
                productList.appendChild(item);
            });
        } else {
            productList.innerHTML = '<p style="text-align:center; color:#94a3b8;">Hali mahsulotlar qo\'shilmagan</p>';
        }
    } catch (e) {
        productList.innerHTML = '<p style="text-align:center;">Xatolik!</p>';
    }

    // Scroll to menu
    document.querySelector('.food-popular').scrollIntoView({ behavior: 'smooth' });
}

window.addToFoodCart = function (id, name, price, btn) {
    if (!currentBusiness) return;

    const existing = foodCart.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        foodCart.push({ id, name, price, qty: 1 });
    }

    btn.textContent = '✓';
    btn.style.background = '#10b981';
    btn.style.color = 'white';
    setTimeout(() => {
        btn.textContent = '+';
        btn.style.background = '';
        btn.style.color = '';
    }, 600);

    updateFoodCartUI();
};

function updateFoodCartUI() {
    const totalItems = foodCart.reduce((s, i) => s + i.qty, 0);
    const totalPrice = foodCart.reduce((s, i) => s + i.price * i.qty, 0);

    const cartBtn = document.querySelector('.food-header .cart-btn');
    const badge = cartBtn.querySelector('.cart-count');
    badge.textContent = totalItems;

    if (totalItems > 0) {
        cartBtn.style.background = 'var(--primary)';
        cartBtn.style.color = 'white';
    } else {
        cartBtn.style.background = '';
        cartBtn.style.color = '';
    }
}

// Order handling
document.querySelector('.food-header .cart-btn').onclick = async () => {
    if (foodCart.length === 0) {
        tg.showAlert("Savatingiz bo'sh!");
        return;
    }

    const total = foodCart.reduce((s, i) => s + i.price * i.qty, 0);

    tg.showConfirm(`Buyurtma berishni tasdiqlaysizmi?\nUmumiy summa: ${total.toLocaleString()} so'm`, async (ok) => {
        if (ok) {
            if (!currentUser) {
                tg.showAlert("Iltimos, avval tizimga kiring!");
                return;
            }

            try {
                const response = await fetch(`${API_BASE}?action=place_order`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: currentUser.id,
                        business_id: currentBusiness.id,
                        total_amount: total,
                        items: foodCart
                    })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    currentUser.balance = result.new_balance;
                    updateUI(currentUser);
                    tg.showAlert("Buyurtma qabul qilindi! ✅\nBalansingizdan yechildi. Yetkazib berish: 20-30 daqiqa.");
                    foodCart = [];
                    updateFoodCartUI();
                } else {
                    tg.showAlert(result.message);
                }
            } catch (e) {
                tg.showAlert("Xatolik yuz berdi!");
            }
        }
    });
};

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    // We wait for core.js to maybe load things, or just load if we are in the section
});

// Since switchTab is global, we can hook into it
const originalSwitchTab = window.switchTab;
window.switchTab = function (id) {
    if (id === 'food') {
        loadFoodBusinesses();
    }
    if (originalSwitchTab) originalSwitchTab(id);
};
