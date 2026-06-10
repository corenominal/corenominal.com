(function () {
    'use strict';

    const BASE = '/admin/auth/users';

    const state = { page: 1, perPage: 20, sort: 'id', dir: 'asc', search: '' };

    let deleteIsBulk = false;
    let deleteTargetId = null;

    const tbody        = document.getElementById('usersTableBody');
    const paginationEl = document.getElementById('pagination');
    const infoEl       = document.getElementById('paginationInfo');
    const selectAll    = document.getElementById('selectAll');
    const btnBulkDel   = document.getElementById('btnBulkDelete');
    const selectedCount = document.getElementById('selectedCount');
    const editModalEl  = document.getElementById('editModal');
    const deleteModalEl = document.getElementById('deleteModal');
    const editModal    = new bootstrap.Modal(editModalEl);
    const deleteModal  = new bootstrap.Modal(deleteModalEl);

    // ── Fetch & render ────────────────────────────────────────────────────────

    function fetchUsers() {
        const params = new URLSearchParams({
            page:     state.page,
            per_page: state.perPage,
            sort:     state.sort,
            dir:      state.dir,
            search:   state.search,
        });
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">Loading…</td></tr>`;

        fetch(`${BASE}/data?${params}`)
            .then(r => r.json())
            .then(data => {
                renderRows(data.users);
                renderPagination(data.total, data.page, data.pages, data.per_page);
            })
            .catch(() => {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">Failed to load users.</td></tr>`;
            });
    }

    function renderRows(users) {
        selectAll.checked     = false;
        selectAll.indeterminate = false;

        if (!users || users.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No users found.</td></tr>`;
            updateBulkBtn();
            return;
        }

        tbody.innerHTML = users.map(u => `
            <tr data-id="${u.id}">
                <td><input type="checkbox" class="form-check-input row-check" value="${u.id}"></td>
                <td>${u.id}</td>
                <td>${esc(u.email)}</td>
                <td>${esc(u.username)}</td>
                <td>${esc(u.realname)}</td>
                <td>${u.validated == 1
                    ? '<span class="badge bg-success">Yes</span>'
                    : '<span class="badge bg-secondary">No</span>'}</td>
                <td>${u.banned == 1
                    ? '<span class="badge bg-danger">Yes</span>'
                    : '<span class="badge bg-secondary">No</span>'}</td>
                <td class="text-nowrap">${fmtDate(u.created_at)}</td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${u.id}" title="Edit">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary btn-delete ms-1"
                            data-id="${u.id}" data-label="${esc(u.email)}" title="Delete">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                </td>
            </tr>`).join('');

        updateBulkBtn();
    }

    function renderPagination(total, page, pages, perPage) {
        const from = total === 0 ? 0 : (page - 1) * perPage + 1;
        const to   = Math.min(page * perPage, total);
        infoEl.textContent = total === 0 ? 'No results' : `Showing ${from}–${to} of ${total}`;

        if (pages <= 1) { paginationEl.innerHTML = ''; return; }

        const li = (p, label, disabled, active) =>
            `<li class="page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}">
                <a class="page-link" href="#" data-page="${p}">${label}</a></li>`;

        let html = li(page - 1, '‹', page === 1, false);

        let start = Math.max(1, page - 3);
        let end   = Math.min(pages, page + 3);
        if (end - start < 6) {
            if (start === 1) end = Math.min(pages, 7);
            else             start = Math.max(1, end - 6);
        }

        if (start > 1) {
            html += li(1, '1', false, false);
            if (start > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        }
        for (let i = start; i <= end; i++) html += li(i, i, false, i === page);
        if (end < pages) {
            if (end < pages - 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            html += li(pages, pages, false, false);
        }

        html += li(page + 1, '›', page === pages, false);
        paginationEl.innerHTML = html;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    function esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fmtDate(dt) {
        return dt ? dt.substring(0, 10) : '';
    }

    function checkedIds() {
        return Array.from(tbody.querySelectorAll('.row-check:checked')).map(cb => cb.value);
    }

    function updateBulkBtn() {
        const ids = checkedIds();
        btnBulkDel.classList.toggle('d-none', ids.length === 0);
        selectedCount.textContent = ids.length;
    }

    function updateSortIcons() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            const icon = th.querySelector('i');
            if (!icon) return;
            if (th.dataset.sort === state.sort) {
                icon.className = state.dir === 'asc' ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
            } else {
                icon.className = 'bi bi-chevron-expand';
            }
        });
    }

    // ── Sort headers ──────────────────────────────────────────────────────────

    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.style.whiteSpace = 'nowrap';
        th.innerHTML += ' <i class="bi bi-chevron-expand" aria-hidden="true"></i>';
        th.addEventListener('click', () => {
            if (state.sort === th.dataset.sort) {
                state.dir = state.dir === 'asc' ? 'desc' : 'asc';
            } else {
                state.sort = th.dataset.sort;
                state.dir  = 'asc';
            }
            state.page = 1;
            updateSortIcons();
            fetchUsers();
        });
    });

    // ── Search ────────────────────────────────────────────────────────────────

    let searchTimer;
    document.getElementById('searchInput').addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.search = e.target.value.trim();
            state.page   = 1;
            fetchUsers();
        }, 350);
    });

    // ── Per-page ──────────────────────────────────────────────────────────────

    document.getElementById('perPageSelect').addEventListener('change', e => {
        state.perPage = parseInt(e.target.value, 10);
        state.page    = 1;
        fetchUsers();
    });

    // ── Pagination clicks ─────────────────────────────────────────────────────

    paginationEl.addEventListener('click', e => {
        e.preventDefault();
        const a = e.target.closest('[data-page]');
        if (!a) return;
        const p = parseInt(a.dataset.page, 10);
        if (!isNaN(p) && p !== state.page) {
            state.page = p;
            fetchUsers();
        }
    });

    // ── Select all ────────────────────────────────────────────────────────────

    selectAll.addEventListener('change', () => {
        tbody.querySelectorAll('.row-check').forEach(cb => { cb.checked = selectAll.checked; });
        updateBulkBtn();
    });

    tbody.addEventListener('change', e => {
        if (!e.target.classList.contains('row-check')) return;
        const all     = tbody.querySelectorAll('.row-check');
        const checked = tbody.querySelectorAll('.row-check:checked');
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        selectAll.checked       = checked.length === all.length && all.length > 0;
        updateBulkBtn();
    });

    // ── Edit ──────────────────────────────────────────────────────────────────

    tbody.addEventListener('click', e => {
        const editBtn = e.target.closest('.btn-edit');
        if (editBtn) {
            fetch(`${BASE}/${editBtn.dataset.id}`)
                .then(r => r.json())
                .then(u => {
                    document.getElementById('editUserId').value    = u.id;
                    document.getElementById('editEmail').value     = u.email    || '';
                    document.getElementById('editUsername').value  = u.username || '';
                    document.getElementById('editRealname').value  = u.realname || '';
                    document.getElementById('editValidated').checked = parseInt(u.validated) === 1;
                    document.getElementById('editBanned').checked    = parseInt(u.banned)    === 1;
                    document.getElementById('editError').classList.add('d-none');
                    document.getElementById('editSaveBtn').disabled = false;
                    editModal.show();
                });
            return;
        }

        const delBtn = e.target.closest('.btn-delete');
        if (delBtn) {
            deleteIsBulk    = false;
            deleteTargetId  = delBtn.dataset.id;
            document.getElementById('deleteModalMsg').textContent =
                `Delete user "${delBtn.dataset.label}"? This action cannot be undone.`;
            deleteModal.show();
        }
    });

    document.getElementById('editForm').addEventListener('submit', e => {
        e.preventDefault();
        const id      = document.getElementById('editUserId').value;
        const saveBtn = document.getElementById('editSaveBtn');
        const errEl   = document.getElementById('editError');
        const body    = new FormData();

        body.set('email',     document.getElementById('editEmail').value);
        body.set('username',  document.getElementById('editUsername').value);
        body.set('realname',  document.getElementById('editRealname').value);
        body.set('validated', document.getElementById('editValidated').checked ? '1' : '0');
        body.set('banned',    document.getElementById('editBanned').checked    ? '1' : '0');

        saveBtn.disabled = true;
        errEl.classList.add('d-none');

        fetch(`${BASE}/${id}`, { method: 'POST', body })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    editModal.hide();
                    fetchUsers();
                } else {
                    errEl.textContent = data.error || 'Save failed.';
                    errEl.classList.remove('d-none');
                    saveBtn.disabled = false;
                }
            })
            .catch(() => {
                errEl.textContent = 'Network error. Please try again.';
                errEl.classList.remove('d-none');
                saveBtn.disabled = false;
            });
    });

    // ── Delete ────────────────────────────────────────────────────────────────

    btnBulkDel.addEventListener('click', () => {
        const ids = checkedIds();
        deleteIsBulk = true;
        document.getElementById('deleteModalMsg').textContent =
            `Delete ${ids.length} selected user${ids.length !== 1 ? 's' : ''}? This action cannot be undone.`;
        deleteModal.show();
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.disabled = true;

        const done = () => { deleteModal.hide(); fetchUsers(); confirmBtn.disabled = false; };

        if (deleteIsBulk) {
            const body = new FormData();
            checkedIds().forEach(id => body.append('ids[]', id));
            fetch(`${BASE}/bulk-delete`, { method: 'POST', body }).then(done);
        } else {
            fetch(`${BASE}/${deleteTargetId}`, { method: 'DELETE' }).then(done);
        }
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    fetchUsers();

}());
