document.addEventListener('DOMContentLoaded', () => {
  const selectedIds = new Set();
  const tableBody   = document.querySelector('#table-history tbody');

  function updateDeleteButton() {
    document.getElementById('btn-history-delete').disabled = selectedIds.size === 0;
  }

  function getAllRows() {
    return Array.from(tableBody.querySelectorAll('tr[data-id]'));
  }

  tableBody.addEventListener('change', function (e) {
    if (!e.target.classList.contains('row-select')) return;
    const tr = e.target.closest('tr');
    const id = tr.dataset.id;
    if (e.target.checked) {
      selectedIds.add(id);
      tr.classList.add('table-active');
    } else {
      selectedIds.delete(id);
      tr.classList.remove('table-active');
    }
    const allRows    = getAllRows();
    const selectAll  = document.getElementById('history-select-all');
    if (selectAll) {
      const n = allRows.filter((r) => selectedIds.has(r.dataset.id)).length;
      selectAll.checked       = n > 0 && n === allRows.length;
      selectAll.indeterminate = n > 0 && n < allRows.length;
    }
    updateDeleteButton();
  });

  document.querySelector('#table-history thead').addEventListener('change', function (e) {
    if (e.target.id !== 'history-select-all') return;
    getAllRows().forEach((tr) => {
      const checkbox = tr.querySelector('.row-select');
      if (e.target.checked) {
        selectedIds.add(tr.dataset.id);
        if (checkbox) checkbox.checked = true;
        tr.classList.add('table-active');
      } else {
        selectedIds.delete(tr.dataset.id);
        if (checkbox) checkbox.checked = false;
        tr.classList.remove('table-active');
      }
    });
    updateDeleteButton();
  });

  const deleteModalEl = document.getElementById('modal-history-delete-confirm');
  const deleteModal   = new bootstrap.Modal(deleteModalEl, { focus: false });

  deleteModalEl.addEventListener('shown.bs.modal', function () {
    const closeBtn = deleteModalEl.querySelector('.btn-close');
    if (closeBtn) closeBtn.focus();
  });

  deleteModalEl.addEventListener('hide.bs.modal', function () {
    const focused = deleteModalEl.querySelector(':focus');
    if (focused) focused.blur();
    const btn = document.getElementById('btn-history-delete');
    if (btn && !btn.disabled) btn.focus();
  });

  document.getElementById('btn-history-delete').addEventListener('click', function () {
    document.getElementById('history-delete-modal-count').textContent = selectedIds.size;
    deleteModal.show();
  });

  document.getElementById('btn-history-delete-confirm').addEventListener('click', function () {
    const ids = Array.from(selectedIds);
    fetch('/admin/startpage/history/delete', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ ids }),
    })
      .then((res) => res.json())
      .then(() => {
        deleteModal.hide();
        ids.forEach((id) => {
          const tr = tableBody.querySelector(`tr[data-id="${id}"]`);
          if (tr) tr.remove();
          selectedIds.delete(id);
        });
        const selectAll = document.getElementById('history-select-all');
        if (selectAll) {
          selectAll.checked       = false;
          selectAll.indeterminate = false;
        }
        updateDeleteButton();
      })
      .catch((err) => console.error('Delete failed:', err));
  });
});
