document.addEventListener('DOMContentLoaded', function () {
	const container  = document.getElementById('notes-container');
	const masterKey  = container ? container.dataset.masterKey : '';

	const getCookie = (name) => {
		const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
		return match ? decodeURIComponent(match[1]) : '';
	};

	const tbody = document.getElementById('notes-tbody');

	const escHtml = (str) => {
		const d = document.createElement('div');
		d.appendChild(document.createTextNode(str));
		return d.innerHTML;
	};

	const formatDate = (str) => {
		const d = new Date(str);
		return d.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
	};

	const deleteModal = new bootstrap.Modal(document.getElementById('modal-delete-note'));
	const btnConfirmDelete = document.getElementById('btn-confirm-delete');
	let pendingDeleteId = null;
	let pendingDeleteRow = null;

	const attachPinHandlers = () => {
		tbody.querySelectorAll('.btn-pin-note').forEach((btn) => {
			btn.addEventListener('click', async function () {
				const noteId        = this.dataset.noteId;
				const currentlyPinned = parseInt(this.dataset.pinned, 10);
				const newPinned     = currentlyPinned ? 0 : 1;
				const notekey       = getCookie('noteskey');
				try {
					const response = await fetch(`/api/notes/${noteId}`, {
						method: 'PATCH',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest',
							apikey: masterKey,
							notekey,
						},
						body: JSON.stringify({ pinned: newPinned }),
					});
					if (!response.ok) {
						alert('Failed to update pin status. Please try again.');
						return;
					}
					fetchNotes(currentQuery, currentPage);
				} catch {
					alert('An error occurred. Please try again.');
				}
			});
		});
	};

	const attachDeleteHandlers = () => {
		tbody.querySelectorAll('.btn-delete-note').forEach((btn) => {
			btn.addEventListener('click', function () {
				pendingDeleteId  = this.dataset.noteId;
				pendingDeleteRow = this.closest('tr');
				deleteModal.show();
			});
		});
	};

	btnConfirmDelete.addEventListener('click', async () => {
		if (!pendingDeleteId) return;
		deleteModal.hide();
		try {
			const response = await fetch(`/api/notes/${pendingDeleteId}`, {
				method: 'DELETE',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					apikey: masterKey,
				},
			});
			if (!response.ok) {
				alert('Failed to delete the note. Please try again.');
				return;
			}
			pendingDeleteRow?.remove();
			if (tbody.querySelectorAll('tr').length === 0) {
				tbody.innerHTML = '<tr><td colspan="2" class="text-muted">No notes found.</td></tr>';
				paginationNav.innerHTML = '';
			}
		} catch {
			alert('An error occurred. Please try again.');
		} finally {
			pendingDeleteId  = null;
			pendingDeleteRow = null;
		}
	});

	const paginationNav = document.getElementById('notes-pagination');
	const urlParams     = new URLSearchParams(window.location.search);
	let currentQuery    = urlParams.get('q') ?? '';
	let currentPage     = parseInt(urlParams.get('page') ?? '1', 10);
	document.getElementById('notes-search').value = currentQuery;
	history.replaceState({ q: currentQuery, page: currentPage }, '');

	const renderNotes = (notes) => {
		if (notes.length === 0) {
			tbody.innerHTML = '<tr><td colspan="2" class="text-muted">No notes found.</td></tr>';
			return;
		}
		tbody.innerHTML = notes.map((note) => {
			const pinned     = parseInt(note.pinned, 10) === 1;
			const rowClass   = pinned ? ' class="note-pinned"' : '';
			const pinBtnClass = 'btn-outline-primary';
			const pinIcon    = pinned ? 'bi-pin-fill' : 'bi-pin';
			const pinTitle   = pinned ? 'Unpin' : 'Pin';
			return `<tr${rowClass}>
				<td>
					<a href="/admin/notes/${note.id}/edit" class="text-decoration-none fw-semibold">${escHtml(note.title ?? 'Untitled')}</a>
					<div class="text-muted small">${formatDate(note.updated_at)}</div>
				</td>
				<td class="text-end text-nowrap">
					<div class="btn-group" role="group" aria-label="Note actions">
						<button type="button" class="btn btn-sm ${pinBtnClass} btn-pin-note" data-note-id="${note.id}" data-pinned="${pinned ? 1 : 0}" title="${pinTitle}"><i class="bi ${pinIcon}"></i></button>
						<a href="/admin/notes/${note.id}/edit" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-fill"></i><span class="d-none d-lg-inline"> Edit</span></a>
						<button type="button" class="btn btn-sm btn-outline-primary btn-delete-note" data-note-id="${note.id}" title="Delete"><i class="bi bi-trash-fill"></i><span class="d-none d-lg-inline"> Delete</span></button>
					</div>
				</td>
			</tr>`;
		}).join('');
		attachPinHandlers();
		attachDeleteHandlers();
	};

	const renderPagination = (page, totalPages) => {
		if (totalPages <= 1) {
			paginationNav.innerHTML = '';
			return;
		}
		const prev = page > 1
			? `<li class="page-item"><button class="page-link" data-page="${page - 1}">Previous</button></li>`
			: `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
		const next = page < totalPages
			? `<li class="page-item"><button class="page-link" data-page="${page + 1}">Next</button></li>`
			: `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
		const pages = Array.from({ length: totalPages }, (_, i) => i + 1).map((p) =>
			`<li class="page-item${p === page ? ' active' : ''}">
				<button class="page-link" data-page="${p}">${p}</button>
			</li>`
		).join('');
		paginationNav.innerHTML = `<ul class="pagination justify-content-center mt-3">${prev}${pages}${next}</ul>`;
		paginationNav.querySelectorAll('button[data-page]').forEach((btn) => {
			btn.addEventListener('click', () => {
				currentPage  = parseInt(btn.dataset.page, 10);
				const pParams = new URLSearchParams();
				if (currentQuery) pParams.set('q', currentQuery);
				if (currentPage > 1) pParams.set('page', String(currentPage));
				history.pushState({ q: currentQuery, page: currentPage }, '', pParams.toString() ? `/admin/notes?${pParams}` : '/admin/notes');
				fetchNotes(currentQuery, currentPage);
			});
		});
	};

	const fetchNotes = async (q = '', page = 1) => {
		tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Loading&hellip;</td></tr>';
		paginationNav.innerHTML = '';
		try {
			const params = new URLSearchParams({ page });
			if (q) params.set('q', q);
			const response = await fetch(`/api/notes/list?${params}`, {
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					apikey: masterKey,
				},
			});
			if (!response.ok) {
				tbody.innerHTML = '<tr><td colspan="2" class="text-danger">Failed to load notes.</td></tr>';
				return;
			}
			const data = await response.json();
			renderNotes(data.notes ?? []);
			renderPagination(data.page ?? 1, data.total_pages ?? 1);
		} catch {
			tbody.innerHTML = '<tr><td colspan="2" class="text-danger">An error occurred.</td></tr>';
		}
	};

	document.getElementById('btn-refresh').addEventListener('click', () => {
		fetchNotes(currentQuery, currentPage);
	});

	let debounceTimer;
	document.getElementById('notes-search').addEventListener('input', function () {
		clearTimeout(debounceTimer);
		currentQuery = this.value.trim();
		currentPage  = 1;
		debounceTimer = setTimeout(() => {
			const sParams = new URLSearchParams();
			if (currentQuery) sParams.set('q', currentQuery);
			history.pushState({ q: currentQuery, page: 1 }, '', sParams.toString() ? `/admin/notes?${sParams}` : '/admin/notes');
			fetchNotes(currentQuery, 1);
		}, 300);
	});

	window.addEventListener('popstate', (event) => {
		const state  = event.state ?? {};
		currentQuery = state.q ?? '';
		currentPage  = state.page ?? 1;
		document.getElementById('notes-search').value = currentQuery;
		fetchNotes(currentQuery, currentPage);
	});

	fetchNotes(currentQuery, currentPage);
});
