/* ========================================
   MAHALLA BIZNES - Biznes Dashboard logikasi
   ======================================== */

let myBusiness = null;

async function checkMyBusiness() {
    if (!currentUser) return;

    try {
        const response = await fetch(`${API_BASE}?action=get_my_business&owner_id=${currentUser.id}`);
        const biz = await response.json();

        if (biz && !biz.error) {
            myBusiness = biz;
            showBiznesDashboard();
        } else {
            showBiznesCreateView();
        }
    } catch (e) {
        console.error("Biznes check error:", e);
    }
}

function showBiznesCreateView() {
    document.getElementById('biznes-create-view').classList.remove('hidden');
    document.getElementById('biznes-dashboard-view').classList.add('hidden');
}

window.createBiznes = async function () {
    const name = document.getElementById('biz-name').value.trim();
    const category = document.getElementById('biz-category').value;

    if (!name) {
        tg.showAlert("Iltimos, biznesingiz nomini kiriting!");
        return;
    }

    if (!currentUser) {
        tg.showAlert("Iltimos, avval tizimga kiring!");
        return;
    }

    try {
        const response = await fetch(`${API_BASE}?action=create_business`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                owner_id: currentUser.id,
                name: name,
                category: category
            })
        });
        const result = await response.json();

        if (result.status === 'success') {
            tg.showAlert("Tabriklaymiz! Biznesingiz yaratildi 🎉");
            checkMyBusiness();
        } else {
            tg.showAlert("Xatolik: " + result.message);
        }
    } catch (e) {
        tg.showAlert("Server bilan aloqa uzildi.");
    }
};

async function showBiznesDashboard() {
    if (!myBusiness) return;

    document.getElementById('biznes-create-view').classList.add('hidden');
    document.getElementById('biznes-dashboard-view').classList.remove('hidden');

    document.getElementById('biz-dash-logo').textContent = myBusiness.name.charAt(0).toUpperCase();
    document.getElementById('biz-dash-name').textContent = myBusiness.name;

    const catMap = {
        'food': '🍱 Fast Food',
        'market': '🛒 Market',
        'service': '🔧 Xizmat',
        'other': '📦 Boshqa'
    };
    document.getElementById('biz-dash-cat').textContent = catMap[myBusiness.category] || myBusiness.category;

    loadBusinessOrders();
}

async function loadBusinessOrders() {
    if (!myBusiness) return;

    const list = document.getElementById('bz-orders');
    list.innerHTML = '<p style="text-align:center; padding:20px;">Yuklanmoqda...</p>';

    try {
        const response = await fetch(`${API_BASE}?action=get_business_orders&business_id=${myBusiness.id}`);
        const orders = await response.json();

        if (orders && orders.length > 0) {
            list.innerHTML = orders.map(o => {
                const items = JSON.parse(o.items);
                const itemsText = items.map(i => `${i.qty} x ${i.name}`).join(', ');
                return `
                    <div class="bz-card" style="margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-weight:700;">Order #${o.id}</span>
                            <span style="color:var(--primary); font-weight:600;">${parseFloat(o.total_amount).toLocaleString()}</span>
                        </div>
                        <p style="font-size:0.85rem; color:#64748b; margin-bottom:8px;">Mijoz: ${o.customer_name}</p>
                        <p style="font-size:0.9rem;">${itemsText}</p>
                        <div style="margin-top:10px; display:flex; gap:10px;">
                            <button class="btn-primary" style="padding:8px 16px; font-size:0.8rem; background: var(--accent); color:white;">Tayyor</button>
                            <button class="btn-outline" style="padding:8px 16px; font-size:0.8rem; border:1px solid #e2e8f0; border-radius:10px;">Bekor qilish</button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            list.innerHTML = `
                <div class="empty-orders">
                    <span>📝</span>
                    <p>Hozircha yangi buyurtmalar yo'q</p>
                </div>
            `;
        }
    } catch (e) {
        list.innerHTML = '<p>Xatolik!</p>';
    }
}

window.addProduct = async function () {
    const name = prompt("Mahsulot nomi:");
    const price = prompt("Narxi (so'm):");
    const desc = prompt("Tavsif:");

    if (!name || !price) return;

    try {
        const response = await fetch(`${API_BASE}?action=add_product`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                business_id: myBusiness.id,
                name: name,
                price: parseFloat(price),
                description: desc
            })
        });
        const result = await response.json();

        if (result.status === 'success') {
            tg.showAlert("Mahsulot qo'shildi!");
            loadBusinessProducts();
        } else {
            tg.showAlert("Xatolik!");
        }
    } catch (e) {
        tg.showAlert("Xatolik!");
    }
}

async function loadBusinessProducts() {
    if (!myBusiness) return;
    const list = document.querySelector('.bz-product-list');
    list.innerHTML = 'Yuklanmoqda...';

    try {
        const response = await fetch(`${API_BASE}?action=get_products&business_id=${myBusiness.id}`);
        const products = await response.json();

        if (products && products.length > 0) {
            list.innerHTML = products.map(p => `
                <div class="bz-card" style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <h5 style="margin:0">${p.name}</h5>
                        <p style="margin:0; font-size:0.8rem; color:#64748b;">${parseFloat(p.price).toLocaleString()} so'm</p>
                    </div>
                    <button style="border:none; background:none; color:#ef4444;">O'chirish</button>
                </div>
            `).join('');
        } else {
            list.innerHTML = '<p style="text-align:center; color:#94a3b8;">Mahsulotlar yo\'q</p>';
        }
    } catch (e) {
        list.innerHTML = 'Xatolik!';
    }
}

// Tabs
document.querySelectorAll('.bz-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.bz-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        document.querySelectorAll('.bz-tab-content').forEach(c => c.classList.add('hidden'));
        const targetId = tab.dataset.target;
        document.getElementById(targetId).classList.remove('hidden');

        if (targetId === 'bz-products') loadBusinessProducts();
        if (targetId === 'bz-orders') loadBusinessOrders();
    });
});

// Hook into navigation
const bizOriginalSwitchTab = window.switchTab;
window.switchTab = function (id) {
    if (id === 'biznes') {
        checkMyBusiness();
    }
    if (bizOriginalSwitchTab) bizOriginalSwitchTab(id);
};
