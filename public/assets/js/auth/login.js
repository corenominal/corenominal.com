// Login form JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('floatingInput');
    const passwordInput = document.getElementById('floatingPassword');
    const csrfTokenInput = document.getElementById('csrf_token');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;

    let isSubmitting = false;
    const originalButtonHTML = submitButton ? submitButton.innerHTML : 'Submit';

    function createToastContainer() {
        let container = document.getElementById('login-toasts');
        if (!container) {
            container = document.createElement('div');
            container.id = 'login-toasts';
            // Position the toasts at the top center of the page
            container.className = 'toast-container position-fixed top-0 start-50 translate-middle-x mt-3';
            container.style.zIndex = '1080';
            document.body.appendChild(container);
        }
        return container;
    }

    function clearToasts() {
        const container = document.getElementById('login-toasts');
        if (container) container.innerHTML = '';
    }

    function showToast(message, type = 'danger') {
        const container = createToastContainer();

        const toastEl = document.createElement('div');
        // Do not add `show` initially; let Bootstrap's Toast control show/hide to avoid flicker.
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
            // Fallback: show immediately and auto-remove after 5s
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

    if (!form) return;

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent the default form submission

        if (isSubmitting) return; // prevent double submit

        clearToasts();

        const email = emailInput ? emailInput.value.trim() : '';
        const password = passwordInput ? passwordInput.value.trim() : '';
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

        // Basic client-side validation
        if (!email || !password) {
            showToast('Please fill in both fields.', 'warning');
            return;
        }

        setSubmittingState(true);

        // Prepare data for submission
        const data = {
            email: email,
            password: password
        };
        if (csrfTokenInput && csrfTokenInput.name) data[csrfTokenInput.name] = csrfToken;

        // Send login request to the server
        fetch('/auth', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(async response => {
            const contentType = response.headers.get('Content-Type') || '';
            let result = {};
            if (contentType.includes('application/json')) {
                result = await response.json().catch(() => ({}));
            }

            if (response.ok && result.success) {
                window.location.href = result.redirect;
                return;
            }

            // Show server-provided message(s) or default
            const message = result.message || 'Login failed. Please try again.';
            showToast(message, 'danger');
            setSubmittingState(false);
        })
        .catch(error => {
            console.error('Error during login:', error);
            showToast('An error occurred. Please try again later.', 'danger');
            setSubmittingState(false);
        });
    });
});