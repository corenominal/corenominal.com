// Register form JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('floatingEmail');
    const usernameInput = document.getElementById('floatingUsername');
    const realnameInput = document.getElementById('floatingRealname');
    const passwordInput = document.getElementById('floatingPassword');
    const passwordConfirmInput = document.getElementById('floatingPasswordConfirm');
    const csrfTokenInput = document.getElementById('csrf_token');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;

    let isSubmitting = false;
    const originalButtonHTML = submitButton ? submitButton.innerHTML : 'Submit';

    function createToastContainer() {
        let container = document.getElementById('register-toasts');
        if (!container) {
            container = document.createElement('div');
            container.id = 'register-toasts';
            // Position the toasts at the top center of the page
            container.className = 'toast-container position-fixed top-0 start-50 translate-middle-x mt-3';
            container.style.zIndex = '1080';
            document.body.appendChild(container);
        }
        return container;
    }

    function clearToasts() {
        const container = document.getElementById('register-toasts');
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

    // Password requirements rules — kept in sync with server-side validation in Register.php
    const PASSWORD_RULES = [
        { req: 'length',  test: p => p.length >= 12 },
        { req: 'upper',   test: p => /[A-Z]/.test(p) },
        { req: 'lower',   test: p => /[a-z]/.test(p) },
        { req: 'number',  test: p => /[0-9]/.test(p) },
        { req: 'special', test: p => /[^A-Za-z0-9]/.test(p) },
    ];

    function checkPasswordRequirements(password) {
        const reqPanel = document.getElementById('password-requirements');
        if (reqPanel) reqPanel.classList.toggle('visible', password.length > 0);
        let allMet = true;
        PASSWORD_RULES.forEach(({ req, test }) => {
            const el = document.querySelector(`[data-req="${req}"]`);
            if (!el) return;
            const met = test(password);
            el.classList.toggle('met', met);
            if (!met) allMet = false;
        });
        return allMet;
    }

    if (!form) return;

    // Live password requirements check as user types
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            checkPasswordRequirements(this.value);
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent the default form submission

        if (isSubmitting) return; // prevent double submit

        clearToasts();

        const email           = emailInput ? emailInput.value.trim() : '';
        const username        = usernameInput ? usernameInput.value.trim() : '';
        const realname        = realnameInput ? realnameInput.value.trim() : '';
        const password        = passwordInput ? passwordInput.value : '';
        const passwordConfirm = passwordConfirmInput ? passwordConfirmInput.value : '';
        const csrfToken       = csrfTokenInput ? csrfTokenInput.value : '';

        // Basic client-side validation
        if (!email || !username || !password || !passwordConfirm) {
            showToast('Please fill in all required fields.', 'warning');
            return;
        }

        if (password !== passwordConfirm) {
            showToast('Passwords do not match.', 'warning');
            return;
        }

        if (!checkPasswordRequirements(password)) {
            showToast('Password does not meet the requirements listed below the password field.', 'warning');
            return;
        }

        setSubmittingState(true);

        // Prepare data for submission
        const data = {
            email:            email,
            username:         username,
            realname:         realname,
            password:         password,
            password_confirm: passwordConfirm,
        };
        if (csrfTokenInput && csrfTokenInput.name) data[csrfTokenInput.name] = csrfToken;

        // Send registration request to the server
        fetch('/auth/register', {
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

            if (response.ok && result.validated) {
                // First user (admin) — validated immediately, show toast then redirect to login
                showToast('Administrator account created successfully. Redirecting to login&hellip;', 'success');
                setTimeout(() => { window.location.href = result.redirect; }, 3000);
                return;
            }

            if (response.ok && result.validated === false) {
                window.location.href = '/auth/register/verify';
                return;
            }

            // Show server-provided error or default
            const message = result.error || 'Registration failed. Please try again.';
            showToast(message, 'danger');
            setSubmittingState(false);
        })
        .catch(error => {
            console.error('Error during registration:', error);
            showToast('An error occurred. Please try again later.', 'danger');
            setSubmittingState(false);
        });
    });
});
