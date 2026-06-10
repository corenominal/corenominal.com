(function () {
    'use strict';

    const BASE = '/admin/auth/groups';

    const state = { page: 1, perPage: 20, sort: 'id', dir: 'desc', search: '' };

    let deleteIsBulk   = false;
    let deleteTargetId = null;
    let isEditing      = false;

    const tbody         = document.getElementById('groupsTableBody');
    const paginationEl  = document.getElementById('pagination');
    const infoEl        = document.getElementById('paginationInfo');
    const selectAll     = document.getElementById('selectAll');
    const btnBulkDel    = document.getElementById('btnBulkDelete');
    const selectedCount = document.getElementById('selectedCount');
    const editModalEl   = document.getElementById('editModal');
    const deleteModalEl = document.getElementById('deleteModal');
    const editModal     = new bootstrap.Modal(editModalEl);
    const deleteModal   = new bootstrap.Modal(deleteModalEl);
    const userSelect      = document.getElementById('editUserUuid');
    const groupSelect     = document.getElementById('editGroupSelect');
    const groupNewNameWrap = document.getElementById('newGroupNameWrap');
    const groupNewName    = document.getElementById('editGroupName');

    // ── Group names select ────────────────────────────────────────────────────

    function loadGroupNames() {
        fetch(`${BASE}/group-names`)
            .then(r => r.json())
            .then(data => {
                const placeholder = groupSelect.querySelector('option[value=""]');
                groupSelect.innerHTML = '';
                groupSelect.appendChild(placeholder);
                data.groups.forEach(name => {
                    const opt = document.createElement('option');
                    opt.value       = name;
                    opt.textContent = name;
                    groupSelect.appendChild(opt);
                });
                const newOpt = document.createElement('option');
                newOpt.value       = '__new__';
                newOpt.textContent = '— Create new group —';
                groupSelect.appendChild(newOpt);
            });
    }

    groupSelect.addEventListener('change', () => {
        const isNew = groupSelect.value === '__new__';
        groupNewNameWrap.classList.toggle('d-none', !isNew);
        if (isNew) groupNewName.focus();
        else        groupNewName.value = '';
    });

    // ── Users select ──────────────────────────────────────────────────────────

    function loadUsers() {
        fetch(`${BASE}/users`)
            .then(r => r.json())
            .then(data => {
                const placeholder = userSelect.querySelector('option[value=""]');
                userSelect.innerHTML = '';
                userSelect.appendChild(placeholder);
                data.users.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value       = u.uuid;
                    opt.textContent = u.email;
                    userSelect.appendChild(opt);
                });
            });
    }

    // ── Fetch & render ────────────────────────────────────────────────────────

    function fetchGroups() {
        const params = new URLSearchParams({
            page:     state.page,
            per_page: state.perPage,
            sort:     state.sort,
            dir:      state.dir,
            search:   state.search,
        });
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr>`;

        fetch(`${BASE}/data?${params}`)
            .then(r => r.json())
            .then(data => {
                renderRows(data.groups);
                renderPagination(data.total, data.page, data.pages, data.per_page);
            })
            .catch(() => {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Failed to load groups.</td></tr>`;
            });
    }

    function renderRows(groups) {
        selectAll.checked       = false;
        selectAll.indeterminate = false;

        if (!groups || groups.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No groups found.</td></tr>`;
            updateBulkBtn();
            return;
        }

        tbody.innerHTML = groups.map(g => `
            <tr data-id="${g.id}">
                <td><input type="checkbox" class="form-check-input row-check" value="${g.id}"></td>
                <td>${g.id}</td>
                <td>${esc(g.group)}</td>
                <td>${esc(g.user_email)}</td>
                <td class="text-nowrap">${fmtDate(g.created_at)}</td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${g.id}" title="Edit">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary btn-delete ms-1"
                            data-id="${g.id}" data-label="${esc(g.group)}" title="Delete">
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

    function openCreateModal() {
        isEditing = false;
        document.getElementById('editModalLabel').textContent = 'Add Membership';
        document.getElementById('editGroupId').value          = '';
        groupSelect.value                                     = '';
        groupNewName.value                                    = '';
        groupNewNameWrap.classList.add('d-none');
        userSelect.value                                      = '';
        document.getElementById('editError').classList.add('d-none');
        document.getElementById('editSaveBtn').disabled       = false;
        document.getElementById('editSaveBtn').textContent    = 'Create';
        editModal.show();
    }

    function openEditModal(id) {
        isEditing = true;
        fetch(`${BASE}/${id}`)
            .then(r => r.json())
            .then(g => {
                document.getElementById('editModalLabel').textContent = 'Edit Membership';
                document.getElementById('editGroupId').value          = g.id;
                groupSelect.value                                     = g.group || '';
                groupNewName.value                                    = '';
                groupNewNameWrap.classList.add('d-none');
                userSelect.value                                      = g.user_uuid || '';
                document.getElementById('editError').classList.add('d-none');
                document.getElementById('editSaveBtn').disabled       = false;
                document.getElementById('editSaveBtn').textContent    = 'Save Changes';
                editModal.show();
            });
    }

    // ── Sort headers ──────────────────────────────────────────────────────────

    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor     = 'pointer';
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
            fetchGroups();
        });
    });

    // ── Search ────────────────────────────────────────────────────────────────

    let searchTimer;
    document.getElementById('searchInput').addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.search = e.target.value.trim();
            state.page   = 1;
            fetchGroups();
        }, 350);
    });

    // ── Per-page ──────────────────────────────────────────────────────────────

    document.getElementById('perPageSelect').addEventListener('change', e => {
        state.perPage = parseInt(e.target.value, 10);
        state.page    = 1;
        fetchGroups();
    });

    // ── Pagination clicks ─────────────────────────────────────────────────────

    paginationEl.addEventListener('click', e => {
        e.preventDefault();
        const a = e.target.closest('[data-page]');
        if (!a) return;
        const p = parseInt(a.dataset.page, 10);
        if (!isNaN(p) && p !== state.page) {
            state.page = p;
            fetchGroups();
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

    // ── Create button ─────────────────────────────────────────────────────────

    document.getElementById('btnCreate').addEventListener('click', openCreateModal);

    // ── Edit & delete row buttons ─────────────────────────────────────────────

    tbody.addEventListener('click', e => {
        const editBtn = e.target.closest('.btn-edit');
        if (editBtn) {
            openEditModal(editBtn.dataset.id);
            return;
        }

        const delBtn = e.target.closest('.btn-delete');
        if (delBtn) {
            deleteIsBulk   = false;
            deleteTargetId = delBtn.dataset.id;
            document.getElementById('deleteModalMsg').textContent =
                `Delete group "${delBtn.dataset.label}"? This action cannot be undone.`;
            deleteModal.show();
        }
    });

    // ── Save (create or update) ───────────────────────────────────────────────

    document.getElementById('editForm').addEventListener('submit', e => {
        e.preventDefault();

        const id      = document.getElementById('editGroupId').value;
        const saveBtn = document.getElementById('editSaveBtn');
        const errEl   = document.getElementById('editError');
        const body    = new FormData();

        const groupValue = groupSelect.value === '__new__'
            ? groupNewName.value.trim()
            : groupSelect.value;

        if (!groupValue) {
            errEl.textContent = 'Please select or enter a group name.';
            errEl.classList.remove('d-none');
            return;
        }

        body.set('group',     groupValue);
        body.set('user_uuid', document.getElementById('editUserUuid').value);

        saveBtn.disabled = true;
        errEl.classList.add('d-none');

        const url = isEditing ? `${BASE}/${id}` : `${BASE}/create`;

        fetch(url, { method: 'POST', body })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    editModal.hide();
                    loadGroupNames();
                    fetchGroups();
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

    // ── Bulk delete button ────────────────────────────────────────────────────

    btnBulkDel.addEventListener('click', () => {
        const ids = checkedIds();
        deleteIsBulk = true;
        document.getElementById('deleteModalMsg').textContent =
            `Delete ${ids.length} selected group${ids.length !== 1 ? 's' : ''}? This action cannot be undone.`;
        deleteModal.show();
    });

    // ── Confirm delete ────────────────────────────────────────────────────────

    document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
        const confirmBtn    = document.getElementById('confirmDeleteBtn');
        confirmBtn.disabled = true;

        const done = () => { deleteModal.hide(); fetchGroups(); confirmBtn.disabled = false; };

        if (deleteIsBulk) {
            const body = new FormData();
            checkedIds().forEach(id => body.append('ids[]', id));
            fetch(`${BASE}/bulk-delete`, { method: 'POST', body }).then(done);
        } else {
            fetch(`${BASE}/${deleteTargetId}`, { method: 'DELETE' }).then(done);
        }
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    loadGroupNames();
    loadUsers();
    fetchGroups();

}());
