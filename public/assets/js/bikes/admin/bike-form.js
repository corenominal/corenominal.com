document.addEventListener('DOMContentLoaded', function () {

	// ── Form elements ──────────────────────────────────────────────────────────
	const form = document.getElementById('bike-form');
	const alertBox = document.getElementById('form-alert');
	const btnSubmit = document.getElementById('btn-submit');
	const btnSpinner = document.getElementById('btn-submit-spinner');

	const action = form.dataset.action; // 'create' | 'edit'
	const bikeId = form.dataset.id;
	const apiKey = form.dataset.apiKey;

	const apiUrl = action === 'edit' ? '/api/bikes/' + bikeId : '/api/bikes';
	const apiMethod = action === 'edit' ? 'PUT' : 'POST';

	// ── Alert helpers ──────────────────────────────────────────────────────────
	function showAlert(type, message) {
		alertBox.className = 'alert alert-' + type + ' mb-4';
		alertBox.textContent = message;
		alertBox.classList.remove('d-none');
		alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	function hideAlert() {
		alertBox.className = 'alert d-none mb-4';
		alertBox.textContent = '';
	}

	function clearFieldErrors() {
		form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
		form.querySelectorAll('.invalid-feedback').forEach(function (el) { el.textContent = ''; });
	}

	function setFieldError(field, message) {
		const input = document.getElementById('field-' + field.replace('_', '-'));
		const err = document.getElementById('error-' + field);
		if (input) input.classList.add('is-invalid');
		if (err) err.textContent = message;
	}

	function setLoading(loading) {
		btnSubmit.disabled = loading;
		btnSpinner.classList.toggle('d-none', !loading);
	}

	// ── Form submit ────────────────────────────────────────────────────────────
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		hideAlert();
		clearFieldErrors();

		const name = document.getElementById('field-name').value.trim();
		const brand = document.getElementById('field-brand').value.trim();
		const model = document.getElementById('field-model').value.trim();
		const year = document.getElementById('field-year').value.trim();
		const status = document.getElementById('field-status').value;
		const totalKm = document.getElementById('field-total-km').value.trim();
		const components = document.getElementById('field-components').value.trim();
		const notes = document.getElementById('field-notes').value.trim();

		let hasErrors = false;

		if (!brand) {
			setFieldError('brand', 'Brand is required.');
			hasErrors = true;
		}

		if (!model) {
			setFieldError('model', 'Model is required.');
			hasErrors = true;
		}

		if (hasErrors) return;

		setLoading(true);

		fetch(apiUrl, {
			method: apiMethod,
			headers: { 'Content-Type': 'application/json', apikey: apiKey },
			body: JSON.stringify({
				name,
				brand,
				model,
				year,
				status,
				total_km: totalKm,
				components,
				notes,
			}),
		})
		.then(function (res) {
			return res.json().then(function (data) { return { status: res.status, data }; });
		})
		.then(function ({ status, data }) {
			setLoading(false);

			if (status === 200 || status === 201) {
				if (action === 'create') {
					window.location.href = '/admin/bikes/' + data.id + '/edit?created=1';
				} else {
					showAlert('success', 'Bike updated successfully.');
					window.scrollTo({ top: 0, behavior: 'smooth' });
				}
				return;
			}

			if (status === 422 && data.errors) {
				Object.entries(data.errors).forEach(function ([field, message]) { setFieldError(field, message); });
				showAlert('danger', 'Please correct the errors below and try again.');
				return;
			}

			showAlert('danger', data.message || 'An unexpected error occurred. Please try again.');
		})
		.catch(function () {
			setLoading(false);
			showAlert('danger', 'A network error occurred. Please check your connection and try again.');
		});
	});

	// ── Success banner after redirect from create ──────────────────────────────
	const params = new URLSearchParams(window.location.search);
	if (params.get('created') === '1') {
		showAlert('success', 'Bike created successfully.');
		window.history.replaceState({}, '', window.location.pathname);
	}

	// ── Photo gallery (edit mode only) ─────────────────────────────────────────
	const gallery = document.getElementById('photo-gallery');
	if (!gallery) return;

	const photoAlert = document.getElementById('photo-alert');
	const photoUpload = document.getElementById('field-photo-upload');
	const deletePhotoModal = new bootstrap.Modal(document.getElementById('modal-delete-photo'));
	const btnConfirmDeletePhoto = document.getElementById('btn-confirm-delete-photo');
	let pendingPhotoId = null;

	function showPhotoAlert(type, message) {
		photoAlert.className = 'alert alert-' + type + ' mb-3';
		photoAlert.textContent = message;
		photoAlert.classList.remove('d-none');
	}

	function hidePhotoAlert() {
		photoAlert.className = 'alert d-none mb-3';
		photoAlert.textContent = '';
	}

	function buildPhotoItem(photo) {
		const col = document.createElement('div');
		col.className = 'col-4 photo-item';
		col.dataset.photoId = photo.id;
		col.innerHTML = ''
			+ '<div class="position-relative">'
			+ '<img src="' + photo.url + '" alt="" class="img-fluid rounded" style="aspect-ratio: 1 / 1; object-fit: cover; width: 100%;">'
			+ '<button type="button" class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-1 btn-photo-delete" data-photo-id="' + photo.id + '" title="Delete photo">'
			+ '<i class="bi bi-trash3" aria-hidden="true"></i></button>'
			+ '<div class="d-flex justify-content-between mt-1">'
			+ '<button type="button" class="btn btn-sm btn-outline-primary btn-photo-move" data-direction="up" data-photo-id="' + photo.id + '" title="Move earlier">'
			+ '<i class="bi bi-arrow-left" aria-hidden="true"></i></button>'
			+ '<button type="button" class="btn btn-sm btn-outline-primary btn-photo-move" data-direction="down" data-photo-id="' + photo.id + '" title="Move later">'
			+ '<i class="bi bi-arrow-right" aria-hidden="true"></i></button>'
			+ '</div></div>';
		return col;
	}

	function sendReorder() {
		const ids = Array.from(gallery.querySelectorAll('.photo-item')).map(function (el) {
			return parseInt(el.dataset.photoId, 10);
		});

		fetch('/api/bikes/' + bikeId + '/photos/reorder', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', apikey: apiKey },
			body: JSON.stringify({ ids }),
		}).catch(function () {});
	}

	gallery.addEventListener('click', function (e) {
		const deleteBtn = e.target.closest('.btn-photo-delete');
		if (deleteBtn) {
			pendingPhotoId = deleteBtn.dataset.photoId;
			deletePhotoModal.show();
			return;
		}

		const moveBtn = e.target.closest('.btn-photo-move');
		if (moveBtn) {
			const item = moveBtn.closest('.photo-item');
			const direction = moveBtn.dataset.direction;
			const sibling = direction === 'up' ? item.previousElementSibling : item.nextElementSibling;
			if (!sibling) return;

			if (direction === 'up') {
				gallery.insertBefore(item, sibling);
			} else {
				gallery.insertBefore(sibling, item);
			}

			sendReorder();
		}
	});

	btnConfirmDeletePhoto.addEventListener('click', function () {
		if (!pendingPhotoId) return;

		btnConfirmDeletePhoto.disabled = true;

		fetch('/api/bikes/' + bikeId + '/photos/' + pendingPhotoId, {
			method: 'DELETE',
			headers: { apikey: apiKey },
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			btnConfirmDeletePhoto.disabled = false;
			deletePhotoModal.hide();

			if (data.status === 'success') {
				const item = gallery.querySelector('.photo-item[data-photo-id="' + pendingPhotoId + '"]');
				if (item) item.remove();
			} else {
				showPhotoAlert('danger', data.message || 'Failed to delete photo.');
			}

			pendingPhotoId = null;
		})
		.catch(function () {
			btnConfirmDeletePhoto.disabled = false;
			deletePhotoModal.hide();
			showPhotoAlert('danger', 'A network error occurred. Please try again.');
		});
	});

	photoUpload.addEventListener('change', function () {
		const file = photoUpload.files[0];
		if (!file) return;

		hidePhotoAlert();

		const formData = new FormData();
		formData.append('photo', file);

		photoUpload.disabled = true;

		fetch('/api/bikes/' + bikeId + '/photos', {
			method: 'POST',
			headers: { apikey: apiKey },
			body: formData,
		})
		.then(function (res) {
			return res.json().then(function (data) { return { status: res.status, data }; });
		})
		.then(function ({ status, data }) {
			photoUpload.disabled = false;
			photoUpload.value = '';

			if (status === 201 && data.photo) {
				gallery.appendChild(buildPhotoItem(data.photo));
			} else {
				showPhotoAlert('danger', data.message || 'Failed to upload photo.');
			}
		})
		.catch(function () {
			photoUpload.disabled = false;
			photoUpload.value = '';
			showPhotoAlert('danger', 'A network error occurred. Please try again.');
		});
	});

	// ── Notes list (edit mode only) ────────────────────────────────────────────
	const notesList = document.getElementById('notes-list');
	const deleteNoteModalEl = document.getElementById('modal-delete-note');
	if (!notesList || !deleteNoteModalEl) return;

	const deleteNoteModal = new bootstrap.Modal(deleteNoteModalEl);
	const btnConfirmDeleteNote = document.getElementById('btn-confirm-delete-note');
	let pendingNoteId = null;

	notesList.addEventListener('click', function (e) {
		const deleteBtn = e.target.closest('.btn-note-delete');
		if (!deleteBtn) return;

		pendingNoteId = deleteBtn.dataset.noteId;
		deleteNoteModal.show();
	});

	btnConfirmDeleteNote.addEventListener('click', function () {
		if (!pendingNoteId) return;

		btnConfirmDeleteNote.disabled = true;

		fetch('/api/bikes/' + bikeId + '/notes/' + pendingNoteId, {
			method: 'DELETE',
			headers: { apikey: apiKey },
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			btnConfirmDeleteNote.disabled = false;
			deleteNoteModal.hide();

			if (data.status === 'success') {
				const item = notesList.querySelector('.note-item[data-note-id="' + pendingNoteId + '"]');
				if (item) item.remove();
			} else {
				showAlert('danger', data.message || 'Failed to delete note.');
			}

			pendingNoteId = null;
		})
		.catch(function () {
			btnConfirmDeleteNote.disabled = false;
			deleteNoteModal.hide();
			showAlert('danger', 'A network error occurred. Please try again.');
		});
	});
});
