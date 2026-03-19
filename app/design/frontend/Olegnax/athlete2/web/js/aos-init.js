require(['jquery', 'aos'], function($, AOS) {
    $(document).ready(function() {
        if (AOS) {
            AOS.init();
        }

        var highlightButtons = document.querySelectorAll(".highlight-link");
        var highlightInfos = document.querySelectorAll(".highlight-info");

        function closeAllTooltips() {
            highlightInfos.forEach(function(info) {
                info.classList.remove("highlight-info-active");
                info.setAttribute("aria-expanded", "false");
            });
            highlightButtons.forEach(function(button) {
                button.setAttribute("aria-expanded", "false");
            });
        }

        highlightButtons.forEach(function(button) {
            button.addEventListener("click", function() {
                var id = this.getAttribute("data-id");
                var targetInfo = document.querySelector("#highlight-info-" + id);
                if (!targetInfo) return;
                var isActive = targetInfo.classList.contains("highlight-info-active");
                closeAllTooltips();
                if (!isActive) {
                    targetInfo.classList.add("highlight-info-active");
                    targetInfo.setAttribute("aria-expanded", "true");
                    this.setAttribute("aria-expanded", "true");
                }
            });
        });

        highlightInfos.forEach(function(info) {
            var closeButton = info.querySelector(".highlight-close");
            if (closeButton) {
                closeButton.addEventListener("click", function() {
                    var id = this.getAttribute("data-id");
                    var targetInfo = document.querySelector("#highlight-info-" + id);
                    if (targetInfo) {
                        targetInfo.classList.remove("highlight-info-active");
                        targetInfo.setAttribute("aria-expanded", "false");
                    }
                    var linkButton = document.querySelector("#highlight-link-" + id);
                    if (linkButton) {
                        linkButton.setAttribute("aria-expanded", "false");
                    }
                });
            }
        });
    });
});
