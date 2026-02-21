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

    // Fade out loading screen
    setTimeout(() => {
        fadeOutIn('loading', 'dashboard');
    }, 1500);
}

function updateUI(user) {
    if (!user) return;
    userName.textContent = user.fullname || "Foydalanuvchi";
    userAvatar.textContent = (user.fullname || "M").charAt(0);
    if (user.region || user.mahalla) {
        userRegion.textContent = `${user.region || ''}, ${user.mahalla || ''} mah.`;
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
        'home': 'dashboard',
        'chat': 'ai-chat'
    };

    const targetScreenId = screens[tabId];
    if (!targetScreenId) return;

    // Get current visible screen
    const currentScreen = document.querySelector('.screen:not(.hidden)');
    if (currentScreen && currentScreen.id === targetScreenId) return;

    fadeOutIn(currentScreen ? currentScreen.id : null, targetScreenId);

    // Update Nav
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    if (tabId === 'home') document.querySelector('.nav-item:nth-child(1)').classList.add('active');
    if (tabId === 'chat') document.querySelector('.nav-item:nth-child(3)').classList.add('active');
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
