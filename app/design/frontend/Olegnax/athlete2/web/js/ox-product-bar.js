define(['jquery'], function($) {
    'use strict';

    return function(config, element) {
        const $pBar = $(element),
            showQty = config.showQty || false,
            stickyHeader = config.stickyHeader || false,
            positionTop = config.positionTop || false,
            $cartForm = ($(config.cartForm).length ? $(config.cartForm) : $("#product_addtocart_form")),
            $header = ($(config.header).length ? $(config.header) : $(".page-header"));
            let cartFormOffset, dHeight, wHeight, headerHeight;
        if(!$cartForm.length){
         return;
        }

        const syncButtons = () => {
            const $btn = $('#product-addtocart-button'),
            $barBtn = $('#product-bar-product-addtocart-button');
            let o = null;
            if($btn.length && $barBtn.length){
                function updateBtn() {
                    requestAnimationFrame(() => {    
                        $barBtn.attr('class', $btn.attr('class'));
                        $barBtn.attr('title', $btn.attr('title'));
                        $barBtn.find('span').first().text($btn.find('span').text());
                    });
                }

                $barBtn.on('click', function(){
                    if(o === null){
                        o = new MutationObserver(updateBtn);
                        o.observe($btn[0], { attributes: true, childList: true, subtree: true });
                    }
                    $btn.first().trigger("click");
                });
            }
        }

        const syncPrices = () => {
            let o = null;
            var priceElement = $('.product-info-main .price-box');
            if(!priceElement.length) { return }
            function updatePrice() {
                requestAnimationFrame(() => {
                    $('.product-bar__info-price .price-box').html('<span class="price">' + priceElement.html() +  '</span>');
                });
            }
            if(o === null){
                o = new MutationObserver(updatePrice);
                o.observe(priceElement[0], {attributes: true, childList: true, subtree: true  });
            }
        }

        const syncInputs = () => {
            var $qtyInput = $cartForm.find('.input-text.qty');
            var $qtyInputPbar = $pBar.find('#product-bar-qty');  
            if($qtyInputPbar.length && $qtyInput.length){
                $qtyInput.on('change', function() {
                    $qtyInputPbar.val($(this).val());
                    $qtyInputPbar.change();
                });
            }                
            const $minus = $cartForm.find('.qty-minus'),
                $plus = $cartForm.find('.qty-plus');
            if($minus.length && $plus.length){
                $('#product-bar-qty-minus').on('click', function(e){
                    if($qtyInput.length){ e.stopPropagation(); } 
                    $minus.trigger("click");
                });
                $('#product-bar-qty-plus').on('click', function(e){
                    if($qtyInput.length){ e.stopPropagation(); } 
                    $plus.trigger("click");
                });
            }
        }

        const getHeaderHeight = () => {
            if(typeof window.a2header_sticky_height !== 'undefined'){
                if(!headerHeight || headerHeight != window.a2header_sticky_height) { 
                    headerHeight = window.a2header_sticky_height;
                    $pBar.css('--ox-sh', parseInt(headerHeight) + 'px' );
                }
            }
        }

        const updateBar = () => {
            const sPos = $(window).scrollTop();
            dHeight = $(document).height(); /* required for dynamically added content to the page */
            const isCartFormVisible = !(sPos <= cartFormOffset) && (dHeight > (wHeight + sPos + 400 ));
           
            requestAnimationFrame(() => {
                $pBar.toggleClass('show', isCartFormVisible);
                $('body').toggleClass('pbar-is-visible', isCartFormVisible)
            });
            if(isCartFormVisible && stickyHeader && positionTop){
                requestAnimationFrame(() => {
                    $pBar.toggleClass('header-is-resized', $header.hasClass('resize'));
                    $pBar.toggleClass('header-is-smart', $header.hasClass('sticky-scroll-up'));
                    $pBar.toggleClass('header-is-sticky', $header.hasClass('sticky'));
                });
            }
            if(!$pBar.hasClass('init')){$pBar.addClass('init')}
        }
        const initProductBar = () => {
            if($pBar.hasClass('hide-on-mobile') && $( window ).width() < 1025){
                return;
            }
            cartFormOffset = $cartForm.offset().top + $cartForm.outerHeight();
            dHeight = $(document).height();
            wHeight = $(window).height();
            requestAnimationFrame(() => {
                document.documentElement.style.setProperty('--ox-pbar-height', (parseInt($pBar[0].getBoundingClientRect().height) + 'px'));
            });
            $('.page-header').on('heightsticky.ox', () => {
                requestAnimationFrame(() => getHeaderHeight());
            });  
            $(window).on('scroll', () => {
                requestAnimationFrame(() => updateBar());
            });  
        }

        syncButtons();
        syncPrices();

        if(showQty){
            syncInputs();
        }
        $('body').on('contentUpdated', initProductBar);
        $(window).on("resize", initProductBar);
        initProductBar();
    };
});