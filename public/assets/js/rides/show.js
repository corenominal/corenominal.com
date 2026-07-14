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
