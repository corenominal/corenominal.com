document.addEventListener('DOMContentLoaded', function () {

	// ── Form elements ──────────────────────────────────────────────────────────
	const form       = document.getElementById('bookmark-form');
	const alertBox   = document.getElementById('form-alert');
	const btnSubmit  = document.getElementById('btn-submit');
	const btnSpinner = document.getElementById('btn-submit-spinner');

	const action = form.dataset.action;   // 'create' | 'edit'
	const uuid   = form.dataset.uuid;
	const apiKey = form.dataset.apiKey;

	const apiUrl    = action === 'edit' ? '/api/bookmarks/' + uuid : '/api/bookmarks';
	const apiMethod = action === 'edit' ? 'PUT' : 'POST';

	// ── Preview elements ───────────────────────────────────────────────────────
	const previewCard        = document.getElementById('bookmark-preview');
	const previewPlaceholder = document.getElementById('preview-placeholder');
	const previewTitle       = document.getElementById('preview-title');
	const previewFavicon     = document.getElementById('preview-favicon');
	const previewImageWrap   = document.getElementById('preview-image-wrap');
	const previewImage       = document.getElementById('preview-image');
	const previewNotes       = document.getElementById('preview-notes');
	const previewTags        = document.getElementById('preview-tags');
	const previewDate        = document.getElementById('preview-date');
	const previewPrivate     = document.getElementById('preview-private');

	function showPreviewImage(src, alt, onError) {
		previewImage.onerror = onError || null;
		previewImage.onload  = function () {
			previewImage.onload = null;
		};
		previewImage.alt = alt || '';
		previewImage.src = src;
		previewImageWrap.classList.remove('d-none');
	}

	// ── Tag badge management ───────────────────────────────────────────────────
	const tagInput      = document.getElementById('field-tag-input');
	const tagsHidden    = document.getElementById('field-tags');
	const tagsBadgeList = document.getElementById('tags-badge-list');
	const tagsDatalist  = document.getElementById('tags-datalist');
	let currentTags     = [];

	function escTag(str) {
		return str
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function syncTagsHidden() {
		tagsHidden.value = currentTags.join(', ');
	}

	function renderTagBadges() {
		tagsBadgeList.innerHTML = '';
		currentTags.forEach(function (tag) {
			const btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'badge text-bg-secondary border-0';
			btn.style.cursor = 'pointer';
			btn.setAttribute('aria-label', 'Remove tag: ' + tag);
			btn.innerHTML = escTag(tag) + ' <span aria-hidden="true">&times;</span>';
			btn.addEventListener('click', function () {
				removeTag(tag);
			});
			tagsBadgeList.appendChild(btn);
		});
	}

	function addTag(raw) {
		const tag = raw.trim();
		if (!tag) return;
		const exists = currentTags.some(function (t) {
			return t.toLowerCase() === tag.toLowerCase();
		});
		if (exists) return;
		currentTags.push(tag);
		syncTagsHidden();
		renderTagBadges();
		tagInput.classList.remove('is-invalid');
		updatePreview();
	}

	function removeTag(tag) {
		currentTags = currentTags.filter(function (t) { return t !== tag; });
		syncTagsHidden();
		renderTagBadges();
		updatePreview();
	}

	// Initialise badges from the existing hidden value (edit mode)
	(function initTags() {
		const initial = tagsHidden.value.trim();
		if (initial) {
			currentTags = initial.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
			renderTagBadges();
		}
	}());

	// Populate datalist from the tags API
	fetch('/api/bookmarks/tags', {
		method: 'GET',
		headers: { 'apikey': apiKey },
	})
	.then(function (res) { return res.json(); })
	.then(function (data) {
		(data.tags || []).forEach(function (tag) {
			const option = document.createElement('option');
			option.value = tag;
			tagsDatalist.appendChild(option);
		});
	})
	.catch(function () {});

	// Commit on Enter or comma
	tagInput.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' || e.key === ',') {
			e.preventDefault();
			if (tagInput.value.trim()) {
				addTag(tagInput.value);
				tagInput.value = '';
			}
		}
	});

	// Commit when the user types a trailing comma
	tagInput.addEventListener('input', function () {
		if (tagInput.value.endsWith(',')) {
			const raw = tagInput.value.slice(0, -1);
			addTag(raw);
			tagInput.value = '';
		}
	});

	// Commit on datalist selection (and on blur with uncommitted text)
	tagInput.addEventListener('change', function () {
		if (tagInput.value.trim()) {
			addTag(tagInput.value);
			tagInput.value = '';
		}
	});

	// ── Live preview update ────────────────────────────────────────────────────
	let faviconDebounceTimer     = null;
	let lastFaviconHost          = '';
	let notesDebounceTimer       = null;
	let lastNotesSent            = null;
	let screenshotDebounceTimer  = null;
	let lastScreenshotRequestUrl = null;
	let lastYoutubeId            = '';
	let capturedScreenshotFile   = '';

	function extractYoutubeVideoId(url) {
		let m = url.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/);
		if (m) return m[1];
		m = url.match(/youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|v\/)([a-zA-Z0-9_-]{11})/);
		return m ? m[1] : '';
	}

	function hasInspirationTag() {
		return currentTags.some(function (t) { return t.toLowerCase() === 'inspiration'; });
	}

	function showOriginalImage() {
		const existingImage = form.dataset.image || '';
		if (existingImage) {
			showPreviewImage('/uploads/bookmarks/media/' + existingImage, '');
		} else {
			previewImageWrap.classList.add('d-none');
			previewImage.src = '';
		}
	}

	function updatePreviewImage(url) {
		if (capturedScreenshotFile) {
			showPreviewImage('/uploads/bookmarks/media/' + capturedScreenshotFile, '', function () { previewImageWrap.classList.add('d-none'); });
			return;
		}

		if (action === 'edit' && form.dataset.image) {
			showOriginalImage();
			return;
		}

		const videoId = extractYoutubeVideoId(url);

		if (videoId) {
			if (videoId !== lastYoutubeId) {
				lastYoutubeId = videoId;
				clearTimeout(screenshotDebounceTimer);
				lastScreenshotRequestUrl = null;
				const thumbUrl = 'https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg';
				showPreviewImage(thumbUrl, '', function () { previewImageWrap.classList.add('d-none'); });
			}
			return;
		}

		if (lastYoutubeId !== '') {
			lastYoutubeId = '';
			previewImage.onerror = null;
		}

		if (!hasInspirationTag() || !url) {
			clearTimeout(screenshotDebounceTimer);
			lastScreenshotRequestUrl = null;
			showOriginalImage();
			return;
		}

		if (url === lastScreenshotRequestUrl) {
			return;
		}

		clearTimeout(screenshotDebounceTimer);
		screenshotDebounceTimer = setTimeout(function () {
			lastScreenshotRequestUrl = url;
			fetch('/api/bookmarks/screenshot/preview?url=' + encodeURIComponent(url), {
				method:  'GET',
				headers: { 'apikey': apiKey },
			})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (data.url) {
					showPreviewImage(data.url, '', function () { previewImageWrap.classList.add('d-none'); });
				} else {
					showOriginalImage();
				}
			})
			.catch(function () {
				showOriginalImage();
			});
		}, 1200);
	}

	function fetchNotesHtml(markdown) {
		if (markdown === '') {
			previewNotes.innerHTML = '';
			previewNotes.classList.add('d-none');
			return;
		}

		fetch('/api/bookmarks/markdown/preview', {
			method:  'POST',
			headers: { 'Content-Type': 'application/json', 'apikey': apiKey },
			body:    JSON.stringify({ markdown }),
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			const html = data.html || '';
			if (html) {
				previewNotes.innerHTML = html;
				previewNotes.classList.remove('d-none');
			} else {
				previewNotes.innerHTML = '';
				previewNotes.classList.add('d-none');
			}
		})
		.catch(function () {
			previewNotes.innerHTML = '';
			previewNotes.classList.add('d-none');
		});
	}

	function updatePreview() {
		const title = document.getElementById('field-title').value.trim();
		const url   = document.getElementById('field-url').value.trim();
		const tags  = document.getElementById('field-tags').value.trim();
		const notes = document.getElementById('field-notes').value.trim();
		const priv  = document.getElementById('field-private').checked;

		const hasMinimum = title !== '' && url !== '';

		previewCard.classList.toggle('d-none', !hasMinimum);
		previewPlaceholder.classList.toggle('d-none', hasMinimum);

		if (!hasMinimum) {
			clearTimeout(screenshotDebounceTimer);
			lastYoutubeId = '';
			lastScreenshotRequestUrl = null;
			return;
		}

		previewTitle.textContent = title || 'Untitled';
		previewTitle.href        = url || '#';

		const tagItems = tags.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
		if (tagItems.length > 0) {
			previewTags.innerHTML = tagItems
				.map(function (t) {
					return '<span class="badge text-bg-secondary fw-normal">' + t.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</span>';
				})
				.join('');
			previewTags.classList.remove('d-none');
		} else {
			previewTags.innerHTML = '';
			previewTags.classList.add('d-none');
		}

		if (notes !== lastNotesSent) {
			clearTimeout(notesDebounceTimer);
			notesDebounceTimer = setTimeout(function () {
				lastNotesSent = notes;
				fetchNotesHtml(notes);
			}, 400);
		}

		previewPrivate.classList.toggle('d-none', !priv);

		const now = new Date();
		previewDate.textContent = now.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

		let host = '';
		try { host = new URL(url).hostname; } catch (_) {}

		if (host && host !== lastFaviconHost) {
			clearTimeout(faviconDebounceTimer);
			faviconDebounceTimer = setTimeout(function () {
				lastFaviconHost = host;
				const faviconUrl = 'https://www.google.com/s2/favicons?domain=' + encodeURIComponent(host) + '&sz=32';
				previewFavicon.src = faviconUrl;
				previewFavicon.classList.remove('d-none');
				previewFavicon.onerror = function () { previewFavicon.classList.add('d-none'); };
			}, 600);
		} else if (!host) {
			previewFavicon.src = '';
			previewFavicon.classList.add('d-none');
			lastFaviconHost = '';
		}

		updatePreviewImage(url);
	}

	// ── Attach live preview listeners ──────────────────────────────────────────
	['field-title', 'field-url', 'field-notes'].forEach(function (id) {
		document.getElementById(id).addEventListener('input', updatePreview);
	});
	document.getElementById('field-private').addEventListener('change', updatePreview);

	updatePreview();

	if (action === 'edit') {
		const initialNotes = document.getElementById('field-notes').value.trim();
		if (initialNotes) {
			lastNotesSent = initialNotes;
			fetchNotesHtml(initialNotes);
		}
	}

	if (action === 'edit') {
		const existingUrl = document.getElementById('field-url').value.trim();
		try {
			const host = new URL(existingUrl).hostname;
			if (host) {
				lastFaviconHost    = host;
				previewFavicon.src = 'https://www.google.com/s2/favicons?domain=' + encodeURIComponent(host) + '&sz=32';
				previewFavicon.classList.remove('d-none');
				previewFavicon.onerror = function () { previewFavicon.classList.add('d-none'); };
			}
		} catch (_) {}
	}

	// ── Screenshot button ──────────────────────────────────────────────────────
	const btnScreenshot        = document.getElementById('btn-screenshot');
	const btnScreenshotSpinner = document.getElementById('btn-screenshot-spinner');
	const btnScreenshotIcon    = document.getElementById('btn-screenshot-icon');
	const screenshotStatus     = document.getElementById('screenshot-status');

	function updateScreenshotButtonState() {
		const url = document.getElementById('field-url').value.trim();
		let valid = false;
		try { new URL(url); valid = true; } catch (_) {}
		btnScreenshot.disabled = !valid;
	}

	document.getElementById('field-url').addEventListener('input', updateScreenshotButtonState);
	updateScreenshotButtonState();

	btnScreenshot.addEventListener('click', function () {
		const url = document.getElementById('field-url').value.trim();
		let valid = false;
		try { new URL(url); valid = true; } catch (_) {}
		if (!valid) return;

		btnScreenshot.disabled = true;
		btnScreenshotSpinner.classList.remove('d-none');
		btnScreenshotIcon.classList.add('d-none');
		screenshotStatus.textContent = '';
		screenshotStatus.classList.add('d-none');

		fetch('/api/bookmarks/screenshot/capture', {
			method:  'POST',
			headers: { 'Content-Type': 'application/json', 'apikey': apiKey },
			body:    JSON.stringify({ url }),
		})
		.then(function (res) {
			return res.json().then(function (data) { return { status: res.status, data }; });
		})
		.then(function ({ status, data }) {
			btnScreenshotSpinner.classList.add('d-none');
			btnScreenshotIcon.classList.remove('d-none');
			updateScreenshotButtonState();

			if (status === 200 && data.filename) {
				capturedScreenshotFile = data.filename;
				previewImage.src = '/uploads/bookmarks/media/' + data.filename;
				previewImage.alt = '';
				previewImage.onerror = function () { previewImageWrap.classList.add('d-none'); };
				previewImageWrap.classList.remove('d-none');
				screenshotStatus.textContent = 'Screenshot captured.';
				screenshotStatus.className = 'small text-success';
				screenshotStatus.classList.remove('d-none');
			} else {
				screenshotStatus.textContent = data.message || 'Screenshot capture failed.';
				screenshotStatus.className = 'small text-danger';
				screenshotStatus.classList.remove('d-none');
			}
		})
		.catch(function () {
			btnScreenshotSpinner.classList.add('d-none');
			btnScreenshotIcon.classList.remove('d-none');
			updateScreenshotButtonState();
			screenshotStatus.textContent = 'A network error occurred.';
			screenshotStatus.className = 'small text-danger';
			screenshotStatus.classList.remove('d-none');
		});
	});

	// ── Alert helpers ──────────────────────────────────────────────────────────
	function showAlert(type, message) {
		alertBox.className    = 'alert alert-' + type + ' mb-4';
		alertBox.textContent  = message;
		alertBox.classList.remove('d-none');
		alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	function hideAlert() {
		alertBox.className   = 'alert d-none mb-4';
		alertBox.textContent = '';
	}

	function clearFieldErrors() {
		form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
		form.querySelectorAll('.invalid-feedback').forEach(function (el) { el.textContent = ''; });
	}

	function setFieldError(field, message) {
		const inputId = field === 'tags' ? 'field-tag-input' : 'field-' + field;
		const input   = document.getElementById(inputId);
		const err     = document.getElementById('error-' + field);
		if (input) input.classList.add('is-invalid');
		if (err)   err.textContent = message;
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

		const title       = document.getElementById('field-title').value.trim();
		const url         = document.getElementById('field-url').value.trim();
		const tags        = document.getElementById('field-tags').value.trim();
		const notes       = document.getElementById('field-notes').value.trim();
		const isPrivate   = document.getElementById('field-private').checked ? 1 : 0;
		const isDashboard = document.getElementById('field-dashboard').checked ? 1 : 0;

		let hasErrors = false;

		if (!title) {
			setFieldError('title', 'Title is required.');
			hasErrors = true;
		}

		if (!url) {
			setFieldError('url', 'URL is required.');
			hasErrors = true;
		} else {
			try { new URL(url); } catch (_) {
				setFieldError('url', 'URL is not valid.');
				hasErrors = true;
			}
		}

		if (!tags) {
			setFieldError('tags', 'At least one tag is required.');
			tagInput.focus();
			hasErrors = true;
		}

		if (hasErrors) return;

		setLoading(true);

		fetch(apiUrl, {
			method:  apiMethod,
			headers: { 'Content-Type': 'application/json', 'apikey': apiKey },
			body:    JSON.stringify({ title, url, tags, notes, private: isPrivate, dashboard: isDashboard, image_file: capturedScreenshotFile }),
		})
		.then(function (res) {
			return res.json().then(function (data) { return { status: res.status, data }; });
		})
		.then(function ({ status, data }) {
			setLoading(false);

			if (status === 200 || status === 201) {
				if (action === 'create') {
					window.location.href = '/admin/bookmarks/' + data.uuid + '/edit?created=1';
				} else {
					showAlert('success', 'Bookmark updated successfully.');
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

	// ── Show success banner after redirect from create ─────────────────────────
	const params = new URLSearchParams(window.location.search);
	if (params.get('created') === '1') {
		showAlert('success', 'Bookmark created successfully.');
		window.history.replaceState({}, '', window.location.pathname);
	}

});
