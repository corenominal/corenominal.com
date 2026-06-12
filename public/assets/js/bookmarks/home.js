document.addEventListener('DOMContentLoaded', () => {
	const bookmarksItems = document.querySelector('#bookmarks-items');
	const observerTarget = document.querySelector('#bookmarks-observer');
	const loader         = document.querySelector('#bookmarks-loader');
	const searchInput    = document.querySelector('#bookmarks-search');

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

	if (bookmarksItems && observerTarget && loader) {
		const state = {
			isLoading: false,
			offset:    Number(bookmarksItems.dataset.offset || 0),
			limit:     Number(bookmarksItems.dataset.limit || 20),
			hasMore:   bookmarksItems.dataset.hasMore === '1',
			loadUrl:   bookmarksItems.dataset.loadUrl || '/bookmarks/load',
			query:     bookmarksItems.dataset.search || '',
		};

		const showLoader = (visible) => {
			loader.style.display = visible ? '' : 'none';
		};

		const loadMoreBookmarks = async () => {
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
					throw new Error(`Bookmarks request failed (${response.status})`);
				}

				const payload = await response.json();

				if (typeof payload.html === 'string' && payload.html.trim() !== '') {
					bookmarksItems.insertAdjacentHTML('beforeend', payload.html);
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
						loadMoreBookmarks();
					}
				});
			}, { rootMargin: '500px 0px', threshold: 0 });

			observer.observe(observerTarget);
		}
	}
});
