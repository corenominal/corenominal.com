(function () {
    'use strict';

    const BASE = '/admin/social/tags';

    const state = { page: 1, perPage: 20, sort: 'id', dir: 'asc', search: '' };

    let deleteIsBulk   = false;
    let deleteTargetId = null;
    let isEditing      = false;

    const tbody         = document.getElementById('tagsTableBody');
    const paginationEl  = document.getElementById('pagination');
    const infoEl        = document.getElementById('paginationInfo');
    const selectAll     = document.getElementById('selectAll');
    const btnBulkDel    = document.getElementById('btnBulkDelete');
    const selectedCount = document.getElementById('selectedCount');
    const editModalEl   = document.getElementById('editModal');
    const deleteModalEl = document.getElementById('deleteModal');
    const editModal     = new bootstrap.Modal(editModalEl);
    const deleteModal   = new bootstrap.Modal(deleteModalEl);

    // ── Fetch & render ────────────────────────────────────────────────────────

    function fetchTags() {
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
                renderRows(data.tags);
                renderPagination(data.total, data.page, data.pages, data.per_page);
            })
            .catch(() => {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Failed to load tags.</td></tr>`;
            });
    }

    function renderRows(tags) {
        selectAll.checked       = false;
        selectAll.indeterminate = false;

        if (!tags || tags.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No tags found.</td></tr>`;
            updateBulkBtn();
            return;
        }

        tbody.innerHTML = tags.map(t => `
            <tr data-id="${t.id}">
                <td><input type="checkbox" class="form-check-input row-check" value="${t.id}"></td>
                <td>${t.id}</td>
                <td>${esc(t.name)}</td>
                <td><a href="${esc(t.url)}" target="_blank" rel="noopener noreferrer">${esc(t.url)}</a></td>
                <td class="text-nowrap">${fmtDate(t.created_at)}</td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${t.id}" title="Edit">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary btn-delete ms-1"
                            data-id="${t.id}" data-label="${esc(t.name)}" title="Delete">
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

    function openCreateModal() {
        isEditing = false;
        document.getElementById('editModalLabel').textContent = 'New Tag';
        document.getElementById('editTagId').value            = '';
        document.getElementById('editName').value             = '';
        document.getElementById('editUrl').value              = '';
        document.getElementById('editError').classList.add('d-none');
        document.getElementById('editSaveBtn').disabled       = false;
        document.getElementById('editSaveBtn').textContent    = 'Create';
        editModal.show();
    }

    function openEditModal(id) {
        isEditing = true;
        fetch(`${BASE}/${id}`)
            .then(r => r.json())
            .then(t => {
                document.getElementById('editModalLabel').textContent = 'Edit Tag';
                document.getElementById('editTagId').value            = t.id;
                document.getElementById('editName').value             = t.name || '';
                document.getElementById('editUrl').value              = t.url  || '';
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
            fetchTags();
        });
    });

    // ── Search ────────────────────────────────────────────────────────────────

    let searchTimer;
    document.getElementById('searchInput').addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.search = e.target.value.trim();
            state.page   = 1;
            fetchTags();
        }, 350);
    });

    // ── Per-page ──────────────────────────────────────────────────────────────

    document.getElementById('perPageSelect').addEventListener('change', e => {
        state.perPage = parseInt(e.target.value, 10);
        state.page    = 1;
        fetchTags();
    });

    // ── Pagination clicks ─────────────────────────────────────────────────────

    paginationEl.addEventListener('click', e => {
        e.preventDefault();
        const a = e.target.closest('[data-page]');
        if (!a) return;
        const p = parseInt(a.dataset.page, 10);
        if (!isNaN(p) && p !== state.page) {
            state.page = p;
            fetchTags();
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
                `Delete tag "${delBtn.dataset.label}"? This action cannot be undone.`;
            deleteModal.show();
        }
    });

    // ── Save (create or update) ───────────────────────────────────────────────

    document.getElementById('editForm').addEventListener('submit', e => {
        e.preventDefault();

        const id      = document.getElementById('editTagId').value;
        const saveBtn = document.getElementById('editSaveBtn');
        const errEl   = document.getElementById('editError');
        const urlVal  = document.getElementById('editUrl').value.trim();

        errEl.classList.add('d-none');

        try {
            new URL(urlVal);
        } catch {
            errEl.textContent = 'URL is not valid.';
            errEl.classList.remove('d-none');
            return;
        }

        const body = new FormData();

        body.set('name', document.getElementById('editName').value);
        body.set('url',  urlVal);

        saveBtn.disabled = true;

        const url = isEditing ? `${BASE}/${id}` : `${BASE}/create`;

        fetch(url, { method: 'POST', body })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    editModal.hide();
                    fetchTags();
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
            `Delete ${ids.length} selected tag${ids.length !== 1 ? 's' : ''}? This action cannot be undone.`;
        deleteModal.show();
    });

    // ── Confirm delete ────────────────────────────────────────────────────────

    document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
        const confirmBtn    = document.getElementById('confirmDeleteBtn');
        confirmBtn.disabled = true;

        const done = () => { deleteModal.hide(); fetchTags(); confirmBtn.disabled = false; };

        if (deleteIsBulk) {
            const body = new FormData();
            checkedIds().forEach(id => body.append('ids[]', id));
            fetch(`${BASE}/bulk-delete`, { method: 'POST', body }).then(done);
        } else {
            fetch(`${BASE}/${deleteTargetId}`, { method: 'DELETE' }).then(done);
        }
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    fetchTags();

    if (new URLSearchParams(window.location.search).get('new') === '1') {
        history.replaceState(null, '', BASE);
        openCreateModal();
    }

}());
