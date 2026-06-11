document.addEventListener('DOMContentLoaded', () => {
	const copyBtn   = document.getElementById('copy-prompt-btn');
	const promptEl  = document.getElementById('ai-prompt-text');

	if (!copyBtn || !promptEl) return;

	copyBtn.addEventListener('click', async () => {
		try {
			await navigator.clipboard.writeText(promptEl.textContent.trim());

			const original = copyBtn.innerHTML;
			copyBtn.innerHTML = '<i class="bi bi-clipboard-check me-1"></i>Copied!';
			copyBtn.disabled  = true;

			setTimeout(() => {
				copyBtn.innerHTML = original;
				copyBtn.disabled  = false;
			}, 2000);
		} catch {
			const range = document.createRange();
			range.selectNodeContents(promptEl);
			const selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(range);
		}
	});
});
