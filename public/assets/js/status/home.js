document.addEventListener('DOMContentLoaded', () => {
	// -------------------------------------------------------------------------
	// Shared helpers
	// -------------------------------------------------------------------------

	const getCookie = (name) => {
		const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
		return match ? decodeURIComponent(match[1]) : '';
	};

	const authHeaders = () => {
		const userUuid = getCookie('user_uuid');
		const apikey   = getCookie('apikey');

		if (!userUuid || !apikey) {
			return {};
		}

		return { 'user-uuid': userUuid, apikey };
	};

	// -------------------------------------------------------------------------
	// Timeline infinite scroll
	// -------------------------------------------------------------------------

	const timelineItems   = document.querySelector('#timeline-items');
	const observerTarget  = document.querySelector('#timeline-observer');
	const loader          = document.querySelector('#timeline-loader');
	const searchInput     = document.querySelector('#timeline-search');
	const imageModalEl    = document.querySelector('#timeline-image-modal');
	const imageModalImg   = document.querySelector('#timeline-image-modal-img');
	const imageModalWrap  = document.querySelector('#timeline-image-modal-img-wrap');
	const imageModalCapt  = document.querySelector('#timeline-image-modal-caption');
	const imageModal      = imageModalEl && window.bootstrap
		? window.bootstrap.Modal.getOrCreateInstance(imageModalEl)
		: null;

	if (searchInput) {
		document.addEventListener('keydown', (event) => {
			const isFindShortcut = (event.metaKey || event.ctrlKey)
				&& !event.shiftKey
				&& !event.altKey
				&& event.key.toLowerCase() === 'f';

			if (!isFindShortcut) {
				return;
			}

			event.preventDefault();
			searchInput.focus();
			searchInput.select();
		});
	}

	if (timelineItems) {
		if (imageModal && imageModalImg && imageModalCapt) {
			timelineItems.addEventListener('click', (event) => {
				const clickedImage = event.target.closest('.status-media-img');

				if (!clickedImage) {
					return;
				}

				event.preventDefault();

				const fullSrc   = clickedImage.currentSrc || clickedImage.src;
				const altText   = clickedImage.getAttribute('alt') || 'Full size image';
				const imgWidth  = parseInt(clickedImage.dataset.width, 10) || 0;
				const imgHeight = parseInt(clickedImage.dataset.height, 10) || 0;

				imageModalImg.alt = altText;

				if (imageModalWrap) {
					imageModalWrap.style.aspectRatio = '';
					imageModalWrap.style.maxHeight   = '';
					imageModalWrap.style.maxWidth    = '';

					if (imgWidth > 0 && imgHeight > 0) {
						imageModalWrap.style.aspectRatio = `${imgWidth} / ${imgHeight}`;

						if (imgHeight > imgWidth) {
							imageModalWrap.style.maxHeight = '78vh';
							imageModalWrap.style.maxWidth  = `calc(78vh * ${imgWidth} / ${imgHeight})`;
						}
					}
				}

				imageModalImg.src     = fullSrc;
				imageModalCapt.textContent = altText;

				imageModal.show();
			});

			imageModalEl.addEventListener('hidden.bs.modal', () => {
				imageModalImg.src = '';
				imageModalImg.alt = '';
				imageModalCapt.textContent = '';

				if (imageModalWrap) {
					imageModalWrap.style.aspectRatio = '';
					imageModalWrap.style.maxHeight   = '';
					imageModalWrap.style.maxWidth    = '';
				}
			});
		}
	}

	if (timelineItems && observerTarget && loader) {
		const state = {
			isLoading: false,
			offset:    Number(timelineItems.dataset.offset || 0),
			limit:     Number(timelineItems.dataset.limit || 20),
			hasMore:   timelineItems.dataset.hasMore === '1',
			loadUrl:   timelineItems.dataset.loadUrl || '/status/timeline/load',
			query:     timelineItems.dataset.search || '',
		};

		const setLoaderMessage = (message) => {
			loader.style.display = 'block';
			loader.textContent   = message;
		};

		const loadMoreStatuses = async () => {
			if (state.isLoading || !state.hasMore) {
				return;
			}

			state.isLoading = true;
			setLoaderMessage('Loading…');

			try {
				const url = new URL(state.loadUrl, window.location.origin);
				url.searchParams.set('offset', String(state.offset));
				url.searchParams.set('limit', String(state.limit));

				if (state.query.trim() !== '') {
					url.searchParams.set('q', state.query.trim());
				}

				const response = await fetch(url.toString(), {
					method: 'GET',
					headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				});

				if (!response.ok) {
					throw new Error(`Timeline request failed (${response.status})`);
				}

				const payload = await response.json();

				if (typeof payload.html === 'string' && payload.html.trim() !== '') {
					timelineItems.insertAdjacentHTML('beforeend', payload.html);
				}

				state.offset  = Number(payload.nextOffset || state.offset);
				state.hasMore = Boolean(payload.hasMore);

				if (!state.hasMore) {
					setLoaderMessage('End of timeline.');
				} else {
					loader.style.display = 'none';
				}
			} catch (error) {
				setLoaderMessage('Could not load more statuses. Try again shortly.');
				// eslint-disable-next-line no-console
				console.error(error);
			} finally {
				state.isLoading = false;
			}
		};

		if (!state.hasMore) {
			setLoaderMessage('End of timeline.');
		} else {
			const observer = new IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						loadMoreStatuses();
					}
				});
			}, { rootMargin: '500px 0px', threshold: 0 });

			observer.observe(observerTarget);
		}
	}

	// -------------------------------------------------------------------------
	// Delete button — modal init (shared: home page + permalink page)
	// -------------------------------------------------------------------------

	const deleteModalEl    = document.querySelector('#delete-status-modal');
	const deleteConfirmBtn = document.querySelector('#delete-status-confirm-btn');
	const deleteModal      = deleteModalEl && window.bootstrap
		? window.bootstrap.Modal.getOrCreateInstance(deleteModalEl)
		: null;

	let pendingDeleteId = null;

	if (timelineItems && deleteModal) {
		timelineItems.addEventListener('click', (event) => {
			const deleteBtn = event.target.closest('.status-delete-btn');

			if (!deleteBtn) {
				return;
			}

			pendingDeleteId = parseInt(deleteBtn.dataset.statusId, 10);
			deleteModal.show();
		});
	}

	// -------------------------------------------------------------------------
	// Compose / edit form (admin only)
	// -------------------------------------------------------------------------

	const composeSection = document.querySelector('#timeline-compose');

	if (!composeSection) {
		// Permalink page: wire up edit redirect and delete confirm without compose form.
		if (timelineItems) {
			timelineItems.addEventListener('click', (event) => {
				const editBtn = event.target.closest('.status-edit-btn');

				if (!editBtn) {
					return;
				}

				window.location.href = `/status?edit_id=${editBtn.dataset.statusId}`;
			});
		}

		if (deleteConfirmBtn && deleteModal) {
			deleteConfirmBtn.addEventListener('click', async () => {
				if (!pendingDeleteId) {
					return;
				}

				const id = pendingDeleteId;
				pendingDeleteId = null;
				deleteModal.hide();

				try {
					const response = await fetch(`/api/status/statuses/${id}`, {
						method: 'DELETE',
						headers: { ...authHeaders(), Accept: 'application/json' },
					});

					if (!response.ok) {
						const body = await response.json().catch(() => ({}));
						throw new Error(body.error || `Delete failed (${response.status})`);
					}

					window.location.href = '/status';
				} catch (error) {
					// eslint-disable-next-line no-alert
					alert(`Could not delete status: ${error.message}`);
				}
			});
		}

		return;
	}

	const composeForm          = document.querySelector('#compose-form');
	const composeStatusIdEl    = document.querySelector('#compose-status-id');
	const composeContentEl     = document.querySelector('#compose-content');
	const composeTitleEl       = document.querySelector('#compose-form-title');
	const composeCancelBtn     = document.querySelector('#compose-cancel-btn');
	const composeSubmitBtn     = document.querySelector('#compose-submit-btn');
	const composeStatusMsg     = document.querySelector('#compose-status-msg');
	const composeAddVideoBtn   = document.querySelector('#compose-add-video-btn');
	const composePendingEl     = document.querySelector('#compose-pending-uploads');
	const composeExistingEl    = document.querySelector('#compose-existing-media');
	const composeExistingList  = document.querySelector('#compose-existing-media-list');
	const composeMastodonSwitch = document.querySelector('#compose-mastodon-switch');
	const composeCharCount     = document.querySelector('#compose-char-count');

	const CHAR_LIMIT = 500;

	const updateCharCount = () => {
		const remaining = CHAR_LIMIT - composeContentEl.value.length;

		if (composeCharCount) {
			composeCharCount.textContent = remaining;
			composeCharCount.classList.toggle('text-danger', remaining < 0);
			composeCharCount.classList.toggle('text-warning', remaining >= 0 && remaining <= 50);
			composeCharCount.classList.toggle('text-secondary', remaining > 50);
		}

		updateAiBtn?.();
	};

	composeContentEl.addEventListener('input', updateCharCount);

	// -------------------------------------------------------------------------
	// AI rewrite
	// -------------------------------------------------------------------------

	const aiRewriteBtn      = document.querySelector('#ai-rewrite-btn');
	const aiRewriteCard     = document.querySelector('#ai-rewrite-card');
	const aiRewriteCardBody = document.querySelector('#ai-rewrite-card-body');
	const aiRewriteDismiss  = document.querySelector('#ai-rewrite-dismiss');

	const showRewriteCard = () => aiRewriteCard?.classList.remove('d-none');
	const hideRewriteCard = () => aiRewriteCard?.classList.add('d-none');

	if (aiRewriteDismiss) {
		aiRewriteDismiss.addEventListener('click', hideRewriteCard);
	}

	const MODEL_STORAGE_KEY = 'ollama_selected_model';
	const getSelectedModel  = () => localStorage.getItem(MODEL_STORAGE_KEY) || '';
	const setSelectedModel  = (m) => localStorage.setItem(MODEL_STORAGE_KEY, m);

	const aiSettingsBtn       = document.querySelector('#ai-settings-btn');
	const aiSettingsModalEl   = document.querySelector('#ai-settings-modal');
	const aiSettingsModalBody = document.querySelector('#ai-settings-modal-body');
	const aiSettingsModal     = aiSettingsModalEl && window.bootstrap
		? window.bootstrap.Modal.getOrCreateInstance(aiSettingsModalEl)
		: null;

	let availableModels = [];

	const renderModelList = () => {
		if (!aiSettingsModalBody) return;

		if (availableModels.length === 0) {
			aiSettingsModalBody.innerHTML = '<p class="text-secondary mb-0">No models available.</p>';
			return;
		}

		const selectedModel = getSelectedModel();
		const list = document.createElement('ul');
		list.className = 'list-group list-group-flush';

		availableModels.forEach((model) => {
			const item = document.createElement('li');
			item.className    = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between';
			item.style.cursor = 'pointer';
			item.innerHTML    = `<span>${model}</span>${model === selectedModel ? '<i class="bi bi-check-circle-fill text-primary" aria-hidden="true"></i>' : ''}`;
			item.addEventListener('click', () => {
				setSelectedModel(model);
				renderModelList();
			});
			list.appendChild(item);
		});

		const hint = document.createElement('p');
		hint.className   = 'text-secondary small mt-3 mb-0';
		hint.textContent = 'The selected model will be used for all AI actions.';

		aiSettingsModalBody.innerHTML = '';
		aiSettingsModalBody.appendChild(list);
		aiSettingsModalBody.appendChild(hint);
	};

	(async () => {
		try {
			const response = await fetch('/api/ai/ollama/list', {
				method: 'GET',
				headers: { ...authHeaders(), Accept: 'application/json' },
			});

			if (!response.ok) return;

			const data = await response.json();
			availableModels = data.models || [];

			if (availableModels.length > 0) {
				if (!getSelectedModel() || !availableModels.includes(getSelectedModel())) {
					setSelectedModel(availableModels[0]);
				}

				if (aiSettingsBtn) {
					aiSettingsBtn.disabled = false;
				}
			}
		} catch { /* ignore — fall back to stored or default model */ }
	})();

	if (aiSettingsBtn) {
		aiSettingsBtn.addEventListener('click', () => {
			renderModelList();
			aiSettingsModal?.show();
		});
	}

	const updateAiBtn = () => {
		if (aiRewriteBtn) {
			aiRewriteBtn.disabled = !composeContentEl.value.trim();
		}
	};

	const fetchAiRewrites = async (sourceText) => {
		const text = sourceText || composeContentEl.value.trim();
		if (!text) return;

		aiRewriteCardBody.innerHTML = '<div class="d-flex align-items-center gap-2 text-secondary p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Generating rewrites…</div>';
		showRewriteCard();

		const buildRetryBtn = () => {
			const btn = document.createElement('button');
			btn.type      = 'button';
			btn.className = 'btn btn-sm btn-outline-secondary';
			btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Try again';
			btn.addEventListener('click', () => fetchAiRewrites(text));
			return btn;
		};

		try {
			const response = await fetch('/api/ai/status/rewrite', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					...authHeaders(),
				},
				body: JSON.stringify({ text, model: getSelectedModel() || 'gemma4:e4b', expand: true }),
			});

			if (!response.ok) {
				throw new Error(`Request failed (${response.status})`);
			}

			const data        = await response.json();
			const suggestions = data.suggestions || [];

			aiRewriteCardBody.innerHTML = '';

			if (suggestions.length === 0) {
				const wrap = document.createElement('div');
				wrap.className = 'p-3 d-flex align-items-center gap-2';
				const p = document.createElement('p');
				p.className   = 'text-secondary mb-0 flex-grow-1';
				p.textContent = 'No suggestions were returned.';
				wrap.appendChild(p);
				wrap.appendChild(buildRetryBtn());
				aiRewriteCardBody.appendChild(wrap);
				return;
			}

			const list = document.createElement('ul');
			list.className = 'list-group list-group-flush';

			suggestions.forEach((suggestion) => {
				const item = document.createElement('li');
				item.className = 'list-group-item d-flex align-items-start gap-2 py-2';

				const label = document.createElement('span');
				label.className   = 'flex-grow-1 small';
				label.textContent = suggestion;

				const useBtn = document.createElement('button');
				useBtn.type      = 'button';
				useBtn.className = 'btn btn-sm btn-outline-primary flex-shrink-0';
				useBtn.innerHTML = '<i class="bi bi-check-lg me-1" aria-hidden="true"></i>Use';
				useBtn.addEventListener('click', () => {
					composeContentEl.value = suggestion;
					updateCharCount();
					hideRewriteCard();
				});

				const drillBtn = document.createElement('button');
				drillBtn.type      = 'button';
				drillBtn.className = 'btn btn-sm btn-outline-secondary flex-shrink-0';
				drillBtn.innerHTML = '<i class="bi bi-arrow-return-right me-1" aria-hidden="true"></i>Drill down';
				drillBtn.addEventListener('click', () => fetchAiRewrites(suggestion));

				item.appendChild(label);
				item.appendChild(useBtn);
				item.appendChild(drillBtn);
				list.appendChild(item);
			});

			const footer = document.createElement('div');
			footer.className = 'd-flex align-items-center justify-content-between gap-2 p-2 border-top';

			const hint = document.createElement('p');
			hint.className   = 'text-secondary small mb-0';
			hint.textContent = sourceText
				? `Rewrites of: "${sourceText.length > 60 ? sourceText.slice(0, 60) + '…' : sourceText}"`
				: 'Rewrites of your current status.';

			footer.appendChild(hint);
			footer.appendChild(buildRetryBtn());

			aiRewriteCardBody.appendChild(list);
			aiRewriteCardBody.appendChild(footer);

		} catch (err) {
			const wrap = document.createElement('div');
			wrap.className = 'p-3 d-flex align-items-center gap-2';
			const p = document.createElement('p');
			p.className   = 'text-danger mb-0 flex-grow-1';
			p.textContent = `Could not fetch rewrites: ${err.message}`;
			aiRewriteCardBody.innerHTML = '';
			wrap.appendChild(p);
			wrap.appendChild(buildRetryBtn());
			aiRewriteCardBody.appendChild(wrap);
		}
	};

	if (aiRewriteBtn) {
		aiRewriteBtn.addEventListener('click', () => fetchAiRewrites());
	}

	// -------------------------------------------------------------------------
	// Media state
	// -------------------------------------------------------------------------

	const mediaState = {
		pending:  [],
		existing: [],
		removed:  new Set(),
	};

	const setComposeMsg = (msg, type = 'info') => {
		const colours = { info: 'text-secondary', success: 'text-success', error: 'text-danger' };
		composeStatusMsg.className = `ms-auto text-end small ${colours[type] || ''}`;
		composeStatusMsg.textContent = msg;
	};

	const resetCompose = () => {
		composeStatusIdEl.value    = '0';
		composeContentEl.value     = '';
		composeTitleEl.textContent = 'New Status';
		composeSubmitBtn.innerHTML = '<i class="bi bi-send me-1" aria-hidden="true"></i>Post';
		composeCancelBtn.classList.add('d-none');
		setComposeMsg('');
		composePendingEl.innerHTML    = '';
		composeExistingList.innerHTML = '';
		composeExistingEl.classList.add('d-none');
		mediaState.pending  = [];
		mediaState.existing = [];
		mediaState.removed.clear();
		updateCharCount();

		const draftIdEl = document.querySelector('#compose-draft-id');

		if (draftIdEl) {
			draftIdEl.value = '0';
		}

		const mastodonWrap = document.querySelector('#compose-mastodon-switch')?.closest('.form-check');

		if (mastodonWrap) {
			mastodonWrap.classList.remove('d-none');
		}

		if (composeMastodonSwitch) {
			composeMastodonSwitch.checked = true;
		}
	};

	const setComposeLoading = (isLoading) => {
		composeSubmitBtn.disabled   = isLoading;
		composeAddVideoBtn.disabled = isLoading;
		const isEditMode = composeStatusIdEl.value !== '0';
		const label = isLoading
			? (isEditMode ? 'Saving…' : 'Posting…')
			: (isEditMode ? '<i class="bi bi-pencil me-1" aria-hidden="true"></i>Update' : '<i class="bi bi-send me-1" aria-hidden="true"></i>Post');
		composeSubmitBtn.innerHTML = isLoading
			? `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${label}`
			: label;
	};

	// ---- existing media items ----

	const buildExistingMediaItem = (media) => {
		const item = document.createElement('div');
		item.className  = 'd-flex align-items-center gap-2 mb-2';
		item.dataset.id = String(media.id);

		const isVideo  = media.mime_type === 'video/mp4';
		const thumbSrc = media.url || '';
		const preview  = isVideo
			? `<video class="rounded" src="${thumbSrc}" muted preload="none" style="width:48px;height:48px;object-fit:cover;"></video>`
			: `<img class="rounded" src="${thumbSrc}" alt="${media.description || ''}" style="width:48px;height:48px;object-fit:cover;">`;

		item.innerHTML = `
			${preview}
			<span class="flex-grow-1 text-truncate small">${media.description || '<em class="text-secondary">No description</em>'}</span>
			<button type="button" class="btn btn-sm btn-outline-primary" aria-label="Remove media">
				<i class="bi bi-x-lg" aria-hidden="true"></i>
			</button>`;

		item.querySelector('button').addEventListener('click', () => {
			mediaState.removed.add(media.id);
			mediaState.existing = mediaState.existing.filter((m) => m.id !== media.id);
			item.remove();

			if (mediaState.existing.length === 0) {
				composeExistingEl.classList.add('d-none');
			}
		});

		return item;
	};

	const renderExistingMedia = (mediaArray) => {
		composeExistingList.innerHTML = '';
		mediaArray.forEach((m) => composeExistingList.appendChild(buildExistingMediaItem(m)));

		if (mediaArray.length > 0) {
			composeExistingEl.classList.remove('d-none');
		} else {
			composeExistingEl.classList.add('d-none');
		}
	};

	// ---- pending upload items ----

	const ALLOWED_MIME_TYPES = new Set(['video/mp4', 'image/jpeg', 'image/png', 'image/gif', 'image/webp']);

	const setFileOnEntry = (entry, file, dropZone, previewEl) => {
		const label = dropZone.querySelector('.drop-label');

		if (!file || !ALLOWED_MIME_TYPES.has(file.type)) {
			dropZone.classList.add('is-invalid');
			label.textContent = 'Unsupported file type. Allowed: JPEG, PNG, GIF, WebP, MP4.';
			entry.file = null;
			if (entry.aiAltBtn) entry.aiAltBtn.classList.add('d-none');
			return;
		}

		entry.file = file;
		dropZone.classList.remove('is-invalid');
		label.textContent = file.name;

		const objectUrl = URL.createObjectURL(file);
		const isVideo   = file.type === 'video/mp4';
		const video     = previewEl.querySelector('video');
		const img       = previewEl.querySelector('img');

		if (isVideo) {
			video.src = objectUrl;
			video.classList.remove('d-none');
			img.src = '';
			img.classList.add('d-none');
			if (entry.aiAltBtn) entry.aiAltBtn.classList.add('d-none');
		} else {
			img.src = objectUrl;
			img.classList.remove('d-none');
			video.src = '';
			video.classList.add('d-none');
			if (entry.aiAltBtn) entry.aiAltBtn.classList.remove('d-none');
		}
	};

	const buildPendingUploadItem = () => {
		const wrapper = document.createElement('div');
		wrapper.className = 'border rounded p-2 mb-2';

		wrapper.innerHTML = `
			<div class="border rounded p-3 mb-2 text-center text-secondary drop-zone" role="button" tabindex="0" aria-label="Drop image or MP4 video here or click to browse" style="cursor:pointer;">
				<input type="file" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4" class="d-none drop-input" aria-hidden="true" tabindex="-1">
				<i class="bi bi-paperclip me-1" aria-hidden="true"></i>
				<span class="drop-label small">Drop image or MP4 here, or click to browse</span>
			</div>
			<div class="mb-2">
				<label class="form-label form-label-sm mb-1">Description / alt text <span class="text-danger" aria-hidden="true">*</span></label>
				<div class="input-group input-group-sm">
					<input type="text" maxlength="255" required class="form-control desc-input" placeholder="Describe the media (used as alt text)">
					<button type="button" class="btn btn-outline-secondary ai-alt-btn d-none" aria-label="Generate alt text with AI" title="Generate alt text with AI">
						<i class="bi bi-stars" aria-hidden="true"></i>
					</button>
				</div>
			</div>
			<div class="preview-wrap mb-2">
				<img class="d-none w-100 rounded" src="" alt="">
				<video class="d-none w-100 rounded" controls muted preload="none"></video>
			</div>
			<button type="button" class="btn btn-sm btn-outline-primary remove-pending-btn">Remove</button>`;

		const dropZone  = wrapper.querySelector('.drop-zone');
		const fileInput = wrapper.querySelector('.drop-input');
		const previewEl = wrapper.querySelector('.preview-wrap');
		const descInput = wrapper.querySelector('.desc-input');
		const aiAltBtn  = wrapper.querySelector('.ai-alt-btn');
		const removeBtn = wrapper.querySelector('.remove-pending-btn');

		const entry = { el: wrapper, file: null, descInput, aiAltBtn };
		mediaState.pending.push(entry);

		dropZone.addEventListener('click', () => fileInput.click());
		dropZone.addEventListener('keydown', (e) => {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				fileInput.click();
			}
		});

		dropZone.addEventListener('dragover', (e) => {
			e.preventDefault();
			dropZone.classList.add('border-primary');
		});
		['dragleave', 'dragend'].forEach((evt) => {
			dropZone.addEventListener(evt, () => dropZone.classList.remove('border-primary'));
		});
		dropZone.addEventListener('drop', (e) => {
			e.preventDefault();
			dropZone.classList.remove('border-primary');
			setFileOnEntry(entry, e.dataTransfer.files[0], dropZone, previewEl);
		});

		fileInput.addEventListener('change', () => {
			setFileOnEntry(entry, fileInput.files[0], dropZone, previewEl);
			fileInput.value = '';
		});

		removeBtn.addEventListener('click', () => {
			mediaState.pending = mediaState.pending.filter((p) => p !== entry);
			wrapper.remove();
		});

		descInput.addEventListener('input', () => {
			if (descInput.value.trim() !== '') {
				descInput.classList.remove('is-invalid');
			}
		});

		aiAltBtn.addEventListener('click', async () => {
			if (!entry.file) return;

			const icon = '<i class="bi bi-stars" aria-hidden="true"></i>';
			aiAltBtn.disabled = true;
			aiAltBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

			try {
				const base64 = await new Promise((resolve, reject) => {
					const reader = new FileReader();
					reader.onload  = () => resolve(reader.result.split(',')[1]);
					reader.onerror = reject;
					reader.readAsDataURL(entry.file);
				});

				const response = await fetch('/api/ai/images/alttext', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						...authHeaders(),
					},
					body: JSON.stringify({ image: base64, model: getSelectedModel() || 'gemma4:e4b' }),
				});

				if (!response.ok) {
					throw new Error(`Request failed (${response.status})`);
				}

				const data = await response.json();

				if (data.alt_text) {
					descInput.value = data.alt_text;
					descInput.classList.remove('is-invalid');
				}
			} catch (err) {
				aiAltBtn.innerHTML = '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i>';
				setTimeout(() => {
					aiAltBtn.innerHTML = icon;
					aiAltBtn.disabled  = false;
				}, 2000);
				// eslint-disable-next-line no-console
				console.error(err);
				return;
			}

			aiAltBtn.innerHTML = icon;
			aiAltBtn.disabled  = false;
		});

		return wrapper;
	};

	composeAddVideoBtn.addEventListener('click', () => {
		composePendingEl.appendChild(buildPendingUploadItem());
	});

	// ---- auto-trigger edit from URL param (?edit_id=N) ----

	const editIdParam = new URLSearchParams(window.location.search).get('edit_id');

	if (editIdParam) {
		history.replaceState(null, '', window.location.pathname);

		(async () => {
			try {
				const response = await fetch(`/api/status/statuses/${encodeURIComponent(editIdParam)}`, {
					headers: { ...authHeaders(), Accept: 'application/json' },
				});

				if (!response.ok) {
					return;
				}

				const { data } = await response.json();

				resetCompose();
				composeStatusIdEl.value    = String(data.id);
				composeContentEl.value     = data.content || '';
				updateCharCount();
				composeTitleEl.textContent = 'Edit Status';
				composeSubmitBtn.innerHTML = '<i class="bi bi-pencil me-1" aria-hidden="true"></i>Update';
				composeCancelBtn.classList.remove('d-none');

				const mastodonWrap = composeMastodonSwitch?.closest('.form-check');

				if (mastodonWrap) {
					mastodonWrap.classList.add('d-none');
				}

				mediaState.existing = (data.media || []).map((m) => ({ ...m }));
				renderExistingMedia(mediaState.existing);
				composeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
				composeContentEl.focus();
			} catch {
				// Ignore — the edit_id param may be stale or invalid.
			}
		})();
	}

	// ---- edit button handler (delegated to timeline items container) ----

	if (timelineItems) {
		timelineItems.addEventListener('click', (event) => {
			const editBtn = event.target.closest('.status-edit-btn');

			if (!editBtn) {
				return;
			}

			const article  = editBtn.closest('article[data-status-id]');
			const statusId = editBtn.dataset.statusId;
			const content  = article?.dataset.statusContent ?? '';
			let mediaItems = [];

			try {
				mediaItems = JSON.parse(article?.dataset.statusMedia || '[]');
			} catch {
				mediaItems = [];
			}

			resetCompose();

			composeStatusIdEl.value    = statusId;
			composeContentEl.value     = content;
			updateCharCount();
			composeTitleEl.textContent = 'Edit Status';
			composeSubmitBtn.innerHTML = '<i class="bi bi-pencil me-1" aria-hidden="true"></i>Update';
			composeCancelBtn.classList.remove('d-none');

			const mastodonWrap = composeMastodonSwitch?.closest('.form-check');

			if (mastodonWrap) {
				mastodonWrap.classList.add('d-none');
			}

			mediaState.existing = mediaItems.map((m) => ({ ...m }));
			renderExistingMedia(mediaState.existing);

			composeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
			composeContentEl.focus();
		});
	}

	composeCancelBtn.addEventListener('click', resetCompose);

	// ---- delete button confirm (home page) ----

	if (deleteConfirmBtn && deleteModal) {
		deleteConfirmBtn.addEventListener('click', async () => {
			if (!pendingDeleteId) {
				return;
			}

			const id = pendingDeleteId;
			pendingDeleteId = null;
			deleteModal.hide();

			try {
				const response = await fetch(`/api/status/statuses/${id}`, {
					method: 'DELETE',
					headers: { ...authHeaders(), Accept: 'application/json' },
				});

				if (!response.ok) {
					const body = await response.json().catch(() => ({}));
					throw new Error(body.error || `Delete failed (${response.status})`);
				}

				const article = timelineItems?.querySelector(`[data-status-id="${id}"]`);

				if (article) {
					article.remove();
				}

				if (composeStatusIdEl.value === String(id)) {
					resetCompose();
				}
			} catch (error) {
				// eslint-disable-next-line no-alert
				alert(`Could not delete status: ${error.message}`);
			}
		});
	}

	// ---- form submission (create / update) ----

	composeForm.addEventListener('submit', async (event) => {
		event.preventDefault();

		const content  = composeContentEl.value.trim();
		const statusId = parseInt(composeStatusIdEl.value, 10);
		const isEdit   = statusId > 0;

		if (content === '') {
			setComposeMsg('Status content is required.', 'error');
			composeContentEl.focus();
			return;
		}

		if (content.length > CHAR_LIMIT) {
			setComposeMsg(`Status must be ${CHAR_LIMIT} characters or fewer.`, 'error');
			composeContentEl.focus();
			return;
		}

		for (const entry of mediaState.pending) {
			if (!entry.file) {
				continue;
			}

			const desc = entry.descInput.value.trim();

			if (desc === '') {
				entry.descInput.classList.add('is-invalid');
				entry.descInput.focus();
				setComposeMsg('A description is required for each media item.', 'error');
				return;
			}
		}

		setComposeLoading(true);
		setComposeMsg('');

		try {
			const newMediaIds = [];

			const uploadWithProgress = (file, description, progressBar) => new Promise((resolve, reject) => {
				const formData = new FormData();
				formData.append('file', file);
				formData.append('description', description);

				const xhr     = new XMLHttpRequest();
				const headers = authHeaders();

				xhr.upload.addEventListener('progress', (e) => {
					if (!e.lengthComputable) return;
					const pct = Math.round((e.loaded / e.total) * 100);
					progressBar.style.width = `${pct}%`;
					progressBar.setAttribute('aria-valuenow', String(pct));
					progressBar.textContent = `${pct}%`;
				});

				xhr.addEventListener('load', () => {
					progressBar.style.width = '100%';
					progressBar.setAttribute('aria-valuenow', '100');
					progressBar.textContent = '100%';

					if (xhr.status < 200 || xhr.status >= 300) {
						let msg = `Media upload failed (${xhr.status})`;

						try {
							const body = JSON.parse(xhr.responseText);
							if (body.error) msg = body.error;
						} catch { /* ignore */ }

						reject(new Error(msg));
						return;
					}

					try {
						const data = JSON.parse(xhr.responseText);
						resolve(data.data.id);
					} catch {
						reject(new Error('Invalid response from media upload.'));
					}
				});

				xhr.addEventListener('error', () => reject(new Error('Network error during media upload.')));
				xhr.addEventListener('abort', () => reject(new Error('Media upload was cancelled.')));

				xhr.open('POST', '/api/status/media');
				Object.entries(headers).forEach(([k, v]) => xhr.setRequestHeader(k, v));
				xhr.setRequestHeader('Accept', 'application/json');
				xhr.send(formData);
			});

			for (const entry of mediaState.pending) {
				if (!entry.file) continue;

				let progressBar = entry.el.querySelector('.progress .progress-bar');

				if (!progressBar) {
					const progressWrap = document.createElement('div');
					progressWrap.className = 'progress mb-2';
					progressWrap.setAttribute('role', 'progressbar');
					progressWrap.innerHTML = '<div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">0%</div>';
					entry.el.querySelector('.remove-pending-btn').before(progressWrap);
					progressBar = progressWrap.querySelector('.progress-bar');
				} else {
					progressBar.style.width = '0%';
					progressBar.setAttribute('aria-valuenow', '0');
					progressBar.textContent = '0%';
				}

				const mediaId = await uploadWithProgress(
					entry.file,
					entry.descInput.value.trim(),
					progressBar,
				);
				newMediaIds.push(mediaId);
			}

			const keptIds     = mediaState.existing
				.filter((m) => !mediaState.removed.has(m.id))
				.map((m) => m.id);
			const allMediaIds = [...keptIds, ...newMediaIds];

			const formData = new FormData();
			formData.append('content', content);
			allMediaIds.forEach((id) => formData.append('media_ids[]', String(id)));

			if (!isEdit && composeMastodonSwitch) {
				formData.append('post_to_mastodon', composeMastodonSwitch.checked ? '1' : '0');
			}

			let statusRes;

			if (isEdit) {
				statusRes = await fetch(`/api/status/statuses/${statusId}`, {
					method: 'PATCH',
					headers: {
						...authHeaders(),
						Accept: 'application/json',
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({ content, media_ids: allMediaIds }),
				});
			} else {
				statusRes = await fetch('/api/status/statuses', {
					method: 'POST',
					headers: { ...authHeaders(), Accept: 'application/json' },
					body: formData,
				});
			}

			if (!statusRes.ok) {
				const body = await statusRes.json().catch(() => ({}));
				throw new Error(body.error || `Request failed (${statusRes.status})`);
			}

			setComposeMsg(isEdit ? 'Status updated.' : 'Status posted.', 'success');
			window.location.reload();

		} catch (error) {
			setComposeMsg(error.message, 'error');
			// eslint-disable-next-line no-console
			console.error(error);
		} finally {
			setComposeLoading(false);
		}
	});

	// -------------------------------------------------------------------------
	// Drafts
	// -------------------------------------------------------------------------

	const composeDraftIdEl = document.querySelector('#compose-draft-id');
	const saveDraftBtn     = document.querySelector('#compose-save-draft-btn');
	const draftsModalEl    = document.querySelector('#drafts-modal');
	const draftsModalBody  = document.querySelector('#drafts-modal-body');
	const draftsModal      = draftsModalEl && window.bootstrap
		? window.bootstrap.Modal.getOrCreateInstance(draftsModalEl)
		: null;

	const updateDraftsBadge = (count) => {
		const draftsBtn  = document.querySelector('#drafts-btn');
		const countBadge = document.querySelector('#drafts-count-badge');

		if (draftsBtn) {
			if (count <= 0) {
				draftsBtn.remove();
			} else if (countBadge) {
				countBadge.textContent = String(count);
			}

			return;
		}

		if (count > 0) {
			const aiBtn = document.querySelector('#ai-rewrite-btn');

			if (aiBtn) {
				const btn = document.createElement('button');
				btn.type             = 'button';
				btn.className        = 'btn btn-sm btn-outline-primary';
				btn.id               = 'drafts-btn';
				btn.dataset.bsToggle = 'modal';
				btn.dataset.bsTarget = '#drafts-modal';
				btn.innerHTML        = '<i class="bi bi-journal-text me-1" aria-hidden="true"></i>Drafts <span class="badge text-bg-secondary ms-1" id="drafts-count-badge">' + count + '</span>';
				aiBtn.before(btn);
			}
		}
	};

	const renderDraftsList = (drafts) => {
		if (!draftsModalBody) return;

		if (drafts.length === 0) {
			draftsModalBody.innerHTML = '<p class="text-secondary mb-0">You have no saved drafts.</p>';
			return;
		}

		const list = document.createElement('ul');
		list.className = 'list-group list-group-flush';

		drafts.forEach((draft) => {
			const item = document.createElement('li');
			item.className       = 'list-group-item px-0';
			item.dataset.draftId = String(draft.id);

			const preview  = draft.content
				? draft.content.substring(0, 120) + (draft.content.length > 120 ? '…' : '')
				: '<em class="text-secondary">Empty draft</em>';
			const mediaNote = draft.media && draft.media.length > 0
				? `<span class="badge text-bg-secondary ms-1">${draft.media.length} media</span>`
				: '';

			item.innerHTML = `
				<div class="d-flex align-items-start gap-3">
					<div class="flex-grow-1 small">
						<span>${preview}</span>${mediaNote}
					</div>
					<div class="d-flex gap-2 flex-shrink-0">
						<button type="button" class="btn btn-sm btn-outline-primary drafts-edit-btn" data-draft-id="${draft.id}">Edit</button>
						<button type="button" class="btn btn-sm btn-outline-primary drafts-delete-btn" data-draft-id="${draft.id}">Delete</button>
					</div>
				</div>`;

			list.appendChild(item);
		});

		draftsModalBody.innerHTML = '';
		draftsModalBody.appendChild(list);

		list.querySelectorAll('.drafts-edit-btn').forEach((btn) => {
			btn.addEventListener('click', async () => {
				const draftId = parseInt(btn.dataset.draftId, 10);
				const draft   = drafts.find((d) => Number(d.id) === draftId);

				if (!draft) return;

				btn.disabled = true;

				try {
					const response = await fetch(`/api/status/drafts/${draftId}`, {
						method: 'DELETE',
						headers: { ...authHeaders(), Accept: 'application/json' },
					});

					if (!response.ok) {
						const body = await response.json().catch(() => ({}));
						throw new Error(body.error || `Delete failed (${response.status})`);
					}
				} catch (error) {
					btn.disabled = false;
					// eslint-disable-next-line no-alert
					alert(`Could not load draft into editor: ${error.message}`);
					return;
				}

				const remaining = drafts.filter((d) => Number(d.id) !== draftId).length;
				updateDraftsBadge(remaining);

				resetCompose();
				composeContentEl.value     = draft.content || '';
				updateCharCount();
				composeTitleEl.textContent = 'New Status';
				composeSubmitBtn.innerHTML = '<i class="bi bi-send me-1" aria-hidden="true"></i>Post';

				const mastodonWrap = composeMastodonSwitch?.closest('.form-check');
				if (mastodonWrap) mastodonWrap.classList.remove('d-none');

				if (draft.media && draft.media.length > 0) {
					mediaState.existing = draft.media.map((m) => ({ ...m }));
					renderExistingMedia(mediaState.existing);
				}

				draftsFocusOnClose = true;
				draftsModal.hide();
			});
		});

		list.querySelectorAll('.drafts-delete-btn').forEach((btn) => {
			btn.addEventListener('click', async () => {
				const draftId = parseInt(btn.dataset.draftId, 10);
				btn.disabled  = true;

				try {
					const response = await fetch(`/api/status/drafts/${draftId}`, {
						method: 'DELETE',
						headers: { ...authHeaders(), Accept: 'application/json' },
					});

					if (!response.ok) {
						const body = await response.json().catch(() => ({}));
						throw new Error(body.error || `Delete failed (${response.status})`);
					}

					const idx = drafts.findIndex((d) => Number(d.id) === draftId);
					if (idx !== -1) drafts.splice(idx, 1);

					const li = list.querySelector(`li[data-draft-id="${draftId}"]`);
					if (li) li.remove();

					updateDraftsBadge(drafts.length);

					if (drafts.length === 0) {
						draftsModalBody.innerHTML = '<p class="text-secondary mb-0">You have no saved drafts.</p>';
					}
				} catch (error) {
					btn.disabled = false;
					// eslint-disable-next-line no-alert
					alert(`Could not delete draft: ${error.message}`);
				}
			});
		});
	};

	let draftsFocusOnClose = false;

	if (draftsModalEl) {
		draftsModalEl.addEventListener('hidden.bs.modal', () => {
			if (draftsFocusOnClose) {
				draftsFocusOnClose = false;
				composeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
				composeContentEl.focus();
			}
		});

		draftsModalEl.addEventListener('show.bs.modal', async () => {
			if (draftsModalBody) {
				draftsModalBody.innerHTML = '<p class="text-secondary">Loading drafts…</p>';
			}

			try {
				const response = await fetch('/api/status/drafts', {
					method: 'GET',
					headers: { ...authHeaders(), Accept: 'application/json' },
				});

				if (!response.ok) {
					throw new Error(`Request failed (${response.status})`);
				}

				const payload = await response.json();
				renderDraftsList(payload.data || []);
			} catch (error) {
				if (draftsModalBody) {
					draftsModalBody.innerHTML = '<p class="text-danger mb-0">Could not load drafts. Please try again.</p>';
				}

				// eslint-disable-next-line no-console
				console.error(error);
			}
		});
	}

	if (saveDraftBtn) {
		saveDraftBtn.addEventListener('click', async (event) => {
			event.preventDefault();

			const content = composeContentEl.value.trim();
			const draftId = composeDraftIdEl ? parseInt(composeDraftIdEl.value, 10) : 0;

			if (content === '') {
				setComposeMsg('Cannot save an empty draft.', 'error');
				return;
			}

			saveDraftBtn.disabled = true;
			setComposeMsg('Saving draft…', 'info');

			try {
				const newMediaIds = [];

				for (const entry of mediaState.pending) {
					if (!entry.file) continue;

					const formData = new FormData();
					formData.append('file', entry.file);
					formData.append('description', entry.descInput.value.trim() || 'Draft media');

					const mediaRes = await fetch('/api/status/media', {
						method: 'POST',
						headers: { ...authHeaders(), Accept: 'application/json' },
						body: formData,
					});

					if (!mediaRes.ok) {
						const body = await mediaRes.json().catch(() => ({}));
						throw new Error(body.error || `Media upload failed (${mediaRes.status})`);
					}

					const mediaData = await mediaRes.json();
					newMediaIds.push(mediaData.data.id);
				}

				const keptIds     = mediaState.existing
					.filter((m) => !mediaState.removed.has(m.id))
					.map((m) => m.id);
				const allMediaIds = [...keptIds, ...newMediaIds];

				const formData = new FormData();
				formData.append('content', content);
				allMediaIds.forEach((id) => formData.append('media_ids[]', String(id)));

				let draftRes;

				if (draftId > 0) {
					draftRes = await fetch(`/api/status/drafts/${draftId}`, {
						method: 'PATCH',
						headers: { ...authHeaders(), Accept: 'application/json', 'Content-Type': 'application/json' },
						body: JSON.stringify({ content, media_ids: allMediaIds }),
					});
				} else {
					draftRes = await fetch('/api/status/drafts', {
						method: 'POST',
						headers: { ...authHeaders(), Accept: 'application/json' },
						body: formData,
					});
				}

				if (!draftRes.ok) {
					const body = await draftRes.json().catch(() => ({}));
					throw new Error(body.error || `Draft save failed (${draftRes.status})`);
				}

				const draftPayload = await draftRes.json();

				if (composeDraftIdEl) {
					composeDraftIdEl.value = String(draftPayload.data.id);
				}

				const listRes = await fetch('/api/status/drafts', {
					method: 'GET',
					headers: { ...authHeaders(), Accept: 'application/json' },
				});

				if (listRes.ok) {
					const listPayload = await listRes.json();
					updateDraftsBadge((listPayload.data || []).length);
				}

				setComposeMsg('Draft saved.', 'success');
				resetCompose();
			} catch (error) {
				setComposeMsg(error.message, 'error');
				// eslint-disable-next-line no-console
				console.error(error);
			} finally {
				saveDraftBtn.disabled = false;
			}
		});
	}
});
