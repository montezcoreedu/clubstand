<div id="loading-screen">
    <div class="dots">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
    </div>
</div>
<script>
    const loadingScreen = document.getElementById("loading-screen");

    function showLoading() {
        loadingScreen.style.display = "flex";
        requestAnimationFrame(() => {
            loadingScreen.style.opacity = "1";
        });
    }

    function hideLoading() {
        loadingScreen.style.opacity = "0";
        setTimeout(() => {
            loadingScreen.style.display = "none";
        }, 400);
    }

    let loadingTimeout = setTimeout(() => {
        showLoading();
    }, 300);

    window.addEventListener("load", function () {
        clearTimeout(loadingTimeout);
        hideLoading();
    });

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("form").forEach(form => {
            form.addEventListener("submit", function (e) {
                if (form.target === "_blank") return;

                showLoading();

                if (form.classList.contains("csv-export-form")) {
                    setTimeout(hideLoading, 800);
                }
            });
        });

        document.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", function (e) {
                if (
                    link.getAttribute("target") === "_blank" ||
                    link.href.startsWith("javascript:") ||
                    link.href.includes("#")
                ) return;
            });
        });
    });
</script>
