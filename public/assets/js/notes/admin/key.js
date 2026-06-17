const getCookie = (name) => {
	const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\/+^])/g, '\\$1') + '=([^;]*)'));
	return match ? decodeURIComponent(match[1]) : null;
};

const setCookie = (name, value) => {
	document.cookie = name + '=' + encodeURIComponent(value) + '; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/; SameSite=Strict';
};

const deleteCookie = (name) => {
	document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Strict';
};

document.addEventListener('DOMContentLoaded', () => {
	const form     = document.getElementById('notes-key-form');
	const input    = document.getElementById('notes-key-input');
	const clearBtn = document.getElementById('btn-clear-key');
	const statusEl = document.getElementById('notes-key-status');

	const existingKey = getCookie('noteskey');
	if (existingKey) {
		input.value = existingKey;
	}

	form.addEventListener('submit', (e) => {
		e.preventDefault();

		const key = input.value.trim();
		if (!key) {
			statusEl.innerHTML = '<div class="alert alert-danger" role="alert">Please enter an encryption key.</div>';
			input.focus();
			return;
		}

		setCookie('noteskey', key);
		window.location.href = '/admin/notes';
	});

	clearBtn.addEventListener('click', () => {
		deleteCookie('noteskey');
		input.value = '';
		statusEl.innerHTML = '<div class="alert alert-success" role="alert">Key cleared.</div>';
		input.focus();
	});
});
