/* ========================================
   MAHALLA BIZNES - Biznes Dashboard logikasi
   ======================================== */

// Pseudo-storage for local biznes state
let userBiznes = null;

document.addEventListener('DOMContentLoaded', () => {
    // Check if user already created business in this session
    const storedBiznes = localStorage.getItem('mahalla_my_biznes');
    if (storedBiznes) {
        userBiznes = JSON.parse(storedBiznes);
        showBiznesDashboard();
    }
});

// Create business
window.createBiznes = function () {
    const name = document.getElementById('biz-name').value.trim();
    const catSelect = document.getElementById('biz-category');
    const catRaw = catSelect.value;
    const catLabel = catSelect.options[catSelect.selectedIndex].text;

    if (!name) {
        tg.showAlert("Iltimos, biznesingiz nomini kiriting!");
        return;
    }

    userBiznes = {
        name: name,
        category: catRaw,
        categoryLabel: catLabel,
        logoInitial: name.charAt(0).toUpperCase()
    };

    localStorage.setItem('mahalla_my_biznes', JSON.stringify(userBiznes));

    tg.showAlert("Tabriklaymiz! Biznesingiz yaratildi 🎉");
    showBiznesDashboard();
};

function showBiznesDashboard() {
    if (!userBiznes) return;

    // Hide create view, show dashboard view
    document.getElementById('biznes-create-view').classList.add('hidden');
    document.getElementById('biznes-dashboard-view').classList.remove('hidden');

    // Populate dashboard details
    document.getElementById('biz-dash-logo').textContent = userBiznes.logoInitial;
    document.getElementById('biz-dash-name').textContent = userBiznes.name;
    document.getElementById('biz-dash-cat').textContent = userBiznes.categoryLabel;
}

// Biznes Tabs Navigation
document.querySelectorAll('.bz-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        // Remove active from all tabs
        document.querySelectorAll('.bz-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Hide all contents
        document.querySelectorAll('.bz-tab-content').forEach(c => c.classList.add('hidden'));

        // Show correct content
        const targetId = tab.dataset.target;
        document.getElementById(targetId).classList.remove('hidden');
    });
});
