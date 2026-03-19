define(['jquery'], function($) {
    'use strict';

    return function(config, element) {
        const  slider = element.querySelector('.css_slider_items');
        if(!slider){ return }
        /*
        nav = config.nav || true,
        drag = config.drag || true,
        */
        const nav = element.querySelector('.ox-nav');
        const drag = element.dataset.drag;
        if (nav) {
            const prevBtn = element.querySelector('.prev');
            const nextBtn = element.querySelector('.next');
            if(nextBtn && prevBtn){
                const toggleNavVisibility = () => {
                    prevBtn.classList.toggle('disabled', slider.scrollLeft === 0);
                    nextBtn.classList.toggle('disabled', slider.scrollLeft >= (slider.scrollWidth - slider.clientWidth));
                };
                toggleNavVisibility();
                prevBtn.addEventListener('click', function() {
                    slider.scrollBy({
                    left: -slider.clientWidth,
                    behavior: 'smooth'
                    });
                });
                nextBtn.addEventListener('click', function() {
                    slider.scrollBy({
                    left: slider.clientWidth,
                    behavior: 'smooth'
                    });
                    ; 
                });
                slider.addEventListener('scroll', toggleNavVisibility, {passive: true});
            }
        }

        let isMouse = window.matchMedia('(pointer:fine)').matches;

        if (drag && isMouse) {
            let isDown = false;
            let startX;
            let scrollLeft;
            let sliderOffset;
            element.classList.add('is-grabbable');
            slider.addEventListener('mousedown', (e) => {
                isDown = true;
                sliderOffset =  slider.offsetLeft
                startX = e.pageX - sliderOffset;
                scrollLeft = slider.scrollLeft;
                element.classList.add('is-grabbing');
            });
            slider.addEventListener('dragstart', (e) => {
                e.preventDefault();
            });
            $(slider).on('mouseleave mouseup dragend', () => {
                isDown = false;
                element.classList.remove('is-grabbing');
            });
            slider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - sliderOffset;
                const walk = x - startX; 
                slider.scrollLeft = scrollLeft - walk;
            });
        }
    };
});
