document.addEventListener('DOMContentLoaded', function () {

	const selectAll    = document.getElementById('select-all');
	const btnDelete    = document.getElementById('btn-delete');
	const btnConfirm   = document.getElementById('btn-delete-confirm');
	const deleteCount  = document.getElementById('delete-modal-count');
	const deleteModal  = new bootstrap.Modal(document.getElementById('modal-delete-confirm'));

	const selectedIds = new Set();
	let pendingIds    = [];

	function updateDeleteButton() {
		btnDelete.disabled = selectedIds.size === 0;
	}

	// Row checkboxes
	document.querySelectorAll('.row-checkbox').forEach(function (checkbox) {
		checkbox.addEventListener('change', function () {
			const id = parseInt(this.dataset.id, 10);
			if (this.checked) {
				selectedIds.add(id);
			} else {
				selectedIds.delete(id);
				if (selectAll) selectAll.checked = false;
			}
			updateDeleteButton();
		});
	});

	// Select-all checkbox
	if (selectAll) {
		selectAll.addEventListener('change', function () {
			document.querySelectorAll('.row-checkbox').forEach(function (checkbox) {
				checkbox.checked = selectAll.checked;
				const id = parseInt(checkbox.dataset.id, 10);
				if (selectAll.checked) {
					selectedIds.add(id);
				} else {
					selectedIds.delete(id);
				}
			});
			updateDeleteButton();
		});
	}

	// Bulk delete button
	if (btnDelete) {
		btnDelete.addEventListener('click', function () {
			if (selectedIds.size === 0) return;
			pendingIds = Array.from(selectedIds);
			deleteCount.textContent = pendingIds.length;
			deleteModal.show();
		});
	}

	// Per-row delete buttons
	document.querySelectorAll('.btn-delete-single').forEach(function (btn) {
		btn.addEventListener('click', function () {
			const id    = parseInt(this.dataset.id, 10);
			const title = this.dataset.title || 'this bookmark';
			pendingIds  = [id];
			deleteCount.textContent = 1;
			deleteModal.show();
		});
	});

	// Confirm delete
	if (btnConfirm) {
		btnConfirm.addEventListener('click', function () {
			if (pendingIds.length === 0) return;

			btnConfirm.disabled      = true;
			btnConfirm.textContent   = 'Deleting…';

			fetch('/admin/bookmarks/delete', {
				method:  'POST',
				headers: { 'Content-Type': 'application/json' },
				body:    JSON.stringify({ ids: pendingIds }),
			})
			.then(function (res) { return res.json(); })
			.then(function () {
				window.location.reload();
			})
			.catch(function (err) {
				// eslint-disable-next-line no-console
				console.error('Delete failed:', err);
				btnConfirm.disabled    = false;
				btnConfirm.textContent = 'Delete';
				deleteModal.hide();
			});
		});
	}
});
