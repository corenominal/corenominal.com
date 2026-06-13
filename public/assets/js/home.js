document.addEventListener('DOMContentLoaded', () => {
	// -------------------------------------------------------------------------
	// GitHub activity heatmap
	// -------------------------------------------------------------------------

	const heatmapCells = document.querySelectorAll('.heatmap-cell[data-date]');
	const activityDate = document.getElementById('github-activity-date');
	const activityList = document.getElementById('github-activity-list');
	const activityData = window.githubActivityData || {};

	const escHtml = (str) => {
		const d = document.createElement('div');
		d.textContent = String(str ?? '');
		return d.innerHTML;
	};

	const renderActivityPanel = (date) => {
		const events  = activityData[date] || [];
		const dateObj = new Date(`${date}T12:00:00`);
		activityDate.textContent = dateObj.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });

		if (events.length === 0) {
			activityList.innerHTML = '<p class="small text-secondary mb-0">No activity on this day.</p>';
			return;
		}

		activityList.innerHTML = events.map((e) => `
			<a href="${escHtml(e.link)}" target="_blank" rel="noopener noreferrer"
			   class="d-flex align-items-start gap-2 py-1 text-body text-decoration-none">
				<i class="bi bi-${escHtml(e.icon)} mt-1 flex-shrink-0" aria-hidden="true"></i>
				<div class="small">
					<span class="badge text-bg-${escHtml(e.label_class)} me-1">${escHtml(e.label)}</span>
					<span class="text-secondary">${escHtml(e.repo)}</span>
					<div>${e.description}</div>
				</div>
			</a>
		`).join('');
	};

	const infoBtn = document.getElementById('github-activity-info');
	if (infoBtn && window.bootstrap) {
		new window.bootstrap.Popover(infoBtn, {
			trigger: 'focus',
			placement: 'top',
			content: 'This graph only shows activity to public GitHub repositories. Private repository activity is not included.',
		});
	}

	if (heatmapCells.length && activityDate && activityList) {
		const latestActiveDate = Object.keys(activityData)[0];

		if (latestActiveDate) {
			const latestCell = document.querySelector(`.heatmap-cell[data-date="${latestActiveDate}"]`);
			if (latestCell) {
				latestCell.classList.add('heatmap-cell--selected');
			}
		}

		heatmapCells.forEach((cell) => {
			cell.addEventListener('click', () => {
				heatmapCells.forEach((c) => c.classList.remove('heatmap-cell--selected'));
				cell.classList.add('heatmap-cell--selected');
				renderActivityPanel(cell.dataset.date);
			});
		});
	}


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

	const timelineItems   = document.querySelector('#timeline-items');
	const imageModalEl    = document.querySelector('#timeline-image-modal');
	const imageModalImg   = document.querySelector('#timeline-image-modal-img');
	const imageModalWrap  = document.querySelector('#timeline-image-modal-img-wrap');
	const imageModalCapt  = document.querySelector('#timeline-image-modal-caption');
	const imageModal      = imageModalEl && window.bootstrap
		? window.bootstrap.Modal.getOrCreateInstance(imageModalEl)
		: null;

	// -------------------------------------------------------------------------
	// Image preview modal
	// -------------------------------------------------------------------------

	if (timelineItems && imageModal && imageModalImg && imageModalCapt) {
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

			imageModalImg.src          = fullSrc;
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

	// -------------------------------------------------------------------------
	// Delete flow
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

				window.location.reload();
			} catch (error) {
				// eslint-disable-next-line no-alert
				alert(`Could not delete status: ${error.message}`);
			}
		});
	}

	// -------------------------------------------------------------------------
	// Edit flow — redirect to status timeline with edit form pre-filled
	// -------------------------------------------------------------------------

	if (timelineItems) {
		timelineItems.addEventListener('click', (event) => {
			const editBtn = event.target.closest('.status-edit-btn');

			if (!editBtn) {
				return;
			}

			window.location.href = `/status?edit_id=${editBtn.dataset.statusId}`;
		});
	}
});
