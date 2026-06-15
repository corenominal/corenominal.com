function applyPostBodyFormatting(container) {
    const imageModalEl      = document.querySelector('#post-image-modal');
    const imageModalImg     = document.querySelector('#post-image-modal-img');
    const imageModalCaption = document.querySelector('#post-image-modal-caption');
    const imageModal        = imageModalEl && window.bootstrap
        ? window.bootstrap.Modal.getOrCreateInstance(imageModalEl)
        : null;

    container.querySelectorAll('img').forEach((img) => {
        img.classList.add('img-fluid', 'rounded', 'mb-2');

        if (imageModal) {
            img.style.cursor = 'pointer';
            img.addEventListener('click', () => {
                imageModalImg.src             = img.currentSrc || img.src;
                imageModalImg.alt             = img.alt || '';
                imageModalCaption.textContent = img.alt || '';
                imageModal.show();
            });
        }
    });

    container.querySelectorAll('table').forEach((table) => {
        table.classList.add('table', 'table-bordered');
    });

    container.querySelectorAll('pre > code').forEach((codeEl) => {
        const pre = codeEl.parentElement;

        const wrapper = document.createElement('div');
        wrapper.className = 'code-block';
        pre.parentNode.insertBefore(wrapper, pre);
        wrapper.appendChild(pre);

        const btn = document.createElement('button');
        btn.className = 'code-block__copy-btn';
        btn.setAttribute('aria-label', 'Copy code');
        btn.innerHTML = '<i class="bi bi-clipboard" aria-hidden="true"></i>';

        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(codeEl.textContent).then(() => {
                btn.innerHTML = '<i class="bi bi-clipboard-check" aria-hidden="true"></i>';
                btn.setAttribute('aria-label', 'Copied!');
                btn.classList.add('code-block__copy-btn--copied');
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-clipboard" aria-hidden="true"></i>';
                    btn.setAttribute('aria-label', 'Copy code');
                    btn.classList.remove('code-block__copy-btn--copied');
                }, 2000);
            });
        });

        wrapper.appendChild(btn);
    });
}

window.applyPostBodyFormatting = applyPostBodyFormatting;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.post__body').forEach(applyPostBodyFormatting);

    const imageModalEl      = document.querySelector('#post-image-modal');
    const imageModalImg     = document.querySelector('#post-image-modal-img');
    const imageModalCaption = document.querySelector('#post-image-modal-caption');

    if (imageModalEl) {
        imageModalEl.addEventListener('hidden.bs.modal', () => {
            imageModalImg.src             = '';
            imageModalImg.alt             = '';
            imageModalCaption.textContent = '';
        });
    }
});
