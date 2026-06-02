(function () {
    var toggle = document.getElementById("adminSidebarToggle");
    var sidebar = document.getElementById("adminSidebar");
    var overlay = document.getElementById("adminSidebarOverlay");

    if (!toggle || !sidebar) return;

    function closeSidebar() {
        sidebar.classList.remove("open");
        if (overlay) overlay.classList.remove("show");
    }

    toggle.addEventListener("click", function () {
        sidebar.classList.toggle("open");
        if (overlay) overlay.classList.toggle("show");
    });

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }
})();
