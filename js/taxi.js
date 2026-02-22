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

window.orderTaxi = function () {
    const to = document.getElementById('taxi-to').value.trim();
    if (!to) {
        tg.showAlert("Iltimos, boradigan manzilingizni kiriting!");
        return;
    }
    const activeType = document.querySelector('.car-type.active h5').textContent;
    const price = document.querySelector('.car-type.active .car-price').textContent;
    tg.showAlert(`${activeType} taxi chaqirildi!\nNarx: ${price} so'm\nHaydovchi 3-5 daqiqada yetib keladi.`);
};
