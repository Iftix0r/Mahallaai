/* ========================================
   MAHALLA BANK LOGIC
   ======================================== */

let bankBalance = 1500000;
let bankTransactions = [
    { title: 'Elektr energiyasi', amount: '-150 000', type: 'out', date: 'Bugun, 10:30', icon: '💡' },
    { title: 'Uzcard-dan to\'ldirish', amount: '+500 000', type: 'in', date: 'Kecha, 18:15', icon: '💳' },
    { title: 'Mahalla Market', amount: '-45 000', type: 'out', date: '2 kun oldin, 14:00', icon: '🛒' }
];

// Initialize UI
function updateBankUI() {
    document.getElementById('bank-balance-display').textContent = bankBalance.toLocaleString('ru-RU');
    renderTransactions();
}

function renderTransactions() {
    const list = document.getElementById('bank-transactions-list');
    if (!list) return;
    list.innerHTML = '';

    bankTransactions.forEach(t => {
        const item = document.createElement('div');
        item.className = 'transaction-item';

        const iconClass = t.type === 'in' ? 'in' : 'out';
        const sign = t.type === 'in' ? '+' : '';
        item.innerHTML = `
            <div class="t-icon ${iconClass}">
                ${t.icon}
            </div>
            <div class="t-info">
                <div class="t-title">${t.title}</div>
                <div class="t-date">${t.date}</div>
            </div>
            <div class="t-amount ${iconClass}">${t.amount} s'om</div>
        `;
        list.appendChild(item);
    });
}

// Modals
window.openTransferModal = function () {
    document.getElementById('bank-transfer-modal').classList.remove('hidden');
};

window.closeTransferModal = function () {
    document.getElementById('bank-transfer-modal').classList.add('hidden');
    document.getElementById('transfer-card-input').value = '';
    document.getElementById('transfer-amount-input').value = '';
};

window.processTransfer = function () {
    const card = document.getElementById('transfer-card-input').value.trim();
    const amountStr = document.getElementById('transfer-amount-input').value.trim();
    const amountNum = parseInt(amountStr.replace(/\s+/g, ''));

    if (card.length < 16) {
        tg.showAlert('Karta raqami noto\'g\'ri (kamida 16 ta raqam)');
        return;
    }

    if (isNaN(amountNum) || amountNum <= 0) {
        tg.showAlert('O\'tkazma summasi xato kiritildi');
        return;
    }

    if (amountNum > bankBalance) {
        tg.showAlert('Hisobingizda mablag\' yetarli emas');
        return;
    }

    // Process deduct
    bankBalance -= amountNum;

    // Add to transactions
    bankTransactions.unshift({
        title: `Karta ${card.substring(0, 4)}...${card.substring(12)}`,
        amount: `-${amountNum.toLocaleString('ru-RU')}`,
        type: 'out',
        date: 'Hozir',
        icon: '🔄'
    });

    updateBankUI();
    closeTransferModal();

    tg.showAlert(`Muvaffaqiyatli! ✅\n${amountNum.toLocaleString('ru-RU')} so'm o'tkazildi.`);
};

// Initial Call
document.addEventListener('DOMContentLoaded', updateBankUI);

// Trigger updates when tab switching back is needed
const originalSwitchTab = window.switchTab;
window.switchTab = function (tabId) {
    if (tabId === 'bank') {
        updateBankUI();
    }
    if (originalSwitchTab) {
        originalSwitchTab(tabId);
    }
};
