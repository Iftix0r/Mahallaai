/* ========================================
   FOOD APP - Mahalla Fast Food logikasi
   ======================================== */

let foodCartCount = 0;

document.querySelectorAll('.cat-item').forEach(cat => {
    cat.addEventListener('click', () => {
        document.querySelectorAll('.cat-item').forEach(c => c.classList.remove('active'));
        cat.classList.add('active');
        // Filter logic here if needed
    });
});

document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function (e) {
        foodCartCount++;
        document.getElementById('food-cart-count').textContent = foodCartCount;

        // Simple animation
        this.style.transform = 'scale(0.9)';
        this.style.background = '#10b981';
        this.style.color = 'white';
        this.textContent = '✓';

        setTimeout(() => {
            this.style.transform = 'scale(1)';
            this.style.background = 'var(--surface)';
            this.style.color = '';
            this.textContent = '+';
        }, 500);
    });
});
