(function () {
    const MODAL_ID = 'shared-logout-modal';

    function createModal() {
        let existing = document.getElementById(MODAL_ID);
        if (existing) return existing;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="modal fade" id="${MODAL_ID}" tabindex="-1" aria-labelledby="${MODAL_ID}-title">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${MODAL_ID}-title">Confirm Logout</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to logout?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="${MODAL_ID}-confirm">Logout</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const modalElem = wrapper.firstElementChild;
        document.body.appendChild(modalElem);

        // Blur any focused element inside the modal before Bootstrap sets aria-hidden
        modalElem.addEventListener('hide.bs.modal', function () {
            if (modalElem.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });

        return modalElem;
    }

    function showLogoutModal(triggerEl) {
        const url = '/auth/logout';
        const modalElem = createModal();
        const confirmBtn = modalElem.querySelector(`#${MODAL_ID}-confirm`);

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalElem, {
                backdrop: 'static',
                keyboard: false,
            });

            // Move focus away from trigger before Bootstrap shows the modal
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
            }

            // Ensure single handler (replace any previous)
            confirmBtn.replaceWith(confirmBtn.cloneNode(true));
            const freshConfirm = modalElem.querySelector(`#${MODAL_ID}-confirm`);
            freshConfirm.addEventListener('click', function () {
                window.location.href = url;
            });

            // Move focus into the dialog once visible
            modalElem.addEventListener('shown.bs.modal', function onShown() {
                modalElem.removeEventListener('shown.bs.modal', onShown);
                const toFocus = modalElem.querySelector(`#${MODAL_ID}-confirm`);
                if (toFocus) toFocus.focus();
            });

            // Restore focus to the original trigger after the modal closes
            modalElem.addEventListener('hidden.bs.modal', function onHidden() {
                modalElem.removeEventListener('hidden.bs.modal', onHidden);
                if (triggerEl && typeof triggerEl.focus === 'function') triggerEl.focus();
            });

            bsModal.show();
        } else {
            // Fallback if Bootstrap JS not present
            if (window.confirm('Are you sure you want to logout?')) {
                window.location.href = url;
            }
        }
    }

    function onDocumentClick(e) {
        const trigger = e.target.closest && e.target.closest('.trigger-logout');
        if (!trigger) return;
        e.preventDefault();
        showLogoutModal(trigger);
    }

    document.addEventListener('click', onDocumentClick, false);

    // Support keyboard activation for focused elements
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            const active = document.activeElement;
            if (active && active.classList && active.classList.contains('trigger-logout')) {
                e.preventDefault();
                showLogoutModal(active);
            }
        }
    });

    // Expose for testing/override if needed
    window.__sharedLogout = {
        show: showLogoutModal,
        _createModal: createModal,
    };
})();

