// app/design/frontend/<Vendor>/<theme>/web/js/aos-init.js
require(['jquery', 'aos', 'customScript', 'swiper', 'commonAsync', 'common'], function($, AOS) {
    $(document).ready(function() {
        // Inicializar AOS
        AOS.init();

        // Inicializar Swiper
        var mySwiper = new Swiper('.swiper-container', {
            // Opciones del swiper (ajustar según sea necesario)
        });

        // Inicializar Swiper Gallery
        var myGallerySwiper = new Swiper('.swiper-gallery', {
            direction: 'horizontal',
            slidesPerView: 3,
            breakpoints: {
                320: { slidesPerView: 1, spaceBetween: 20 },
                480: { slidesPerView: 2, spaceBetween: 30 },
                640: { slidesPerView: 3, spaceBetween: 40 }
            },
            spaceBetween: 30,
            pagination: {
                el: '.swiper-pagination-gallery',
                clickable: true
            },
            navigation: {
                nextEl: '.swiper-button-next-gallery',
                prevEl: '.swiper-button-prev-gallery'
            }
        });

        // Obtener todos los botones y los elementos de información
        const highlightButtons = document.querySelectorAll(".highlight-link");
        const highlightInfos = document.querySelectorAll(".highlight-info");

        // Función para cerrar todos los tooltips
        function closeAllTooltips() {
            highlightInfos.forEach(info => {
                info.classList.remove("highlight-info-active");
                info.setAttribute("aria-expanded", "false");
            });
            highlightButtons.forEach(button => {
                button.setAttribute("aria-expanded", "false");
            });
        }

        // Agregar eventos de clic a los botones
        highlightButtons.forEach(button => {
            button.addEventListener("click", function() {
                const id = this.getAttribute("data-id");
                const targetInfo = document.querySelector(`#highlight-info-${id}`);
                const isActive = targetInfo.classList.contains("highlight-info-active");

                // Cerrar todos los tooltips
                closeAllTooltips();

                // Si el tooltip no estaba activo, abrirlo
                if (!isActive) {
                    targetInfo.classList.add("highlight-info-active");
                    targetInfo.setAttribute("aria-expanded", "true");
                    this.setAttribute("aria-expanded", "true");
                }
            });
        });

        // Agregar eventos de clic a los botones de cerrar
        highlightInfos.forEach(info => {
            const closeButton = info.querySelector(".highlight-close");
            closeButton.addEventListener("click", function() {
                const id = this.getAttribute("data-id");
                const targetInfo = document.querySelector(`#highlight-info-${id}`);
                targetInfo.classList.remove("highlight-info-active");
                targetInfo.setAttribute("aria-expanded", "false");

                // También actualizar el botón de enlace
                const linkButton = document.querySelector(`#highlight-link-${id}`);
                linkButton.setAttribute("aria-expanded", "false");
            });
        });
    });
});

