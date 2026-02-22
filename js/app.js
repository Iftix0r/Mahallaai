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
        'food': 'food-app'
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
