/* ========================================
   CORE - Asosiy logika, navigatsiya, init
   ======================================== */

const tg = window.Telegram.WebApp;
const API_BASE = 'api/index.php';

tg.expand();
tg.ready();

// DOM Elements
const userName = document.getElementById('user-name');
const userAvatar = document.getElementById('user-avatar');
const userRegion = document.getElementById('user-region');
const profileModal = document.getElementById('profile-modal');

let currentUser = null;

// ===== INIT =====
async function init() {
    if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        const tgUser = tg.initDataUnsafe.user;

        try {
            const response = await fetch(`${API_BASE}?action=get_user&telegram_id=${tgUser.id}&v=${Date.now()}`);
            const dbUser = await response.json();
            console.log("DB User:", dbUser);

            if (dbUser && !dbUser.error) {
                currentUser = dbUser;
                updateUI(dbUser);
            } else {
                userName.textContent = tgUser.first_name + (tgUser.last_name ? ' ' + tgUser.last_name : '');
                userAvatar.textContent = tgUser.first_name.charAt(0);
            }
        } catch (e) {
            console.error("Data fetch error:", e);
        }
    }

    // Route to correct screen
    setTimeout(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');

        const tabMap = {
            'system': 'dashboard',
            'food': 'food-app',
            'taxi': 'taxi-app',
            'market': 'market-app'
        };

        const target = tabMap[tab] || 'selection-screen';
        fadeOutIn('loading', target);
    }, 1500);

    loadNews();
}

// ===== NEWS =====
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

// ===== UI UPDATE =====
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

// ===== PROFILE =====
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
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                telegram_id: tgUser.id,
                fullname: userName.textContent,
                region: region,
                mahalla: mahalla
            })
        });
        const result = await response.json();

        if (result.status === 'success') {
            sessionStorage.setItem("profileFilled", "true");
            profileModal.classList.add('hidden');
            tg.showAlert("Profil yangilandi! ✅");

            if (currentUser) {
                currentUser.region = region;
                currentUser.mahalla = mahalla;
            }
            userRegion.textContent = `${region}, ${mahalla} mah.`;
            document.getElementById('food-user-location').textContent = `${mahalla} mahalla`;
        } else {
            tg.showAlert("Xatolik yuz berdi: " + result.message);
        }
    } catch (e) {
        tg.showAlert("Server bilan aloqa uzildi.");
    }
});

// ===== NAVIGATION =====
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

    const currentScreen = document.querySelector('.screen:not(.hidden)');
    if (currentScreen && currentScreen.id === targetScreenId) return;

    fadeOutIn(currentScreen ? currentScreen.id : null, targetScreenId);

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

// ===== GLOBAL SEARCH (Selection Screen) =====
const globalSearch = document.getElementById('global-search');
if (globalSearch) {
    globalSearch.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const cards = document.querySelectorAll('#main-services-grid .selection-card');

        cards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const desc = card.querySelector('p').textContent.toLowerCase();

            if (title.includes(query) || desc.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

// Start!
init();
