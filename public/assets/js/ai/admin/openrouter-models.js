'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const searchInput      = document.getElementById('model-search');
    if (!searchInput) return;

    const rows             = document.querySelectorAll('.model-row');
    const noResults        = document.getElementById('no-results');
    const selectedCount    = document.getElementById('selected-count');
    const checkboxes       = document.querySelectorAll('.model-checkbox');
    const showSelectedBtn  = document.getElementById('show-selected-btn');
    let selectedOnly       = false;

    function updateCount() {
        const n = document.querySelectorAll('.model-checkbox:checked').length;
        selectedCount.textContent = n + ' selected';
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            updateCount();
            applyFilters();
        });
    });

    function applyFilters() {
        const q     = searchInput.value.trim().toLowerCase();
        let visible = 0;
        rows.forEach(function (row) {
            const cb         = row.querySelector('.model-checkbox');
            const textMatch  = !q || row.dataset.id.toLowerCase().includes(q);
            const selMatch   = !selectedOnly || (cb && cb.checked);
            const match      = textMatch && selMatch;
            row.classList.toggle('d-none', !match);
            row.classList.toggle('d-flex', match);
            if (match) visible++;
        });
        noResults.style.display = visible === 0 ? '' : 'none';
    }

    searchInput.addEventListener('input', applyFilters);

    showSelectedBtn.addEventListener('click', function () {
        selectedOnly = !selectedOnly;
        showSelectedBtn.classList.toggle('btn-outline-secondary', !selectedOnly);
        showSelectedBtn.classList.toggle('btn-primary', selectedOnly);
        applyFilters();
    });
});
