// Password reset request form JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('floatingEmail');
    const csrfTokenInput = document.getElementById('csrf_token');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;

    let isSubmitting = false;
    const originalButtonHTML = submitButton ? submitButton.innerHTML : 'Submit';

    function createToastContainer() {
        let container = document.getElementById('pr-toasts');
        if (!container) {
            container = document.createElement('div');
            container.id = 'pr-toasts';
            container.className = 'toast-container position-fixed top-0 start-50 translate-middle-x mt-3';
            container.style.zIndex = '1080';
            document.body.appendChild(container);
        }
        return container;
    }

    function clearToasts() {
        const container = document.getElementById('pr-toasts');
        if (container) container.innerHTML = '';
    }

    function showToast(message, type = 'danger') {
        const container = createToastContainer();

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center fade border border-1 border-${type}`;
        toastEl.role = 'alert';
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        const inner = document.createElement('div');
        inner.className = 'd-flex';

        const body = document.createElement('div');
        body.className = 'toast-body';
        body.innerHTML = message;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-close me-2 m-auto';
        btn.setAttribute('data-bs-dismiss', 'toast');
        btn.setAttribute('aria-label', 'Close');

        inner.appendChild(body);
        inner.appendChild(btn);
        toastEl.appendChild(inner);
        container.appendChild(toastEl);

        if (window.bootstrap && typeof window.bootstrap.Toast === 'function') {
            const toast = new window.bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
            toast.show();
        } else {
            toastEl.classList.add('show');
            setTimeout(() => {
                try { toastEl.remove(); } catch (e) {}
            }, 5000);
        }
    }

    function setSubmittingState(submitting) {
        isSubmitting = submitting;
        if (!submitButton) return;
        if (submitting) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-disabled', 'true');
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing ..';
        } else {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-disabled');
            submitButton.innerHTML = originalButtonHTML;
        }
    }

    // Fade out the form and show a "check your email" confirmation panel
    function showCheckEmailPanel(main) {
        const currentForm = main.querySelector('form');

        if (currentForm) {
            currentForm.style.transition = 'opacity 0.35s ease';
            currentForm.style.opacity = '0';
        }

        setTimeout(() => {
            main.innerHTML = `
                <div id="check-email-panel" class="text-center" style="max-width: 330px;margin-inline: auto;">
                    <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
                    <h1 id="ce-heading" class="h3 mb-3 fw-normal">&nbsp;</h1>
                    <p id="ce-p1" class="text-body-secondary">If your email address is registered, you&rsquo;ll receive a password reset link shortly.</p>
                    <p id="ce-p2" class="text-body-secondary">The link will expire in <strong>1 hour</strong>. Don&rsquo;t forget to check your <strong>spam or junk folder</strong> if you don&rsquo;t see it.</p>
                    <a id="ce-btn" href="/auth" class="btn btn-primary py-2 mt-2">Back to Login</a>
                </div>
            `;

            const ids = ['ce-icon', 'ce-heading', 'ce-p1', 'ce-p2', 'ce-btn'];
            const els = ids.map(id => document.getElementById(id));
            els.forEach(el => { if (el) el.style.opacity = '0'; });

            void document.getElementById('check-email-panel').offsetWidth;

            const delays = [100, 250, 520, 680, 840];
            els.forEach((el, i) => {
                if (!el) return;
                setTimeout(() => {
                    el.style.transition = 'opacity 0.5s ease';
                    el.style.opacity = '1';
                }, delays[i]);
            });

            setTimeout(() => {
                const heading = document.getElementById('ce-heading');
                if (heading) heading.textContent = 'Check your email!';
            }, 250);
        }, 380);
    }

    if (!form) return;

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (isSubmitting) return;

        clearToasts();

        const email = emailInput ? emailInput.value.trim() : '';
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

        if (!email) {
            showToast('Please enter your email address.', 'warning');
            return;
        }

        setSubmittingState(true);

        const data = { email };
        if (csrfTokenInput && csrfTokenInput.name) data[csrfTokenInput.name] = csrfToken;

        fetch('/auth/password-reset', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(async response => {
            const contentType = response.headers.get('Content-Type') || '';
            let result = {};
            if (contentType.includes('application/json')) {
                result = await response.json().catch(() => ({}));
            }

            if (response.ok && result.success) {
                const main = document.querySelector('main');
                if (main) showCheckEmailPanel(main);
                return;
            }

            const message = result.error || 'Something went wrong. Please try again.';
            showToast(message, 'danger');
            setSubmittingState(false);
        })
        .catch(error => {
            console.error('Error during password reset request:', error);
            showToast('An error occurred. Please try again later.', 'danger');
            setSubmittingState(false);
        });
    });
});
