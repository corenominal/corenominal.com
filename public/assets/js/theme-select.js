// ── Theme toggle ───────────────────────────────────────
// Wrapped in an IIFE to avoid polluting the global scope.
(function () {
    const root = document.documentElement;
    // Watch the OS-level light/dark preference so we can honour it when theme is "auto".
    const mq = window.matchMedia("(prefers-color-scheme: dark)");

    // Sets data-bs-theme on <html>, which Bootstrap uses to switch its colour palette.
    // "auto" resolves to "dark" or "light" based on the OS preference; any other value is used as-is.
    function applyTheme(v) {
    root.setAttribute("data-bs-theme", v === "auto" ? (mq.matches ? "dark" : "light") : v);
    }

    // Apply the saved preference on page load, defaulting to "auto" (OS preference) if nothing is stored.
    applyTheme(localStorage.getItem("bs-theme") || "auto");

    // Delegate click handling to the document so dynamically rendered buttons are covered.
    // Any element with a data-theme attribute acts as a theme switcher button.
    document.addEventListener("click", e => {
    const btn = e.target.closest("[data-theme]");
    if (!btn) return;
    const v = btn.getAttribute("data-theme");
    localStorage.setItem("bs-theme", v);  // Persist the choice across page loads.
    applyTheme(v);
    });

    // When the OS preference changes, re-evaluate unless the user has made an explicit
    // "light" or "dark" choice — null (nothing stored) is treated the same as "auto".
    mq.addEventListener("change", () => {
    const stored = localStorage.getItem("bs-theme");
    if (!stored || stored === "auto") applyTheme("auto");
    });
})();