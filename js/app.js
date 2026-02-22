const tg = window.Telegram.WebApp;
const API_BASE = 'api/index.php';

tg.expand();
tg.ready();

// Initial Setup
const userName = document.getElementById('user-name');
const userAvatar = document.getElementById('user-avatar');
const userRegion = document.getElementById('user-region');
const profileModal = document.getElementById('profile-modal');

let currentUser = null;

async function init() {
    if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        const tgUser = tg.initDataUnsafe.user;

        try {
            // Fetch user from DB with cache buster
            const response = await fetch(`${API_BASE}?action=get_user&telegram_id=${tgUser.id}&v=${Date.now()}`);
            const dbUser = await response.json();
            console.log("DB User:", dbUser);

            if (dbUser && !dbUser.error) {
                currentUser = dbUser;
                updateUI(dbUser);
            } else {
                console.log("User not in DB or error:", dbUser.error);
                // Fallback to TG data
                userName.textContent = tgUser.first_name + (tgUser.last_name ? ' ' + tgUser.last_name : '');
                userAvatar.textContent = tgUser.first_name.charAt(0);
            }
        } catch (e) {
            console.error("Data fetch error:", e);
        }
    }

    // Fade out loading screen to selection screen or specific tab
    setTimeout(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');

        if (tab === 'system') {
            fadeOutIn('loading', 'dashboard');
        } else if (tab === 'food') {
            fadeOutIn('loading', 'food-app');
        } else if (tab === 'taxi') {
            fadeOutIn('loading', 'taxi-app');
        } else if (tab === 'market') {
            fadeOutIn('loading', 'market-app');
        } else {
            fadeOutIn('loading', 'selection-screen');
        }
    }, 1500);

    loadNews();
}

async function loadNews() {
    try {
        const response = await fetch(`${API_BASE}?action=get_news`);
        const news = await response.json();
        const newsList = document.querySelector('.news-list');

        if (news && news.length > 0) {
            newsList.innerHTML = '';
            news.forEach(item => {
                const newsItem = document.createElement('div');
                newsItem.className = 'news-item';
                newsItem.innerHTML = `
                    <div class="news-thumb" style="background-image: url('${item.image}'); background-size: cover;"></div>
                    <div class="news-info">
                        <h5>${item.title}</h5>
                        <p>${item.content}</p>
                    </div>
                `;
                newsList.appendChild(newsItem);
            });
        }
    } catch (e) {
        console.error("News load error:", e);
    }
}

function updateUI(user) {
    if (!user) return;
    userName.textContent = user.fullname || "Foydalanuvchi";
    userAvatar.textContent = (user.fullname || "M").charAt(0);
    if (user.region || user.mahalla) {
        userRegion.textContent = `${user.region || ''}, ${user.mahalla || ''} mah.`;
        document.getElementById('food-user-location').textContent = `${user.mahalla || ''} mahalla`;
    } else {
        userRegion.textContent = "Hudud belgilanmagan";
    }
}

// Profile Save Logic
document.getElementById('save-profile').addEventListener('click', async () => {
    const region = document.getElementById('setup-region').value;
    const mahalla = document.getElementById('setup-mahalla').value;

    if (!region || !mahalla) {
        tg.showAlert("Iltimos, barcha maydonlarni to'ldiring!");
        return;
    }

    const tgUser = tg.initDataUnsafe.user;
    try {
        const response = await fetch(`${API_BASE}?action=update_profile`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                telegram_id: tgUser.id,
                fullname: userName.textContent,
                region: region,
                mahalla: mahalla
            })
        });
        const result = await response.json();
        console.log("Save result:", result);

        if (result.status === 'success') {
            sessionStorage.setItem("profileFilled", "true");
            profileModal.classList.add('hidden');
            tg.showAlert("Profil yangilandi! ✅");

            // Update local state
            if (currentUser) {
                currentUser.region = region;
                currentUser.mahalla = mahalla;
            }

            // Refresh UI
            userRegion.textContent = `${region}, ${mahalla} mah.`;
            document.getElementById('food-user-location').textContent = `${mahalla} mahalla`;
        } else {
            tg.showAlert("Xatolik yuz berdi: " + result.message);
        }
    } catch (e) {
        tg.showAlert("Server bilan aloqa uzildi.");
    }
});

init();

