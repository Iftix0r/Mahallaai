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

    // Load statistics
    loadBusinessStats();
    loadBusinessOrders();
}

async function loadBusinessStats() {
    if (!myBusiness) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=get_business_stats&business_id=${myBusiness.id}`);
        const stats = await response.json();
        
        // Update stats display
        document.querySelector('.bz-stat:nth-child(1) h4').textContent = 
            parseFloat(stats.today_revenue).toLocaleString() + ' so\'m';
        document.querySelector('.bz-stat:nth-child(2) h4').textContent = 
            stats.today_orders + ' ta';
        
        // Update badge on orders tab
        const badge = document.querySelector('.bz-tab[data-target="bz-orders"] .badge');
        if (badge) {
            badge.textContent = stats.pending_orders;
        }
    } catch (e) {
        console.error('Stats load error:', e);
    }
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
                
                const statusMap = {
                    'pending': { text: 'Yangi', color: '#f59e0b', bg: '#fef3c7' },
                    'preparing': { text: 'Tayyorlanmoqda', color: '#3b82f6', bg: '#dbeafe' },
                    'ready': { text: 'Tayyor', color: '#10b981', bg: '#d1fae5' },
                    'delivering': { text: 'Yetkazilmoqda', color: '#8b5cf6', bg: '#ede9fe' },
                    'completed': { text: 'Yetkazildi', color: '#6b7280', bg: '#f3f4f6' },
                    'cancelled': { text: 'Bekor qilindi', color: '#ef4444', bg: '#fee2e2' }
                };
                
                const status = statusMap[o.status] || statusMap['pending'];
                
                return `
                    <div class="bz-card" style="margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; align-items:center;">
                            <div>
                                <span style="font-weight:700;">Buyurtma #${o.id}</span>
                                <span style="background:${status.bg}; color:${status.color}; padding:4px 8px; border-radius:6px; font-size:0.75rem; margin-left:8px; font-weight:600;">${status.text}</span>
                            </div>
                            <span style="color:var(--primary); font-weight:700; font-size:1.1rem;">${parseFloat(o.total_amount).toLocaleString()} so'm</span>
                        </div>
                        <p style="font-size:0.85rem; color:#64748b; margin-bottom:4px;">👤 ${o.customer_name}</p>
                        <p style="font-size:0.85rem; color:#64748b; margin-bottom:8px;">📞 ${o.customer_phone || 'Telefon yo\'q'}</p>
                        <p style="font-size:0.9rem; margin-bottom:10px;">📦 ${itemsText}</p>
                        <p style="font-size:0.8rem; color:#94a3b8;">🕐 ${new Date(o.created_at).toLocaleString('uz-UZ')}</p>
                        ${o.status === 'pending' ? `
                            <div style="margin-top:12px; display:flex; gap:8px;">
                                <button onclick="updateOrderStatus(${o.id}, 'preparing')" class="btn-primary" style="flex:1; padding:8px 16px; font-size:0.85rem; background:#3b82f6; border:none; border-radius:8px; color:white; cursor:pointer;">✓ Qabul qilish</button>
                                <button onclick="updateOrderStatus(${o.id}, 'cancelled')" style="flex:1; padding:8px 16px; font-size:0.85rem; border:1px solid #ef4444; background:white; border-radius:8px; color:#ef4444; cursor:pointer;">✕ Bekor qilish</button>
                            </div>
                        ` : ''}
                        ${o.status === 'preparing' ? `
                            <div style="margin-top:12px;">
                                <button onclick="updateOrderStatus(${o.id}, 'ready')" class="btn-primary" style="width:100%; padding:8px 16px; font-size:0.85rem; background:#10b981; border:none; border-radius:8px; color:white; cursor:pointer;">✓ Tayyor</button>
                            </div>
                        ` : ''}
                        ${o.status === 'ready' ? `
                            <div style="margin-top:12px;">
                                <button onclick="updateOrderStatus(${o.id}, 'delivering')" class="btn-primary" style="width:100%; padding:8px 16px; font-size:0.85rem; background:#8b5cf6; border:none; border-radius:8px; color:white; cursor:pointer;">🚚 Yetkazishga yuborish</button>
                            </div>
                        ` : ''}
                        ${o.status === 'delivering' ? `
                            <div style="margin-top:12px;">
                                <button onclick="updateOrderStatus(${o.id}, 'completed')" class="btn-primary" style="width:100%; padding:8px 16px; font-size:0.85rem; background:#10b981; border:none; border-radius:8px; color:white; cursor:pointer;">✓ Yetkazildi</button>
                            </div>
                        ` : ''}
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

window.updateOrderStatus = async function(orderId, status) {
    if (!myBusiness) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=update_order_status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                status: status,
                business_id: myBusiness.id
            })
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            const statusText = {
                'preparing': 'Qabul qilindi',
                'ready': 'Tayyor deb belgilandi',
                'delivering': 'Yetkazishga yuborildi',
                'completed': 'Yakunlandi',
                'cancelled': 'Bekor qilindi'
            };
            tg.showAlert(statusText[status] || 'Yangilandi');
            loadBusinessOrders();
            loadBusinessStats();
        } else {
            tg.showAlert('Xatolik yuz berdi!');
        }
    } catch (e) {
        tg.showAlert('Xatolik!');
    }
};

