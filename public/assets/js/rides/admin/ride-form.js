document.addEventListener('DOMContentLoaded', function () {

	const alertBox = document.getElementById('form-alert');

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

	// ── Upload (create mode) ─────────────────────────────────────────────────
	const uploadCard = document.getElementById('upload-card');

	if (uploadCard) {
		const apiKey = uploadCard.dataset.apiKey;
		const fileInput = document.getElementById('field-fit-upload');
		const rideTypeInput = document.getElementById('field-ride-type');
		const btnUpload = document.getElementById('btn-upload');
		const btnUploadSpinner = document.getElementById('btn-upload-spinner');

		btnUpload.addEventListener('click', function () {
			hideAlert();

			const file = fileInput.files[0];
			if (!file) {
				showAlert('danger', 'Please choose a .fit file to upload.');
				return;
			}

			const formData = new FormData();
			formData.append('fit', file);
			formData.append('ride_type', rideTypeInput.value);

			btnUpload.disabled = true;
			btnUploadSpinner.classList.remove('d-none');

			fetch('/api/rides/upload', {
				method: 'POST',
				headers: { apikey: apiKey },
				body: formData,
			})
			.then(function (res) {
				return res.json().then(function (data) { return { status: res.status, data }; });
			})
			.then(function ({ status, data }) {
				if (status === 201 && data.id) {
					window.location.href = '/admin/rides/' + data.id + '/edit?created=1';
					return;
				}

				btnUpload.disabled = false;
				btnUploadSpinner.classList.add('d-none');
				showAlert('danger', data.message || 'Failed to upload ride.');
			})
			.catch(function () {
				btnUpload.disabled = false;
				btnUploadSpinner.classList.add('d-none');
				showAlert('danger', 'A network error occurred. Please check your connection and try again.');
			});
		});

		return;
	}

	// ── Edit mode ─────────────────────────────────────────────────────────────
	const form = document.getElementById('ride-form');
	if (!form) return;

	const btnSubmit = document.getElementById('btn-submit');
	const btnSpinner = document.getElementById('btn-submit-spinner');

	const rideId = form.dataset.id;
	const apiKey = form.dataset.apiKey;

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

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		hideAlert();
		clearFieldErrors();

		const titleValue = document.getElementById('field-title').value.trim();
		const bikeId = document.getElementById('field-bike').value;
		const rideType = document.getElementById('field-ride-type').value;
		const distanceKm = document.getElementById('field-distance').value.trim();
		const notes = document.getElementById('field-notes').value.trim();

		setLoading(true);

		fetch('/api/rides/' + rideId, {
			method: 'PUT',
			headers: { 'Content-Type': 'application/json', apikey: apiKey },
			body: JSON.stringify({
				title: titleValue,
				notes,
				bike_id: bikeId,
				ride_type: rideType,
				distance_km: distanceKm,
			}),
		})
		.then(function (res) {
			return res.json().then(function (data) { return { status: res.status, data }; });
		})
		.then(function ({ status, data }) {
			setLoading(false);

			if (status === 200) {
				showAlert('success', 'Ride updated successfully.');
				window.scrollTo({ top: 0, behavior: 'smooth' });
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

	// ── Success banner after redirect from upload ──────────────────────────────
	const params = new URLSearchParams(window.location.search);
	if (params.get('created') === '1') {
		showAlert('success', 'Ride uploaded successfully.');
		window.history.replaceState({}, '', window.location.pathname);
	}

	// ── Elevation chart ──────────────────────────────────────────────────────
	function haversineKm(lat1, lon1, lat2, lon2) {
		const R = 6371;
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLon = (lon2 - lon1) * Math.PI / 180;
		const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
			+ Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
			* Math.sin(dLon / 2) * Math.sin(dLon / 2);
		return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
	}

	function renderElevationChart(points) {
		const canvas = document.getElementById('ride-elevation-chart');
		if (!canvas || points.length < 2 || typeof Chart === 'undefined') return;

		let cumulativeKm = 0;
		const chartPoints = points.map(function (p, i) {
			if (i > 0) {
				cumulativeKm += haversineKm(points[i - 1][0], points[i - 1][1], p[0], p[1]);
			}
			return { x: Math.round(cumulativeKm * 100) / 100, y: p[2] };
		});

		const styles = getComputedStyle(document.documentElement);
		const bodyColor = styles.getPropertyValue('--bs-body-color').trim() || '#212529';
		const borderColor = styles.getPropertyValue('--bs-border-color').trim() || '#dee2e6';
		const secondaryColor = styles.getPropertyValue('--bs-secondary-color').trim() || '#6c757d';

		new Chart(canvas, {
			type: 'line',
			data: {
				datasets: [{
					data: chartPoints,
					fill: true,
					borderColor: bodyColor,
					backgroundColor: 'rgba(128, 128, 128, 0.25)',
					borderWidth: 1.5,
					pointRadius: 0,
					tension: 0.15,
					spanGaps: true,
				}],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { intersect: false, mode: 'index' },
				scales: {
					x: {
						type: 'linear',
						title: { display: true, text: 'Distance (km)', color: secondaryColor },
						ticks: { color: secondaryColor },
						grid: { color: borderColor },
					},
					y: {
						title: { display: true, text: 'Elevation (m)', color: secondaryColor },
						ticks: { color: secondaryColor },
						grid: { color: borderColor },
					},
				},
				plugins: {
					legend: { display: false },
				},
			},
		});
	}

	// ── Route map ────────────────────────────────────────────────────────────
	const mapEl = document.getElementById('ride-map');
	const trackpointsEl = document.getElementById('ride-trackpoints');

	if (mapEl && trackpointsEl) {
		const points = JSON.parse(trackpointsEl.textContent);

		if (points.length > 0) {
			const map = L.map('ride-map');

			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
				maxZoom: 19,
			}).addTo(map);

			const latLngs = points.map(function (p) { return [p[0], p[1]]; });
			const polyline = L.polyline(latLngs, { color: '#212529', weight: 3 }).addTo(map);

			L.polylineDecorator(polyline, {
				patterns: [
					{
						offset: '4%',
						repeat: '80px',
						symbol: L.Symbol.arrowHead({
							pixelSize: 12,
							headAngle: 40,
							pathOptions: { color: '#212529', weight: 1.5, fillColor: '#ffffff', fillOpacity: 1 },
						}),
					},
				],
			}).addTo(map);

			L.circleMarker(latLngs[0], { radius: 6, color: '#198754', fillOpacity: 1 }).addTo(map);
			L.circleMarker(latLngs[latLngs.length - 1], { radius: 6, color: '#dc3545', fillOpacity: 1 }).addTo(map);

			map.fitBounds(polyline.getBounds(), { padding: [20, 20] });
		}

		renderElevationChart(points);
	}

	// ── Photo gallery ────────────────────────────────────────────────────────
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

		fetch('/api/rides/' + rideId + '/photos/reorder', {
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

		fetch('/api/rides/' + rideId + '/photos/' + pendingPhotoId, {
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

		fetch('/api/rides/' + rideId + '/photos', {
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
});
