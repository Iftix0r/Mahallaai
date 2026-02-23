/* ========================================
   MARKET APP - Mahalla Market logikasi
   ======================================== */

let currentMarket = null;
let marketCart = [];

async function loadMarketBusinesses() {
    const list = document.querySelector('.store-scroll');
    list.innerHTML = '<div class="store-chip active">Yuklanmoqda...</div>';

    try {
        const response = await fetch(`${API_BASE}?action=get_businesses&category=market`);
        const businesses = await response.json();

        if (businesses && businesses.length > 0) {
            list.innerHTML = '<div class="store-chip active" onclick="loadAllMarketProducts()">🏪 Barchasi</div>';
            businesses.forEach(biz => {
                const chip = document.createElement('div');
                chip.className = 'store-chip';
                chip.textContent = biz.name;
                chip.onclick = () => {
                    document.querySelectorAll('.store-chip').forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    loadShopProducts(biz);
                };
                list.appendChild(chip);
            });
            // Auto load first one or all
            loadAllMarketProducts();
        } else {
            list.innerHTML = '<div class="store-chip">Do\'konlar yo\'q</div>';
        }
    } catch (e) {
        list.innerHTML = '<div class="store-chip">Xatolik!</div>';
    }
}

async function loadAllMarketProducts() {
    const grid = document.getElementById('products-grid');
    grid.innerHTML = '<p style="text-align:center; grid-column:1/-1;">Mahsulotlar yuklanmoqda...</p>';

    try {
        const response = await fetch(`${API_BASE}?action=get_products&category=market`); // Need to adjust API to support global product fetch or per biz
        // For now, let's fetch all market businesses and their products
        const bizResp = await fetch(`${API_BASE}?action=get_businesses&category=market`);
        const businesses = await bizResp.json();

        let allProducts = [];
        for (let biz of businesses) {
            const pResp = await fetch(`${API_BASE}?action=get_products&business_id=${biz.id}`);
            const products = await pResp.json();
            products.forEach(p => p.biz_id = biz.id); // Tag with biz_id
            allProducts = allProducts.concat(products);
        }

        renderProducts(allProducts);
    } catch (e) {
        grid.innerHTML = '<p style="text-align:center; grid-column:1/-1;">Xatolik!</p>';
    }
}

async function loadShopProducts(biz) {
    currentMarket = biz;
    const grid = document.getElementById('products-grid');
    grid.innerHTML = '<p style="text-align:center; grid-column:1/-1;">Yuklanmoqda...</p>';

    try {
        const response = await fetch(`${API_BASE}?action=get_products&business_id=${biz.id}`);
        const products = await response.json();
        products.forEach(p => p.biz_id = biz.id);
        renderProducts(products);
    } catch (e) {
        grid.innerHTML = '<p style="text-align:center; grid-column:1/-1;">Xatolik!</p>';
    }
}

function renderProducts(products) {
    const grid = document.getElementById('products-grid');
    document.getElementById('product-count').textContent = products.length + ' ta';

    if (products.length === 0) {
        grid.innerHTML = '<p style="text-align:center; grid-column:1/-1;">Mahsulotlar topilmadi</p>';
        return;
    }

    // Category emoji mapping
    const categoryEmoji = {
        'mevalar': '🍎',
        'sabzavotlar': '🥦',
        'sut': '🥛',
        'gosht': '🥩',
        'non': '🍞',
        'ichimliklar': '🧃',
        'tozalik': '🧽',
        'boshqa': '📦'
    };

    grid.innerHTML = products.map(p => {
        const category = (p.category || 'boshqa').toLowerCase();
        const emoji = categoryEmoji[category] || '📦';
        
        return `
        <div class="product-card" data-id="${p.id}" data-biz-id="${p.biz_id}" data-name="${p.name}" data-price="${p.price}" data-category="${category}">
            <div class="product-img" style="background: #f1f5f9;">
                ${p.image ? 
                    `<img src="${p.image}" style="width:100%; height:100%; object-fit:contain;" onerror="this.parentElement.innerHTML='<span style=\\'font-size:3rem;\\'>${emoji}</span>'">` :
                    `<span style="font-size:3rem;">${emoji}</span>`
                }
            </div>
            <div class="product-info">
                <h5>${p.name}</h5>
                <p>${p.description || 'Donalik / kg'}</p>
                ${p.business_name ? `<p style="font-size:0.75rem; color:#94a3b8;">🏪 ${p.business_name}</p>` : ''}
                <div class="product-bottom">
                    <span class="product-price">${parseFloat(p.price).toLocaleString()}</span>
                    <button class="add-product-btn" onclick="addToMarketCart(this)">+</button>
                </div>
            </div>
        </div>
    `}).join('');
}

