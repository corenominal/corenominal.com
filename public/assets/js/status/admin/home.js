document.addEventListener('DOMContentLoaded', () => {
	const currentPath = window.location.pathname.replace(/\/$/, '');

	document.querySelectorAll('#sidebar nav a').forEach((link) => {
		const href = link.getAttribute('href')?.replace(/\/$/, '') || '';

		if (href === currentPath) {
			link.classList.add('active');
		}
	});
});
