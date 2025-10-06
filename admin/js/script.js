document.addEventListener("DOMContentLoaded", function () {
    const drawer = document.getElementById("member-drawer");
    const toggleButton = document.getElementById("toggle-drawer");
    const icon = document.getElementById("drawer-icon");
    const toggleContent = document.querySelector(".toggle-content");

    let isClosed = localStorage.getItem("drawerState") === "closed";

    function updateLayout() {
        if (isClosed) {
            drawer.setAttribute("data-closed", "true");
            toggleButton.style.right = "0px";
            icon.classList.replace("fa-chevron-right", "fa-chevron-left");
            toggleContent.style.marginInlineEnd = "20px";
        } else {
            drawer.removeAttribute("data-closed");
            toggleButton.style.right = "315px";
            toggleContent.style.marginInlineEnd = "250px";
            icon.classList.replace("fa-chevron-left", "fa-chevron-right");
        }
    }

    updateLayout();

    toggleButton.addEventListener("click", function () {
        isClosed = !isClosed;
        localStorage.setItem("drawerState", isClosed ? "closed" : "open");
        updateLayout();
    });
});