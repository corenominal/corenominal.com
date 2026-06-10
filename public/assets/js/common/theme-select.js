// ── Theme toggle ───────────────────────────────────────
(function () {
    const root = document.documentElement;
    const mq = window.matchMedia("(prefers-color-scheme: dark)");

    function applyTheme(v) {
    root.setAttribute("data-bs-theme", v === "auto" ? (mq.matches ? "dark" : "light") : v);
    }

    applyTheme(localStorage.getItem("bs-theme") || "dark");

    document.addEventListener("click", e => {
    const btn = e.target.closest("[data-theme]");
    if (!btn) return;
    const v = btn.getAttribute("data-theme");
    localStorage.setItem("bs-theme", v);
    applyTheme(v);
    });

    mq.addEventListener("change", () => {
    if (localStorage.getItem("bs-theme") === "auto") applyTheme("auto");
    });
})();