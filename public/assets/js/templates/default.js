// ── Mobile offcanvas sidebar (generated from desktop sidebar) ──
(function () {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    // Collect nav items from desktop sidebar
    const navItems = [];
    sidebar.querySelectorAll('nav .nav > li').forEach(li => {
        const a = li.querySelector('a.nav-link');
        if (!a) return;
        const icon = a.querySelector('i');
        const label = a.querySelector('.sidebar-label');
        navItems.push({
            href: a.getAttribute('href'),
            iconClass: icon ? icon.className : '',
            label: label ? label.textContent.trim() : '',
            active: a.classList.contains('active'),
        });
    });

    const navHtml = navItems.map(({ href, iconClass, label, active }) =>
        `<li><a href="${href}" class="nav-link${active ? ' active' : ''} d-flex align-items-center gap-2"><i class="${iconClass}"></i>${label}</a></li>`
    ).join('');

    // Clone and adapt footer from desktop sidebar
    const desktopFooter = sidebar.querySelector('.p-2');
    const mobileFooter = desktopFooter ? desktopFooter.cloneNode(true) : document.createElement('div');

    mobileFooter.querySelectorAll('.sidebar-footer-link').forEach(el => el.classList.add('px-2', 'py-2'));
    mobileFooter.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        el.removeAttribute('data-bs-toggle');
        el.removeAttribute('data-bs-placement');
        el.removeAttribute('data-bs-title');
    });
    mobileFooter.querySelectorAll('.sidebar-label').forEach(el => { el.className = ''; });
    mobileFooter.querySelectorAll('.flex-shrink-0').forEach(el => el.classList.remove('flex-shrink-0'));

    // Build and insert offcanvas element
    const offcanvas = document.createElement('div');
    offcanvas.className = 'offcanvas offcanvas-start border-0';
    offcanvas.setAttribute('tabindex', '-1');
    offcanvas.id = 'mobileSidebar';
    offcanvas.style.width = '260px';
    offcanvas.innerHTML = `
        <div class="offcanvas-header px-3 py-2">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-body-emphasis">
                <img src="/icon.svg" alt="Logo" width="40" height="40" class="rounded-circle">
                <span class="fw-semibold">corenominal</span>
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <nav class="flex-grow-1 overflow-y-auto py-2 px-2 sidebar-nav">
                <ul class="nav flex-column gap-1">${navHtml}</ul>
            </nav>
        </div>`;
    offcanvas.querySelector('.offcanvas-body').appendChild(mobileFooter);

    sidebar.parentNode.insertBefore(offcanvas, sidebar);
})();

// ── Sidebar toggle (desktop) ───────────────────────────
const sidebar   = document.getElementById("sidebar");
const toggleBtn = document.getElementById("sidebarToggle");

let tooltipInstances = [];

function initTooltips() {
    tooltipInstances.forEach(t => t.dispose());
    tooltipInstances = [];
    if (sidebar.classList.contains("collapsed")) {
    document.querySelectorAll("#sidebar [data-bs-toggle='tooltip']").forEach(el => {
        tooltipInstances.push(new bootstrap.Tooltip(el, { trigger: "hover" }));
    });
    }
}

toggleBtn.addEventListener("click", (e) => {
    e.preventDefault();
    sidebar.classList.toggle("collapsed");
    initTooltips();
});

initTooltips();
