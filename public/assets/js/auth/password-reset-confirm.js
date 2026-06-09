// Password reset confirmation form JavaScript
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const passwordInput = document.getElementById('floatingPassword');
    const passwordConfirmInput = document.getElementById('floatingPasswordConfirm');
    const csrfTokenInput = document.getElementById('csrf_token');
    const resetUuidInput = document.getElementById('reset_uuid');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;

    let isSubmitting = false;
    const originalButtonHTML = submitButton ? submitButton.innerHTML : 'Submit';

    function createToastContainer() {
        let container = document.getElementById('prc-toasts');
        if (!container) {
            container = document.createElement('div');
            container.id = 'prc-toasts';
            container.className = 'toast-container position-fixed top-0 start-50 translate-middle-x mt-3';
            container.style.zIndex = '1080';
            document.body.appendChild(container);
        }
        return container;
    }

    function clearToasts() {
        const container = document.getElementById('prc-toasts');
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

    // Replace the form with an animated success panel
    function showSuccessPanel(main) {
        const currentForm = main.querySelector('form');

        if (currentForm) {
            currentForm.style.transition = 'opacity 0.35s ease';
            currentForm.style.opacity = '0';
        }

        setTimeout(() => {
            main.innerHTML = `
                <div id="success-panel" class="text-center" style="max-width: 330px;margin-inline: auto;">
                    <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
                    <h1 id="sp-heading" class="h3 mb-3 fw-normal">&nbsp;</h1>
                    <p id="sp-p1"">Your password has been updated successfully. You can now log in with your new password.</p>
                    <a id="sp-btn" href="/auth" class="btn btn-primary py-2 mt-2">Go to Login</a>
                </div>
            `;

            const ids = ['sp-icon', 'sp-heading', 'sp-p1', 'sp-btn'];
            const els = ids.map(id => document.getElementById(id));
            els.forEach(el => { if (el) el.style.opacity = '0'; });

            void document.getElementById('success-panel').offsetWidth;

            const delays = [100, 250, 520, 680];
            els.forEach((el, i) => {
                if (!el) return;
                setTimeout(() => {
                    el.style.transition = 'opacity 0.5s ease';
                    el.style.opacity = '1';
                }, delays[i]);
            });

            setTimeout(() => {
                const heading = document.getElementById('sp-heading');
                if (heading) heading.textContent = 'Password updated!';
            }, 250);
        }, 380);
    }

    // Password requirements rules — kept in sync with server-side validation in PasswordReset.php
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
        event.preventDefault();

        if (isSubmitting) return;

        clearToasts();

        const password        = passwordInput ? passwordInput.value : '';
        const passwordConfirm = passwordConfirmInput ? passwordConfirmInput.value : '';
        const uuid            = resetUuidInput ? resetUuidInput.value : '';
        const csrfToken       = csrfTokenInput ? csrfTokenInput.value : '';

        if (!password || !passwordConfirm) {
            showToast('Please fill in both password fields.', 'warning');
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

        const data = {
            uuid,
            password,
            password_confirm: passwordConfirm,
        };
        if (csrfTokenInput && csrfTokenInput.name) data[csrfTokenInput.name] = csrfToken;

        fetch('/auth/password-reset/confirm', {
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
                if (main) showSuccessPanel(main);
                return;
            }

            const message = result.error || 'Something went wrong. Please try again.';
            showToast(message, 'danger');
            setSubmittingState(false);
        })
        .catch(error => {
            console.error('Error during password update:', error);
            showToast('An error occurred. Please try again later.', 'danger');
            setSubmittingState(false);
        });
    });
});
