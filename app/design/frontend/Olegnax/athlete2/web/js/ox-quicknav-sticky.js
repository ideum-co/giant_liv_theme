require( [ "jquery" ], function ( $ ) {
    "use strict";
    $( function () {

        const $quickNav = $("#a2-qn");
        const $header = $(".page-header");
        let headerHeight;
        let windowHeight;
        let windowWidth;
        
        var updateQuickNavTop = function () {
            requestAnimationFrame(() => {
                $quickNav.toggleClass('header-is-resized', $header.hasClass('resize'));
                $quickNav.toggleClass('header-is-smart', $header.hasClass('sticky-scroll-up'));
                $quickNav.toggleClass('header-is-sticky', $header.hasClass('sticky'));
            });
        }
        var setStickyHeaderHeight = () => {
            if(typeof window.a2header_sticky_height !== 'undefined'){
                if(!headerHeight || headerHeight != window.a2header_sticky_height) { 
                    headerHeight = window.a2header_sticky_height;
                    $quickNav.css('--ox-sh', parseInt(headerHeight) + 'px' );
                }                               
            } 
        }
        var initQuickNavTop = function(){
            
            windowHeight = window.innerHeight || document.documentElement.clientHeight;
            windowWidth = window.innerWidth || document.documentElement.clientWidth;

            if($quickNav.hasClass('hide-on-mobile') && $( window ).width() < 1025){
                $(window).off('scroll.a2quicknav');
                return;
            }
            $('.page-header').on('heightsticky.ox', () => {
                requestAnimationFrame(() => setStickyHeaderHeight());
            });
        }

        function quickNavInit(){
            let sections = quickNavCollectSections();
            checkIfSectionVisible(sections);
            $(window).on('scroll.a2quicknav', () => {
                requestAnimationFrame(() => {
                    updateQuickNavTop();
                    checkIfSectionVisible(sections);
                });
            });  
        }
        
        function quickNavCollectSections(){
            let _sections = [];
            $('.js-a2-qn__link').each(function() {
                let target = $(this).data('target');
                if(!target){
                    target = $(this).attr('href');
                }
                if ($(target).length) {
                    $(this).toggleClass('js-tab-link', ($(target).data('role') == 'content'));
                    _sections.push(target);
                    $(target).data('quicklink', target);
                    $(this).removeClass('d-none')
                } else {
                    $(this).addClass('d-none'); // Hide the parent li element if section doesn't exist
                }
            });
            if (!_sections.length) {
                $('#a2-qn').hide();
            }
            return _sections;
        }	
        function isElementVisible(elem) {
            var rect = $(elem)[0].getBoundingClientRect();
            // Check if any part of the element is visible
            var $shift = 150;// reduce the visible area by 150px from top and bottom
            return (
                rect.top < (windowHeight - $shift) &&
                rect.bottom > $shift &&
                rect.left < windowWidth &&
                rect.right > 0
            );
        }
        function checkIfSectionVisible(sections){
            if(!sections.length){
                return;
            }
            $(sections).each(function(){
                var elem = $(this),
                    elem_class = elem.data('quicklink');
                $('.js-a2-qn__link[data-target="'+ elem_class +'"]').toggleClass('active', isElementVisible(elem));
            });
        }
        $('body').on('click', '.js-a2-qn__link:not(.js-tab-link)', function(e){
            e.preventDefault();           
            let target = $(this).data('target');
            if(target && $(target).length){
                $('html, body').animate({
                    scrollTop: $(target).offset().top - 200
                }, 200);
            }
        });
        quickNavInit();
        initQuickNavTop();
        $('body').on('contentUpdated', () => {initQuickNavTop(); quickNavInit();});
        $(window).on("resize", () => {initQuickNavTop(); quickNavInit();});
    } );
} );