/* ========================================
   MAHALLA ISH - Ish e'lonlari logikasi
   ======================================== */

// Dummy data for jobs
let jobsData = [
    { id: 1, title: 'Malakali g\'isht teruvchi', cat: 'qurilish', salary: '5 000 000 - 8 000 000', phone: '+998901234567', urgent: true, date: 'Bugun' },
    { id: 2, title: 'Do\'konga sotuvchi kerak', cat: 'savdo', salary: '3 000 000 - 4 000 000', phone: '+998997654321', urgent: false, date: 'Kecha' },
    { id: 3, title: 'Santexnika ustasi', cat: 'xizmat', salary: 'Kelishilgan', phone: '+998931112233', urgent: true, date: 'Bugun' },
    { id: 4, title: 'O\'quv markaziga ofis menejer', cat: 'it', salary: '2 500 000 so\'m', phone: '+998970001122', urgent: false, date: '2 kun oldin' }
];

const jobsList = document.getElementById('jobs-list');
const ishSearch = document.getElementById('ish-search');

// Render Jobs
function renderJobs(filterCat = 'all', searchQuery = '') {
    if (!jobsList) return;
    jobsList.innerHTML = '';

    let filtered = jobsData.filter(job => {
        let matchCat = filterCat === 'all' || job.cat === filterCat;
        let matchSearch = job.title.toLowerCase().includes(searchQuery.toLowerCase());
        return matchCat && matchSearch;
    });

    if (filtered.length === 0) {
        jobsList.innerHTML = '<p style="text-align:center; color:#64748b; padding:20px;">Hech narsa topilmadi</p>';
        return;
    }

    filtered.forEach(job => {
        let tagClass = job.urgent ? 'urgent' : '';
        let tagText = job.urgent ? 'Shoshilinch' : job.date;

        let card = document.createElement('div');
        card.className = 'job-card';
        card.innerHTML = `
            <div class="job-tag ${tagClass}">${tagText}</div>
            <h4>${job.title}</h4>
            <div class="job-salary">${job.salary} so'm</div>
            <div class="job-details">
                <div class="job-detail-item">📞 ${job.phone}</div>
                <div class="job-detail-item">📍 O'z mahallangiz hududida</div>
            </div>
            <a href="tel:${job.phone.replace(/\s+/g, '')}" style="text-decoration:none;">
                <button class="job-btn">Bog'lanish</button>
            </a>
        `;
        jobsList.appendChild(card);
    });
}

// Initial render
document.addEventListener('DOMContentLoaded', () => {
    renderJobs();
});
// Since the script runs after load often in SPAs, let's call it immediately
renderJobs();

// Category filter
document.querySelectorAll('.ish-categories .cat-item').forEach(cat => {
    cat.addEventListener('click', () => {
        document.querySelectorAll('.ish-categories .cat-item').forEach(c => c.classList.remove('active'));
        cat.classList.add('active');
        const selected = cat.dataset.cat;
        renderJobs(selected, ishSearch.value);
    });
});

// Search
if (ishSearch) {
    ishSearch.addEventListener('input', function () {
        const activeCat = document.querySelector('.ish-categories .cat-item.active').dataset.cat;
        renderJobs(activeCat, this.value);
    });
}

// Modal Logic
window.openAddJobModal = function () {
    document.getElementById('add-job-modal').classList.remove('hidden');
};

window.closeAddJobModal = function () {
    document.getElementById('add-job-modal').classList.add('hidden');
};

window.submitJob = function () {
    const title = document.getElementById('job-title').value.trim();
    const cat = document.getElementById('job-cat').value;
    const salary = document.getElementById('job-salary').value.trim();
    const phone = document.getElementById('job-phone').value.trim();

    if (!title || !salary || !phone) {
        tg.showAlert("Barcha maydonlarni to'ldiring!");
        return;
    }

    const newJob = {
        id: Date.now(),
        title,
        cat,
        salary,
        phone,
        urgent: false,
        date: 'Hozir'
    };

    jobsData.unshift(newJob);
    tg.showAlert("E'loningiz muvaffaqiyatli qo'shildi! ✅");
    closeAddJobModal();

    // Clear form
    document.getElementById('job-title').value = '';
    document.getElementById('job-salary').value = '';
    document.getElementById('job-phone').value = '';

    // Re-render
    const activeCat = document.querySelector('.ish-categories .cat-item.active').dataset.cat;
    renderJobs(activeCat, ishSearch.value);
};
