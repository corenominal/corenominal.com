document.addEventListener('DOMContentLoaded', function () {

	function haversineKm(lat1, lon1, lat2, lon2) {
		const R = 6371;
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLon = (lon2 - lon1) * Math.PI / 180;
		const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
			+ Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
			* Math.sin(dLon / 2) * Math.sin(dLon / 2);
		return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
	}

	function buildDistances(points) {
		let cumulativeKm = 0;
		return points.map(function (p, i) {
			if (i > 0) {
				cumulativeKm += haversineKm(points[i - 1][0], points[i - 1][1], p[0], p[1]);
			}
			return Math.round(cumulativeKm * 100) / 100;
		});
	}

	function chartThemeColors() {
		const styles = getComputedStyle(document.documentElement);
		return {
			bodyColor: styles.getPropertyValue('--bs-body-color').trim() || '#212529',
			borderColor: styles.getPropertyValue('--bs-border-color').trim() || '#dee2e6',
			secondaryColor: styles.getPropertyValue('--bs-secondary-color').trim() || '#6c757d',
		};
	}

	function renderElevationChart(points, distances) {
		const canvas = document.getElementById('ride-elevation-chart');
		if (!canvas || points.length < 2 || typeof Chart === 'undefined') return;

		const chartPoints = points.map(function (p, i) {
			return { x: distances[i], y: p[2] };
		});

		const { bodyColor, borderColor, secondaryColor } = chartThemeColors();

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

	function renderHeartRateChart(points, distances) {
		const container = document.getElementById('ride-heart-rate-chart-container');
		if (!container || points.length < 2 || typeof Chart === 'undefined') return;

		const hasHeartRate = points.some(function (p) { return p[4] !== null && p[4] !== undefined; });
		if (!hasHeartRate) {
			container.innerHTML = '<p class="text-secondary small mb-0">No heart rate data available for this ride.</p>';
			return;
		}

		const canvas = document.createElement('canvas');
		container.innerHTML = '';
		container.appendChild(canvas);

		const chartPoints = points.map(function (p, i) {
			return { x: distances[i], y: p[4] !== undefined ? p[4] : null };
		});

		const { borderColor, secondaryColor } = chartThemeColors();

		new Chart(canvas, {
			type: 'line',
			data: {
				datasets: [{
					data: chartPoints,
					fill: false,
					borderColor: '#dc3545',
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
						title: { display: true, text: 'Heart Rate (bpm)', color: secondaryColor },
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

		const distances = buildDistances(points);
		renderElevationChart(points, distances);
		renderHeartRateChart(points, distances);
	}

	const photoModalEl = document.getElementById('photo-modal');

	if (photoModalEl) {
		const photoModal = new bootstrap.Modal(photoModalEl);
		const photoModalImg = document.getElementById('photo-modal-img');

		document.querySelectorAll('.btn-photo-open').forEach(function (btn) {
			btn.addEventListener('click', function () {
				photoModalImg.src = btn.dataset.fullSrc;
				photoModal.show();
			});
		});

		photoModalEl.addEventListener('hidden.bs.modal', function () {
			photoModalImg.src = '';
		});
	}
});
