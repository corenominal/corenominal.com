document.addEventListener('DOMContentLoaded', () => {

  // ── Form modal ─────────────────────────────────────────────────────────────
  const formModalEl = document.getElementById('modal-redirects-form');
  const formModal   = new bootstrap.Modal(formModalEl, { focus: false });

  formModalEl.addEventListener('shown.bs.modal', () => {
    document.getElementById('redirects-form-phrase').focus();
  });

  formModalEl.addEventListener('hide.bs.modal', () => {
    const focused = formModalEl.querySelector(':focus');
    if (focused) focused.blur();
  });

  function openAddModal() {
    document.getElementById('modal-redirects-form-label').textContent = 'New Redirect';
    document.getElementById('redirects-form-id').value       = '';
    document.getElementById('redirects-form-phrase').value   = '';
    document.getElementById('redirects-form-url').value      = '';
    document.getElementById('redirects-form-comments').value = '';
    document.getElementById('redirects-form-error').classList.add('d-none');
    formModal.show();
  }

  function openEditModal(id, phrase, url, comments) {
    document.getElementById('modal-redirects-form-label').textContent = 'Edit Redirect';
    document.getElementById('redirects-form-id').value       = id;
    document.getElementById('redirects-form-phrase').value   = phrase;
    document.getElementById('redirects-form-url').value      = url;
    document.getElementById('redirects-form-comments').value = comments;
    document.getElementById('redirects-form-error').classList.add('d-none');
    formModal.show();
  }

  document.getElementById('btn-add').addEventListener('click', openAddModal);

  document.getElementById('btn-redirects-form-save').addEventListener('click', () => {
    const id       = document.getElementById('redirects-form-id').value;
    const phrase   = document.getElementById('redirects-form-phrase').value.trim();
    const url      = document.getElementById('redirects-form-url').value.trim();
    const comments = document.getElementById('redirects-form-comments').value.trim();
    const errorEl  = document.getElementById('redirects-form-error');

    if (!phrase || !url) {
      errorEl.textContent = 'Phrase and URL are required.';
      errorEl.classList.remove('d-none');
      return;
    }

    const endpoint = id ? '/admin/startpage/redirects/edit' : '/admin/startpage/redirects/add';
    const body     = id
      ? { id: parseInt(id, 10), phrase, url, comments }
      : { phrase, url, comments };

    fetch(endpoint, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status === 'success') {
          formModal.hide();
          window.location.reload();
        } else {
          errorEl.textContent = data.message || 'An error occurred.';
          errorEl.classList.remove('d-none');
        }
      })
      .catch(() => {
        errorEl.textContent = 'A network error occurred.';
        errorEl.classList.remove('d-none');
      });
  });

  formModalEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('btn-redirects-form-save').click();
    }
  });

  // ── Delete modal ───────────────────────────────────────────────────────────
  const deleteModalEl = document.getElementById('modal-redirects-delete-confirm');
  const deleteModal   = new bootstrap.Modal(deleteModalEl, { focus: false });
  let pendingDeleteId = null;

  deleteModalEl.addEventListener('shown.bs.modal', () => {
    const closeBtn = deleteModalEl.querySelector('.btn-close');
    if (closeBtn) closeBtn.focus();
  });

  deleteModalEl.addEventListener('hide.bs.modal', () => {
    const focused = deleteModalEl.querySelector(':focus');
    if (focused) focused.blur();
  });

  document.getElementById('btn-redirects-delete-confirm').addEventListener('click', () => {
    if (!pendingDeleteId) return;

    fetch('/admin/startpage/redirects/delete', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ ids: [pendingDeleteId] }),
    })
      .then((res) => res.json())
      .then(() => {
        pendingDeleteId = null;
        deleteModal.hide();
        window.location.reload();
      })
      .catch((err) => console.error('Delete failed:', err));
  });

  // ── Table button delegation ────────────────────────────────────────────────
  document.querySelector('#table-redirects tbody')?.addEventListener('click', (e) => {
    const editBtn   = e.target.closest('.btn-edit');
    const deleteBtn = e.target.closest('.btn-delete-row');

    if (editBtn) {
      openEditModal(
        editBtn.dataset.id,
        editBtn.dataset.phrase,
        editBtn.dataset.url,
        editBtn.dataset.comments,
      );
    }

    if (deleteBtn) {
      pendingDeleteId = parseInt(deleteBtn.dataset.id, 10);
      deleteModal.show();
    }
  });

});
