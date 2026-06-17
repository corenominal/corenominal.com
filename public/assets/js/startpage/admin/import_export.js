document.addEventListener('DOMContentLoaded', function () {

  // ── Tab state via URL hash ─────────────────────────────────────────────────
  const hash = window.location.hash;
  if (hash) {
    const tabTrigger = document.querySelector(
      `#import-export-tabs button[data-bs-target="${CSS.escape(hash.slice(1))}"], #import-export-tabs button[data-bs-target="${hash}"]`,
    );
    if (tabTrigger) {
      bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
    }
  }

  document.querySelectorAll('#import-export-tabs button[data-bs-toggle="tab"]').forEach((btn) => {
    btn.addEventListener('shown.bs.tab', function (e) {
      const target = e.target.getAttribute('data-bs-target');
      history.replaceState(null, '', target);
    });
  });

  // ── Import form submissions ────────────────────────────────────────────────
  document.querySelectorAll('.import-form').forEach((form) => {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const endpoint  = form.getAttribute('data-endpoint');
      const resultEl  = form.nextElementSibling;
      const submitBtn = form.querySelector('button[type="submit"]');
      const fileInput = form.querySelector('input[type="file"]');

      if (!fileInput.files.length) {
        return;
      }

      const formData = new FormData();
      formData.append('import_file', fileInput.files[0]);

      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing…';
      resultEl.className = 'import-result mt-3 d-none';

      fetch(endpoint, {
        method: 'POST',
        body: formData,
      })
        .then((res) => res.json().then((data) => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
          resultEl.classList.remove('d-none');
          if (ok && data.status === 'success') {
            resultEl.innerHTML =
              `<div class="alert alert-success mb-0">` +
              `<i class="bi bi-check-circle-fill me-1"></i> ` +
              `Import complete: <strong>${data.inserted}</strong> inserted, ` +
              `<strong>${data.updated}</strong> updated.</div>`;
          } else {
            const msg = (data && data.message) ? data.message : 'An unexpected error occurred.';
            resultEl.innerHTML =
              `<div class="alert alert-danger mb-0">` +
              `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${msg}</div>`;
          }
        })
        .catch(() => {
          resultEl.classList.remove('d-none');
          resultEl.innerHTML =
            '<div class="alert alert-danger mb-0">' +
            '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' +
            'Network error. Please try again.</div>';
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="bi bi-upload me-1"></i> Import';
          fileInput.value = '';
        });
    });
  });
});
