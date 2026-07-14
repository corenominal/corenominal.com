document.addEventListener('DOMContentLoaded', () => {
	const ridesItems     = document.querySelector('#rides-items');
	const observerTarget = document.querySelector('#rides-observer');
	const loader         = document.querySelector('#rides-loader');
	const searchInput    = document.querySelector('#rides-search');

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

	if (ridesItems && observerTarget && loader) {
		const state = {
			isLoading: false,
			offset:    Number(ridesItems.dataset.offset || 0),
			limit:     Number(ridesItems.dataset.limit || 12),
			hasMore:   ridesItems.dataset.hasMore === '1',
			loadUrl:   ridesItems.dataset.loadUrl || '/rides/load',
			query:     ridesItems.dataset.search || '',
		};

		const showLoader = (visible) => {
			loader.style.display = visible ? '' : 'none';
		};

		const loadMoreRides = async () => {
			if (state.isLoading || !state.hasMore) {
				return;
			}

			state.isLoading = true;
			showLoader(true);

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
					throw new Error(`Rides request failed (${response.status})`);
				}

				const payload = await response.json();

				if (typeof payload.html === 'string' && payload.html.trim() !== '') {
					ridesItems.insertAdjacentHTML('beforeend', payload.html);
				}

				state.offset  = Number(payload.nextOffset || state.offset);
				state.hasMore = Boolean(payload.hasMore);

				if (!state.hasMore) {
					showLoader(false);
				}
			} catch (error) {
				// eslint-disable-next-line no-console
				console.error(error);
			} finally {
				state.isLoading = false;
				if (!state.hasMore) {
					showLoader(false);
				}
			}
		};

		if (state.hasMore) {
			const observer = new IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						loadMoreRides();
					}
				});
			}, { rootMargin: '500px 0px', threshold: 0 });

			observer.observe(observerTarget);
		}
	}
});
