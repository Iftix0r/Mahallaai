const tg = window.Telegram.WebApp;

tg.expand();
tg.ready();

// Initial Setup
const userName = document.getElementById('user-name');
const userAvatar = document.getElementById('user-avatar');

if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
    const user = tg.initDataUnsafe.user;
    userName.textContent = user.first_name + (user.last_name ? ' ' + user.last_name : '');
    userAvatar.textContent = user.first_name.charAt(0);
}

// Loading Simulation
setTimeout(() => {
    fadeOutIn('loading', 'dashboard');
}, 2200);

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
