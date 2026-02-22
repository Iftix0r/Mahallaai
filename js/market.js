/* ========================================
   MARKET APP - Mahalla Market logikasi
   ======================================== */

let marketCart = [];

// Category filter
document.querySelectorAll('.mcat-item').forEach(cat => {
    cat.addEventListener('click', () => {
        document.querySelectorAll('.mcat-item').forEach(c => c.classList.remove('active'));
        cat.classList.add('active');
        const selected = cat.dataset.cat;
        const cards = document.querySelectorAll('.product-card');
        let visible = 0;
        cards.forEach(card => {
            if (selected === 'all' || card.dataset.cat === selected) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });
        document.getElementById('product-count').textContent = visible + ' ta';
    });
});

// Store chips
document.querySelectorAll('.store-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.store-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
    });
});

// Search
document.getElementById('market-search').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        card.style.display = name.includes(q) ? '' : 'none';
    });
});

// Add to cart
window.addToMarketCart = function (btn) {
    const card = btn.closest('.product-card');
    const name = card.dataset.name;
    const price = parseInt(card.dataset.price);

    const existing = marketCart.find(i => i.name === name);
    if (existing) {
        existing.qty++;
    } else {
        marketCart.push({ name, price, qty: 1 });
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
    if (totalItems > 0) {
        bar.classList.remove('hidden');
    } else {
        bar.classList.add('hidden');
    }

    // Update modal list
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

window.placeMarketOrder = function () {
    if (marketCart.length === 0) {
        tg.showAlert("Savatingiz bo'sh!");
        return;
    }
    const total = marketCart.reduce((s, i) => s + i.price * i.qty, 0);
    tg.showAlert(`Buyurtma qabul qilindi! ✅\nJami: ${total.toLocaleString()} so'm\nYetkazib berish: 30-45 daqiqa`);
    marketCart = [];
    updateCartUI();
    closeMarketCart();
};