// Category filter
document.querySelectorAll('.mcat-item').forEach(cat => {
    cat.addEventListener('click', () => {
        document.querySelectorAll('.mcat-item').forEach(c => c.classList.remove('active'));
        cat.classList.add('active');
        
        const category = cat.dataset.cat;
        filterProductsByCategory(category);
    });
});

function filterProductsByCategory(category) {
    const cards = document.querySelectorAll('.product-card');
    
    cards.forEach(card => {
        const productCategory = card.dataset.category || '';
        
        if (category === 'all' || productCategory === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update count
    const visibleCount = document.querySelectorAll('.product-card[style*="display: block"], .product-card:not([style*="display: none"])').length;
    document.getElementById('product-count').textContent = visibleCount + ' ta';
}

window.addToMarketCart = function (btn) {
    const card = btn.closest('.product-card');
    const id = card.dataset.id;
    const bizId = card.dataset.bizId;
    const name = card.dataset.name;
    const price = parseInt(card.dataset.price);

    const existing = marketCart.find(i => i.id === id);
    if (existing) {
        existing.qty++;
    } else {
        marketCart.push({ id, bizId, name, price, qty: 1 });
    }

    btn.textContent = '✓';
    btn.style.background = '#10b981';
    btn.style.color = 'white';
    setTimeout(() => {
        btn.textContent = '+';
        btn.style.background = '';
        btn.style.color = '';
    }, 600);

    updateCartUI();
};

function updateCartUI() {
    const totalItems = marketCart.reduce((s, i) => s + i.qty, 0);
    const totalPrice = marketCart.reduce((s, i) => s + i.price * i.qty, 0);

    document.getElementById('market-cart-count').textContent = totalItems;
    document.getElementById('cart-bar-items').textContent = totalItems + ' ta mahsulot';
    document.getElementById('cart-bar-total').textContent = totalPrice.toLocaleString() + " so'm";
    document.getElementById('cart-modal-total').textContent = totalPrice.toLocaleString() + " so'm";

    const bar = document.getElementById('market-cart-bar');
    if (totalItems > 0) bar.classList.remove('hidden'); else bar.classList.add('hidden');

    const list = document.getElementById('cart-items-list');
    if (marketCart.length === 0) {
        list.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:20px;">Savat bo\'sh</p>';
    } else {
        list.innerHTML = marketCart.map((item, idx) => `
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f1f5f9;">
                <div>
                    <div style="font-weight:600;">${item.name}</div>
                    <div style="color:var(--text-muted);font-size:0.8rem;">${item.price.toLocaleString()} x ${item.qty}</div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-weight:700;">${(item.price * item.qty).toLocaleString()}</span>
                    <button onclick="removeFromCart(${idx})" style="background:#fee2e2;color:#ef4444;border:none;width:28px;height:28px;border-radius:8px;cursor:pointer;font-weight:700;">−</button>
                </div>
            </div>
        `).join('');
    }
}

window.removeFromCart = function (idx) {
    marketCart[idx].qty--;
    if (marketCart[idx].qty <= 0) marketCart.splice(idx, 1);
    updateCartUI();
};

window.openMarketCart = function () {
    document.getElementById('market-cart-modal').classList.remove('hidden');
};

window.closeMarketCart = function () {
    document.getElementById('market-cart-modal').classList.add('hidden');
};

window.placeMarketOrder = async function () {
    if (marketCart.length === 0) {
        tg.showAlert("Savatingiz bo'sh!");
        return;
    }

    if (!currentUser) {
        tg.showAlert("Iltimos, avval tizimga kiring!");
        return;
    }

    const total = marketCart.reduce((s, i) => s + i.price * i.qty, 0);

    // Group items by business for multiple orders if needed, or just assume one for now
    const bizId = marketCart[0].bizId; // Simplified: assumes all from same biz or just first biz

    try {
        const response = await fetch(`${API_BASE}?action=place_order`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                customer_id: currentUser.id,
                business_id: bizId,
                total_amount: total,
                items: marketCart
            })
        });
        const result = await response.json();

        if (result.status === 'success') {
            currentUser.balance = result.new_balance;
            updateUI(currentUser);
            tg.showAlert("Buyurtma qabul qilindi! ✅\nBalansingizdan yechildi. Yetkazib berish: 30-45 daqiqa.");
            marketCart = [];
            updateCartUI();
            closeMarketCart();
        } else {
            tg.showAlert(result.message);
        }
    } catch (e) {
        tg.showAlert("Xatolik yuz berdi!");
    }
};

// Hook into navigation
const marketOriginalSwitchTab = window.switchTab;
window.switchTab = function (id) {
    if (id === 'market') {
        loadMarketBusinesses();
    }
    if (marketOriginalSwitchTab) marketOriginalSwitchTab(id);
};
