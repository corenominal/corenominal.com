document.addEventListener('DOMContentLoaded', function () {

	// ── Form elements ──────────────────────────────────────────────────────────
	const form = document.getElementById('note-form');
	const alertBox = document.getElementById('form-alert');
	const btnSubmit = document.getElementById('btn-submit');
	const btnSpinner = document.getElementById('btn-submit-spinner');

	const action = form.dataset.action; // 'create' | 'edit'
	const bikeId = form.dataset.bikeId;
	const noteId = form.dataset.noteId;
	const apiKey = form.dataset.apiKey;

	const apiUrl = action === 'edit' ? '/api/bikes/' + bikeId + '/notes/' + noteId : '/api/bikes/' + bikeId + '/notes';
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

	// ── Live preview ───────────────────────────────────────────────────────────
	const bodyField = document.getElementById('field-body');
	const previewPlaceholder = document.getElementById('preview-placeholder');
	const preview = document.getElementById('note-preview');
	let previewDebounceTimer = null;

	function updatePreview() {
		const markdown = bodyField.value.trim();

		if (markdown === '') {
			preview.innerHTML = '';
			preview.classList.add('d-none');
			previewPlaceholder.classList.remove('d-none');
			return;
		}

		fetch('/api/bikes/notes/preview', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', apikey: apiKey },
			body: JSON.stringify({ markdown }),
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			preview.innerHTML = data.html || '';
			preview.classList.remove('d-none');
			previewPlaceholder.classList.add('d-none');
		})
		.catch(function () {});
	}

	bodyField.addEventListener('input', function () {
		clearTimeout(previewDebounceTimer);
		previewDebounceTimer = setTimeout(updatePreview, 400);
	});

	if (action === 'edit' && bodyField.value.trim() !== '') {
		updatePreview();
	}

	// ── Form submit ────────────────────────────────────────────────────────────
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		hideAlert();
		clearFieldErrors();

		const title = document.getElementById('field-title').value.trim();
		const body = bodyField.value.trim();

		if (!body) {
			setFieldError('body', 'Body is required.');
			return;
		}

		setLoading(true);

		fetch(apiUrl, {
			method: apiMethod,
			headers: { 'Content-Type': 'application/json', apikey: apiKey },
			body: JSON.stringify({ title, body }),
		})
		.then(function (res) {
			return res.json().then(function (data) { return { status: res.status, data }; });
		})
		.then(function ({ status, data }) {
			setLoading(false);

			if (status === 200 || status === 201) {
				if (action === 'create') {
					window.location.href = '/admin/bikes/' + bikeId + '/notes/' + data.id + '/edit?created=1';
				} else {
					showAlert('success', 'Note updated successfully.');
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
		showAlert('success', 'Note created successfully.');
		window.history.replaceState({}, '', window.location.pathname);
	}

	// ── Media gallery (edit mode only) ─────────────────────────────────────────
	const gallery = document.getElementById('media-gallery');
	if (!gallery) return;

	const mediaAlert = document.getElementById('media-alert');
	const mediaUpload = document.getElementById('field-media-upload');
	const deleteMediaModal = new bootstrap.Modal(document.getElementById('modal-delete-media'));
	const btnConfirmDeleteMedia = document.getElementById('btn-confirm-delete-media');
	let pendingMediaId = null;

	function showMediaAlert(type, message) {
		mediaAlert.className = 'alert alert-' + type + ' mb-3';
		mediaAlert.textContent = message;
		mediaAlert.classList.remove('d-none');
	}

	function hideMediaAlert() {
		mediaAlert.className = 'alert d-none mb-3';
		mediaAlert.textContent = '';
	}

	function buildMediaTile(media) {
		let inner;

		if (media.mime_type.indexOf('image/') === 0) {
			inner = '<img src="' + media.url + '" alt="" class="img-fluid rounded" style="aspect-ratio: 1 / 1; object-fit: cover; width: 100%;">';
		} else if (media.mime_type === 'video/mp4') {
			inner = '<video src="' + media.url + '" controls class="rounded" style="width: 100%; aspect-ratio: 1 / 1; object-fit: cover;"></video>';
		} else {
			inner = '<a href="' + media.url + '" target="_blank" rel="noopener noreferrer" class="d-flex flex-column align-items-center justify-content-center bg-body-secondary rounded text-secondary text-decoration-none p-2" style="aspect-ratio: 1 / 1; width: 100%;" title="Open PDF">'
				+ '<i class="bi bi-file-earmark-pdf fs-2" aria-hidden="true"></i><span class="small text-truncate w-100 text-center mt-1">PDF</span></a>';
		}

		const col = document.createElement('div');
		col.className = 'col-4 col-md-3 media-item';
		col.dataset.mediaId = media.id;
		col.innerHTML = '<div class="position-relative">'
			+ inner
			+ '<button type="button" class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-1 btn-media-delete" data-media-id="' + media.id + '" title="Delete">'
			+ '<i class="bi bi-trash3" aria-hidden="true"></i></button>'
			+ '<div class="d-flex justify-content-between mt-1">'
			+ '<button type="button" class="btn btn-sm btn-outline-primary btn-media-move" data-direction="up" data-media-id="' + media.id + '" title="Move earlier">'
			+ '<i class="bi bi-arrow-left" aria-hidden="true"></i></button>'
			+ '<button type="button" class="btn btn-sm btn-outline-primary btn-media-move" data-direction="down" data-media-id="' + media.id + '" title="Move later">'
			+ '<i class="bi bi-arrow-right" aria-hidden="true"></i></button>'
			+ '</div></div>';
		return col;
	}

	function sendReorder() {
		const ids = Array.from(gallery.querySelectorAll('.media-item')).map(function (el) {
			return parseInt(el.dataset.mediaId, 10);
		});

		fetch('/api/bikes/' + bikeId + '/notes/' + noteId + '/media/reorder', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', apikey: apiKey },
			body: JSON.stringify({ ids }),
		}).catch(function () {});
	}

	gallery.addEventListener('click', function (e) {
		const deleteBtn = e.target.closest('.btn-media-delete');
		if (deleteBtn) {
			pendingMediaId = deleteBtn.dataset.mediaId;
			deleteMediaModal.show();
			return;
		}

		const moveBtn = e.target.closest('.btn-media-move');
		if (moveBtn) {
			const item = moveBtn.closest('.media-item');
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

	btnConfirmDeleteMedia.addEventListener('click', function () {
		if (!pendingMediaId) return;

		btnConfirmDeleteMedia.disabled = true;

		fetch('/api/bikes/' + bikeId + '/notes/' + noteId + '/media/' + pendingMediaId, {
			method: 'DELETE',
			headers: { apikey: apiKey },
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			btnConfirmDeleteMedia.disabled = false;
			deleteMediaModal.hide();

			if (data.status === 'success') {
				const item = gallery.querySelector('.media-item[data-media-id="' + pendingMediaId + '"]');
				if (item) item.remove();
			} else {
				showMediaAlert('danger', data.message || 'Failed to delete media.');
			}

			pendingMediaId = null;
		})
		.catch(function () {
			btnConfirmDeleteMedia.disabled = false;
			deleteMediaModal.hide();
			showMediaAlert('danger', 'A network error occurred. Please try again.');
		});
	});

	mediaUpload.addEventListener('change', function () {
		const file = mediaUpload.files[0];
		if (!file) return;

		hideMediaAlert();

		const formData = new FormData();
		formData.append('media', file);

		mediaUpload.disabled = true;

		fetch('/api/bikes/' + bikeId + '/notes/' + noteId + '/media', {
			method: 'POST',
			headers: { apikey: apiKey },
			body: formData,
		})
		.then(function (res) {
			return res.json().then(function (data) { return { status: res.status, data }; });
		})
		.then(function ({ status, data }) {
			mediaUpload.disabled = false;
			mediaUpload.value = '';

			if (status === 201 && data.media) {
				gallery.appendChild(buildMediaTile(data.media));
			} else {
				showMediaAlert('danger', data.message || 'Failed to upload media.');
			}
		})
		.catch(function () {
			mediaUpload.disabled = false;
			mediaUpload.value = '';
			showMediaAlert('danger', 'A network error occurred. Please try again.');
		});
	});
});
