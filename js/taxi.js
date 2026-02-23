/* ========================================
   TAXI APP - Mahalla Taxi logikasi
   ======================================== */

// Taxi car selection
document.querySelectorAll('.car-type').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.car-type').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
    });
});

window.orderTaxi = async function () {
    const to = document.getElementById('taxi-to').value.trim();
    if (!to) {
        tg.showAlert("Iltimos, boradigan manzilingizni kiriting!");
        return;
    }
    const activeType = document.querySelector('.car-type.active h5').textContent;
    const priceText = document.querySelector('.car-type.active .car-price').textContent;
    const price = parseInt(priceText.replace(/\s/g, ''));

    const paid = await window.payWithBalance(price);
    if (paid) {
        tg.showAlert(`${activeType} taxi chaqirildi!\nManzil: ${to}\nNarx: ${price.toLocaleString()} so'm\nHisobingizdan yechildi. Haydovchi 3-5 daqiqada yetib keladi.`);
    }
};
