document.addEventListener("DOMContentLoaded", function() {
    const sidebarLinks = document.querySelectorAll(".sidebar-footer-link");
    sidebarLinks.forEach(link => {
        if (link.getAttribute("href") === "/debug") {
            link.classList.add("active");
        }
    });
});
