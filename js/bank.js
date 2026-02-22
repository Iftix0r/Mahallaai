/* ========================================
   MAHALLA BANK LOGIC
   ======================================== */

let userCards = [
    { id: 1, number: '8600 50** **** 1234', exp: '12/26', name: 'Alisher Navoiy', balance: 1450000, color: 'style-1' }
];

const cardsCarousel = document.getElementById('user-cards-carousel');
const addCardModal = document.getElementById('add-bank-card-modal');
const transferModal = document.getElementById('transfer-modal');

// Format card number with spaces
document.getElementById('bank-card-num').addEventListener('input', function (e) {
    let val = this.value.replace(/\D/g, '');
    let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
    this.value = formatted;
});

// Format exp date
document.getElementById('bank-card-exp').addEventListener('input', function (e) {
    let val = this.value.replace(/\D/g, '');
    if (val.length > 2) val = val.slice(0, 2) + '/' + val.slice(2, 4);
    this.value = val;
});

function renderCards() {
    if (!cardsCarousel) return;
    cardsCarousel.innerHTML = '';

    if (userCards.length === 0) {
        cardsCarousel.innerHTML = `
            <div class="add-card-empty" onclick="openBankCardModal()">
                <i>💳</i>
                <span>Birinchi kartangizni qo'shing</span>
            </div>
        `;
        return;
    }

    userCards.forEach((card, idx) => {
        const bgClass = ['style-1', 'style-2', 'style-3'][idx % 3];
        const cardHtml = `
            <div class="bank-card-item ${bgClass}">
                <div class="card-decor"></div>
                <div class="card-balance"><span>Summa</span><br>${card.balance.toLocaleString()} so'm</div>
                <div class="card-num">${card.number}</div>
                <div class="card-bottom">
                    <span>${card.name}</span>
                    <span>${card.exp}</span>
                </div>
            </div>
        `;
        cardsCarousel.innerHTML += cardHtml;
    });

    // Add new card placeholder
    cardsCarousel.innerHTML += `
        <div class="add-card-empty" style="min-width: 150px; padding: 20px;" onclick="openBankCardModal()">
            <i style="color:var(--primary);">+</i>
            <span style="font-weight:600; color:var(--text); font-size:0.8rem;">Qo'shish</span>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', () => {
    // Check if cards saved
    const saved = localStorage.getItem('mahalla_bank_cards');
    if (saved) {
        userCards = JSON.parse(saved);
    }
    renderCards();
});

// Call render directly since JS might be loaded after DOM
renderCards();

// Modals
window.openBankCardModal = function () {
    addCardModal.classList.remove('hidden');
};

window.closeBankCardModal = function () {
    addCardModal.classList.add('hidden');
};

window.submitBankCard = function () {
    const num = document.getElementById('bank-card-num').value.trim();
    const exp = document.getElementById('bank-card-exp').value.trim();
    const name = document.getElementById('bank-card-name').value.trim().toUpperCase();

    if (num.length < 19 || exp.length < 5 || !name) {
        tg.showAlert("Karta ma'lumotlarini to'g'ri to'ldiring!");
        return;
    }

    // Mask the number
    const masked = '8600 **** **** ' + num.slice(-4);

    userCards.unshift({
        id: Date.now(),
        number: masked,
        exp: exp,
        name: name,
        balance: Math.floor(Math.random() * 5000000) + 1000000 // random balance for demo
    });

    localStorage.setItem('mahalla_bank_cards', JSON.stringify(userCards));
    renderCards();
    closeBankCardModal();

    // Clear form
    document.getElementById('bank-card-num').value = '';
    document.getElementById('bank-card-exp').value = '';
    document.getElementById('bank-card-name').value = '';

    tg.showAlert("Karta muvaffaqiyatli ulandi! ✅");
};

// Transfer logic
window.openTransferModal = function () {
    if (userCards.length === 0) {
        tg.showAlert("Sizda ulangan karta yo'q. Dastlab karta qo'shing.");
        return;
    }
    transferModal.classList.remove('hidden');
};

window.closeTransferModal = function () {
    transferModal.classList.add('hidden');
};

window.submitTransfer = function () {
    const to = document.getElementById('tf-card-num').value.trim();
    const amount = parseInt(document.getElementById('tf-amount').value);

    if (!to || !amount) {
        tg.showAlert("Barcha maydonlarni to'ldiring!");
        return;
    }

    if (amount > userCards[0].balance) {
        tg.showAlert("Kartangizda mablag' yetarli emas!");
        return;
    }

    // Subtract from primary card
    userCards[0].balance -= amount;
    localStorage.setItem('mahalla_bank_cards', JSON.stringify(userCards));
    renderCards();
    closeTransferModal();

    document.getElementById('tf-card-num').value = '';
    document.getElementById('tf-amount').value = '';

    tg.showAlert(`✅ O'tkazma yakunlandi!\n${amount.toLocaleString()} so'm yuborildi.`);
};
