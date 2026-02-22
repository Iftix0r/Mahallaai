/* ========================================
   CHAT - AI Chat logikasi
   ======================================== */

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