function switchTab(tabId) {
    const screens = {
        'menu': 'selection-screen',
        'system': 'dashboard',
        'chat': 'ai-chat',
        'food': 'food-app',
        'taxi': 'taxi-app',
        'market': 'market-app'
    };

    const targetScreenId = screens[tabId];
    if (!targetScreenId) return;

    // Get current visible screen
    const currentScreen = document.querySelector('.screen:not(.hidden)');
    if (currentScreen && currentScreen.id === targetScreenId) return;

    fadeOutIn(currentScreen ? currentScreen.id : null, targetScreenId);

    // Update Nav (only for dashboard tabs)
    if (tabId === 'system' || tabId === 'chat') {
        document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
        if (tabId === 'system') document.querySelector('.nav-item:nth-child(1)').classList.add('active');
        if (tabId === 'chat') document.querySelector('.nav-item:nth-child(3)').classList.add('active');
    }
}

function fadeOutIn(fromId, toId) {
    if (fromId) {
        const fromEl = document.getElementById(fromId);
        fromEl.style.opacity = '0';
        fromEl.style.transform = 'translateY(10px)';
        setTimeout(() => {
            fromEl.classList.add('hidden');
            showTo();
        }, 400);
    } else {
        showTo();
    }

    function showTo() {
        const toEl = document.getElementById(toId);
        toEl.classList.remove('hidden');
        // Force reflow
        toEl.offsetHeight;
        toEl.style.opacity = '1';
        toEl.style.transform = 'translateY(0)';
    }
}

window.closeProfileModal = function () {
    profileModal.classList.add('hidden');
};

function openService(service) {
    if (service === 'profile') {
        profileModal.classList.remove('hidden');
        return;
    }

    tg.MainButton.setText("Xizmatni tanlash: " + service);
    tg.MainButton.show();

    tg.onEvent('mainButtonClicked', function () {
        tg.showAlert("Qidirilmoqda...");
    });
}

// Chat logic
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const chatMessages = document.getElementById('chat-messages');

sendBtn.addEventListener('click', () => {
    const text = chatInput.value.trim();
    if (text) {
        addMessage(text, 'user');
        chatInput.value = '';

        // AI Response simulation
        setTimeout(() => {
            const responses = [
                "Assalomu alaykum! Men sizga mahalla xizmatlari yoki arizalar bo'yicha yordam bera olaman. Sizga qaysi turdagi xizmat kerak?",
                "Tushundim, ma'lumotnoma olish uchun 'Ma'lumotnoma' bo'limiga o'tishingiz mumkin. Yana qanday savollaringiz bor?",
                "Hozirda tizimimiz orqali barcha turdagi ijtimoiy yordam arizalarini masofadan yuborish imkoniyati mavjud."
            ];
            const randomMsg = responses[Math.floor(Math.random() * responses.length)];
            addMessage(randomMsg, 'ai');
        }, 1000);
    }
});

function addMessage(text, sender) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `message ${sender}`;
    msgDiv.innerHTML = `<p>${text}</p>`;
    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Taxi logic
document.querySelectorAll('.car-type').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.car-type').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
    });
});

function orderTaxi() {
    const to = document.getElementById('taxi-to').value.trim();
    if (!to) {
        tg.showAlert("Iltimos, boradigan manzilingizni kiriting!");
        return;
    }
    const activeType = document.querySelector('.car-type.active h5').textContent;
    const price = document.querySelector('.car-type.active .car-price').textContent;
    tg.showAlert(`${activeType} taxi chaqirildi!\nNarx: ${price} so'm\nHaydovchi 3-5 daqiqada yetib keladi.`);
}

// ========== MARKET LOGIC ==========
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
function addToMarketCart(btn) {
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
}

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

function removeFromCart(idx) {
    marketCart[idx].qty--;
    if (marketCart[idx].qty <= 0) marketCart.splice(idx, 1);
    updateCartUI();
}

function openMarketCart() {
    document.getElementById('market-cart-modal').classList.remove('hidden');
}

function closeMarketCart() {
    document.getElementById('market-cart-modal').classList.add('hidden');
}

function placeMarketOrder() {
    if (marketCart.length === 0) {
        tg.showAlert("Savatingiz bo'sh!");
        return;
    }
    const total = marketCart.reduce((s, i) => s + i.price * i.qty, 0);
    tg.showAlert(`Buyurtma qabul qilindi! ✅\nJami: ${total.toLocaleString()} so'm\nYetkazib berish: 30-45 daqiqa`);
    marketCart = [];
    updateCartUI();
    closeMarketCart();
}