window.addProduct = async function () {
    const name = prompt("Mahsulot nomi:");
    if (!name) return;
    
    const price = prompt("Narxi (so'm):");
    if (!price) return;
    
    const category = prompt("Kategoriya (masalan: mevalar, sabzavotlar, sut):");
    const desc = prompt("Tavsif (ixtiyoriy):");

    try {
        const response = await fetch(`${API_BASE}?action=add_product`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                business_id: myBusiness.id,
                name: name,
                price: parseFloat(price),
                description: desc || '',
                category: category || '',
                image: ''
            })
        });
        const result = await response.json();

        if (result.status === 'success') {
            tg.showAlert("✅ Mahsulot qo'shildi!");
            loadBusinessProducts();
            loadBusinessStats();
        } else {
            tg.showAlert("❌ Xatolik!");
        }
    } catch (e) {
        tg.showAlert("❌ Xatolik!");
    }
};

window.deleteProduct = async function(productId) {
    if (!confirm('Mahsulotni o\'chirmoqchimisiz?')) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=delete_product`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                business_id: myBusiness.id
            })
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            tg.showAlert('✅ O\'chirildi!');
            loadBusinessProducts();
            loadBusinessStats();
        } else {
            tg.showAlert('❌ Xatolik!');
        }
    } catch (e) {
        tg.showAlert('❌ Xatolik!');
    }
};

window.toggleProductAvailability = async function(productId) {
    try {
        const response = await fetch(`${API_BASE}?action=toggle_product_availability`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                business_id: myBusiness.id
            })
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            loadBusinessProducts();
        } else {
            tg.showAlert('❌ Xatolik!');
        }
    } catch (e) {
        tg.showAlert('❌ Xatolik!');
    }
};

async function loadBusinessProducts() {
    if (!myBusiness) return;
    const list = document.querySelector('.bz-product-list');
    list.innerHTML = 'Yuklanmoqda...';

    try {
        const response = await fetch(`${API_BASE}?action=get_products&business_id=${myBusiness.id}`);
        const products = await response.json();

        if (products && products.length > 0) {
            list.innerHTML = products.map(p => `
                <div class="bz-card" style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; ${p.is_available == 0 ? 'opacity:0.5;' : ''}">
                    <div style="flex:1;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            <h5 style="margin:0">${p.name}</h5>
                            ${p.category ? `<span style="background:#e0f2fe; color:#0284c7; padding:2px 8px; border-radius:6px; font-size:0.7rem;">${p.category}</span>` : ''}
                            ${p.is_available == 0 ? '<span style="background:#fee2e2; color:#ef4444; padding:2px 8px; border-radius:6px; font-size:0.7rem;">Mavjud emas</span>' : ''}
                        </div>
                        <p style="margin:0; font-size:0.8rem; color:#64748b;">${p.description || 'Tavsif yo\'q'}</p>
                        <p style="margin:4px 0 0 0; font-size:0.9rem; font-weight:700; color:var(--primary);">${parseFloat(p.price).toLocaleString()} so'm</p>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button onclick="toggleProductAvailability(${p.id})" style="border:none; background:#f1f5f9; color:#64748b; padding:8px 12px; border-radius:8px; cursor:pointer; font-size:0.8rem;" title="${p.is_available == 1 ? 'Mavjud emas deb belgilash' : 'Mavjud deb belgilash'}">
                            ${p.is_available == 1 ? '👁️' : '🚫'}
                        </button>
                        <button onclick="deleteProduct(${p.id})" style="border:none; background:#fee2e2; color:#ef4444; padding:8px 12px; border-radius:8px; cursor:pointer; font-size:0.8rem;">🗑️</button>
                    </div>
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
        if (targetId === 'bz-settings') loadBusinessSettings();
    });
});

async function loadBusinessSettings() {
    if (!myBusiness) return;
    
    // Set current values
    const openSwitch = document.querySelector('#bz-settings input[type="checkbox"]');
    if (openSwitch) {
        openSwitch.checked = myBusiness.is_open == 1;
        openSwitch.onchange = async function() {
            await updateBusinessSettings({ is_open: this.checked ? 1 : 0 });
        };
    }
    
    const deliveryInput = document.querySelector('#bz-settings input[type="number"]');
    if (deliveryInput) {
        deliveryInput.value = myBusiness.delivery_price || 0;
        deliveryInput.onchange = async function() {
            await updateBusinessSettings({ delivery_price: this.value });
        };
    }
}

async function updateBusinessSettings(settings) {
    if (!myBusiness) return;
    
    try {
        const response = await fetch(`${API_BASE}?action=update_business_settings`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                business_id: myBusiness.id,
                ...settings
            })
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            // Update local business object
            Object.assign(myBusiness, settings);
            tg.showAlert('✅ Saqlandi!');
        } else {
            tg.showAlert('❌ Xatolik!');
        }
    } catch (e) {
        tg.showAlert('❌ Xatolik!');
    }
}

// Hook into navigation
const bizOriginalSwitchTab = window.switchTab;
window.switchTab = function (id) {
    if (id === 'biznes') {
        checkMyBusiness();
    }
    if (bizOriginalSwitchTab) bizOriginalSwitchTab(id);
};
