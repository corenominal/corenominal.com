document.addEventListener("DOMContentLoaded", function() {

    const textarea       = document.getElementById("prompt-content");
    const charCount      = document.getElementById("char-count");
    const dirtyIndicator = document.getElementById("dirty-indicator");
    const form           = document.getElementById("prompt-form");
    const originalValue  = textarea.value;

    function updateCharCount() {
        const len = textarea.value.length;
        charCount.textContent = len.toLocaleString() + " character" + (len !== 1 ? "s" : "");
    }

    function syncDirty() {
        const dirty = textarea.value !== originalValue;
        dirtyIndicator.classList.toggle("d-none", !dirty);
    }

    textarea.addEventListener("input", function() {
        updateCharCount();
        syncDirty();
    });

    updateCharCount();

    // Warn before navigating away with unsaved changes
    window.addEventListener("beforeunload", function(e) {
        if (textarea.value !== originalValue) {
            e.preventDefault();
            e.returnValue = "";
        }
    });

    // Clear dirty flag on save so the beforeunload guard doesn't fire
    form.addEventListener("submit", function() {
        textarea.value = textarea.value; // flush
        Object.defineProperty(textarea, "value", { get: () => originalValue });
    });

    // ── Revert modal ─────────────────────────────────────────────────────────
    const revertModalEl  = document.getElementById("modal-revert");
    const revertModal    = new bootstrap.Modal(revertModalEl, { focus: false });
    const revertForm     = document.getElementById("revert-form");
    const revertRevLabel = document.getElementById("revert-rev-label");

    revertModalEl.addEventListener("shown.bs.modal", function() {
        const closeBtn = revertModalEl.querySelector(".btn-close");
        if (closeBtn) closeBtn.focus();
    });

    revertModalEl.addEventListener("hide.bs.modal", function() {
        const focused = revertModalEl.querySelector(":focus");
        if (focused) focused.blur();
    });

    document.querySelectorAll(".btn-revert").forEach(function(btn) {
        btn.addEventListener("click", function() {
            const id  = this.dataset.id;
            const rev = this.dataset.rev;
            revertRevLabel.textContent = "revision " + rev;
            revertForm.action = "/admin/ai/prompt/revert/" + id;
            revertModal.show();
        });
    });

});
