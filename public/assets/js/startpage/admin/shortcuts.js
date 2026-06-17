document.addEventListener('DOMContentLoaded', function () {

  // ── Helpers ────────────────────────────────────────────────────────────────

  function showAlert(message, type = 'danger') {
    const existing = document.getElementById('shortcuts-alert');
    if (existing) {
      existing.remove();
    }
    const alert = document.createElement('div');
    alert.id = 'shortcuts-alert';
    alert.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alert.role = 'alert';
    alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
    const container = document.querySelector('.container-fluid .row .col-12');
    container.prepend(alert);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  async function jsonPost(url, data) {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(data),
    });
    return response;
  }

  async function formPost(url, formData) {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData,
    });
    return response;
  }

  function reloadPage() {
    window.location.reload();
  }

  function clearAddShortcutExistingIconSelection() {
    document.querySelectorAll('.add-shortcut-existing-icon').forEach((input) => {
      input.checked = false;
    });
  }

  function getAddShortcutExistingIconFilename() {
    const selectedIcon = document.querySelector('.add-shortcut-existing-icon:checked');
    return selectedIcon ? selectedIcon.value : '';
  }

  // ── Category: Add ──────────────────────────────────────────────────────────

  const modalAddCategory     = document.getElementById('modal-add-category');
  const inputAddCategoryName = document.getElementById('add-category-name');

  modalAddCategory.addEventListener('shown.bs.modal', function () {
    inputAddCategoryName.value = '';
    inputAddCategoryName.focus();
  });

  inputAddCategoryName.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      document.getElementById('btn-add-category-confirm').click();
    }
  });

  document.getElementById('btn-add-category-confirm').addEventListener('click', async function () {
    const name = inputAddCategoryName.value.trim();
    if (!name) {
      inputAddCategoryName.focus();
      return;
    }

    const res  = await jsonPost('/admin/startpage/shortcuts/category/add', { name });
    const data = await res.json();

    bootstrap.Modal.getInstance(modalAddCategory).hide();

    if (res.ok) {
      reloadPage();
    } else {
      showAlert(data.message || 'Failed to add category.');
    }
  });

  // ── Category: Edit ─────────────────────────────────────────────────────────

  const modalEditCategory     = document.getElementById('modal-edit-category');
  const inputEditCategoryName = document.getElementById('edit-category-name');

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-edit-category');
    if (!btn) return;

    document.getElementById('edit-category-id').value = btn.dataset.id;
    inputEditCategoryName.value = btn.dataset.name;
    bootstrap.Modal.getOrCreateInstance(modalEditCategory).show();
  });

  inputEditCategoryName.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      document.getElementById('btn-edit-category-confirm').click();
    }
  });

  document.getElementById('btn-edit-category-confirm').addEventListener('click', async function () {
    const id   = parseInt(document.getElementById('edit-category-id').value, 10);
    const name = inputEditCategoryName.value.trim();
    if (!name) {
      inputEditCategoryName.focus();
      return;
    }

    const res  = await jsonPost('/admin/startpage/shortcuts/category/edit', { id, name });
    const data = await res.json();

    bootstrap.Modal.getInstance(modalEditCategory).hide();

    if (res.ok) {
      reloadPage();
    } else {
      showAlert(data.message || 'Failed to update category.');
    }
  });

  // ── Category: Delete ───────────────────────────────────────────────────────

  const modalDeleteCategory = document.getElementById('modal-delete-category');

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete-category');
    if (!btn) return;

    document.getElementById('delete-category-id').value        = btn.dataset.id;
    document.getElementById('delete-category-name').textContent = btn.dataset.name;
    bootstrap.Modal.getOrCreateInstance(modalDeleteCategory).show();
  });

  document.getElementById('btn-delete-category-confirm').addEventListener('click', async function () {
    const id  = parseInt(document.getElementById('delete-category-id').value, 10);
    const res = await jsonPost('/admin/startpage/shortcuts/category/delete', { id });
    const data = await res.json();

    bootstrap.Modal.getInstance(modalDeleteCategory).hide();

    if (res.ok) {
      reloadPage();
    } else {
      showAlert(data.message || 'Failed to delete category.');
    }
  });

  // ── Category: Reorder (up/down) ────────────────────────────────────────────

  document.addEventListener('click', async function (e) {
    const btnUp   = e.target.closest('.btn-move-category-up');
    const btnDown = e.target.closest('.btn-move-category-down');
    const btn     = btnUp || btnDown;
    if (!btn) return;

    const cards = Array.from(document.querySelectorAll('.card[data-category-id]'));
    const card  = document.getElementById(`category-card-${btn.dataset.id}`);
    const idx   = cards.indexOf(card);

    if (btnUp && idx === 0) return;
    if (btnDown && idx === cards.length - 1) return;

    const swapIdx  = btnUp ? idx - 1 : idx + 1;
    const swapCard = cards[swapIdx];

    if (btnUp) {
      card.parentNode.insertBefore(card, swapCard);
    } else {
      card.parentNode.insertBefore(swapCard, card);
    }

    const orderedIds = Array.from(document.querySelectorAll('.card[data-category-id]'))
      .map((el) => parseInt(el.dataset.categoryId, 10));

    const res  = await jsonPost('/admin/startpage/shortcuts/category/reorder', { ids: orderedIds });
    const data = await res.json();

    if (!res.ok) {
      showAlert(data.message || 'Failed to save category order.');
    } else {
      reloadPage();
    }
  });

  // ── Shortcut: Add ──────────────────────────────────────────────────────────

  const modalAddShortcut     = document.getElementById('modal-add-shortcut');
  const inputAddShortcutIcon = document.getElementById('add-shortcut-icon');

  document.querySelectorAll('.add-shortcut-existing-icon').forEach((input) => {
    input.addEventListener('change', function () {
      if (this.checked) {
        inputAddShortcutIcon.value = '';
      }
    });
  });

  inputAddShortcutIcon.addEventListener('change', function () {
    if (this.files.length > 0) {
      clearAddShortcutExistingIconSelection();
    }
  });

  const btnClearAddShortcutExistingIcon = document.getElementById('btn-add-shortcut-clear-existing-icon');
  if (btnClearAddShortcutExistingIcon) {
    btnClearAddShortcutExistingIcon.addEventListener('click', function () {
      clearAddShortcutExistingIconSelection();
    });
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-add-shortcut');
    if (!btn) return;

    document.getElementById('add-shortcut-category-id').value      = btn.dataset.categoryId;
    document.getElementById('add-shortcut-category-display').value = btn.dataset.categoryName;
    document.getElementById('add-shortcut-name').value             = '';
    document.getElementById('add-shortcut-url').value              = '';
    inputAddShortcutIcon.value = '';
    clearAddShortcutExistingIconSelection();
    bootstrap.Modal.getOrCreateInstance(modalAddShortcut).show();
  });

  modalAddShortcut.addEventListener('shown.bs.modal', function () {
    document.getElementById('add-shortcut-name').focus();
  });

  document.getElementById('btn-add-shortcut-confirm').addEventListener('click', async function () {
    const categoryId   = document.getElementById('add-shortcut-category-id').value;
    const name         = document.getElementById('add-shortcut-name').value.trim();
    const url          = document.getElementById('add-shortcut-url').value.trim();
    const iconFile     = inputAddShortcutIcon.files[0];
    const iconFilename = getAddShortcutExistingIconFilename();

    if (!name || !url) {
      showAlert('Name and URL are required.');
      return;
    }

    const fd = new FormData();
    fd.append('category_id', categoryId);
    fd.append('name', name);
    fd.append('url', url);
    if (iconFile) {
      fd.append('icon', iconFile);
    } else if (iconFilename) {
      fd.append('existing_icon_filename', iconFilename);
    }

    const res  = await formPost('/admin/startpage/shortcuts/add', fd);
    const data = await res.json();

    bootstrap.Modal.getInstance(modalAddShortcut).hide();

    if (res.ok) {
      reloadPage();
    } else {
      showAlert(data.message || 'Failed to add shortcut.');
    }
  });

  // ── Shortcut: Edit ─────────────────────────────────────────────────────────

  const modalEditShortcut = document.getElementById('modal-edit-shortcut');

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-edit-shortcut');
    if (!btn) return;

    document.getElementById('edit-shortcut-id').value   = btn.dataset.id;
    document.getElementById('edit-shortcut-name').value = btn.dataset.name;
    document.getElementById('edit-shortcut-url').value  = btn.dataset.url;
    document.getElementById('edit-shortcut-icon').value = '';

    const categorySelect = document.getElementById('edit-shortcut-category-id');
    categorySelect.value = btn.dataset.categoryId;

    const iconFilename  = btn.dataset.iconFilename;
    const iconContainer = document.getElementById('edit-shortcut-current-icon');
    if (iconFilename) {
      iconContainer.innerHTML = `<img src="/uploads/startpage/icons/${iconFilename}" alt="Current icon" style="width:40px;height:40px;object-fit:contain;" class="border rounded p-1">`;
    } else {
      iconContainer.innerHTML = '<span class="text-secondary">No icon</span>';
    }

    bootstrap.Modal.getOrCreateInstance(modalEditShortcut).show();
  });

  document.getElementById('btn-edit-shortcut-confirm').addEventListener('click', async function () {
    const id         = document.getElementById('edit-shortcut-id').value;
    const categoryId = document.getElementById('edit-shortcut-category-id').value;
    const name       = document.getElementById('edit-shortcut-name').value.trim();
    const url        = document.getElementById('edit-shortcut-url').value.trim();
    const iconFile   = document.getElementById('edit-shortcut-icon').files[0];

    if (!name || !url) {
      showAlert('Name and URL are required.');
      return;
    }

    const fd = new FormData();
    fd.append('id', id);
    fd.append('category_id', categoryId);
    fd.append('name', name);
    fd.append('url', url);
    if (iconFile) {
      fd.append('icon', iconFile);
    }

    const res  = await formPost('/admin/startpage/shortcuts/edit', fd);
    const data = await res.json();

    bootstrap.Modal.getInstance(modalEditShortcut).hide();

    if (res.ok) {
      reloadPage();
    } else {
      showAlert(data.message || 'Failed to update shortcut.');
    }
  });

  // ── Shortcut: Delete ───────────────────────────────────────────────────────

  const modalDeleteShortcut = document.getElementById('modal-delete-shortcut');

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete-shortcut');
    if (!btn) return;

    document.getElementById('delete-shortcut-id').value         = btn.dataset.id;
    document.getElementById('delete-shortcut-name').textContent = btn.dataset.name;
    bootstrap.Modal.getOrCreateInstance(modalDeleteShortcut).show();
  });

  document.getElementById('btn-delete-shortcut-confirm').addEventListener('click', async function () {
    const id   = parseInt(document.getElementById('delete-shortcut-id').value, 10);
    const res  = await jsonPost('/admin/startpage/shortcuts/delete', { id });
    const data = await res.json();

    bootstrap.Modal.getInstance(modalDeleteShortcut).hide();

    if (res.ok) {
      reloadPage();
    } else {
      showAlert(data.message || 'Failed to delete shortcut.');
    }
  });

  // ── Shortcut: Reorder (up/down) ────────────────────────────────────────────

  document.addEventListener('click', async function (e) {
    const btnUp   = e.target.closest('.btn-move-shortcut-up');
    const btnDown = e.target.closest('.btn-move-shortcut-down');
    const btn     = btnUp || btnDown;
    if (!btn) return;

    const categoryId = btn.dataset.categoryId;
    const tbody      = document.querySelector(`#shortcuts-table-${categoryId} tbody`);
    const rows       = Array.from(tbody.querySelectorAll('tr[data-shortcut-id]'));
    const row        = document.getElementById(`shortcut-row-${btn.dataset.id}`);
    const idx        = rows.indexOf(row);

    if (btnUp && idx === 0) return;
    if (btnDown && idx === rows.length - 1) return;

    const swapIdx = btnUp ? idx - 1 : idx + 1;
    const swapRow = rows[swapIdx];

    if (btnUp) {
      tbody.insertBefore(row, swapRow);
    } else {
      tbody.insertBefore(swapRow, row);
    }

    const orderedIds = Array.from(tbody.querySelectorAll('tr[data-shortcut-id]'))
      .map((el) => parseInt(el.dataset.shortcutId, 10));

    const res  = await jsonPost('/admin/startpage/shortcuts/reorder', {
      category_id: parseInt(categoryId, 10),
      ids: orderedIds,
    });
    const data = await res.json();

    if (!res.ok) {
      showAlert(data.message || 'Failed to save shortcut order.');
    } else {
      reloadPage();
    }
  });

});
