
require([
    'jquery',
], function ($) {
    /* cms banner animation */
   
    var initBanner = function(element){
        cmsBanner = $(element); 
        if(!cmsBanner.hasClass('show')){
            var cmsBannerText = cmsBanner.find('.ox-banner-animated-container');
            if (!cmsBannerText.length)
                return;
    
            $('.text', cmsBanner).wrap('<div class="animation-wrapper animation-text" />');
            $('.link', cmsBanner).wrap('<div class="animation-wrapper animation-link" />');
            $(' br', cmsBannerText).hide();    
            
            $('.animation-wrapper', cmsBannerText).each(function (i) {
                $(this).css({
                    '--a2-anim-banner-delay': (64 * i) + 'ms',
                    'animation-delay': (64 * i) + 'ms'
               });
            });
            cmsBanner.addClass('show');            
        } 
    }
    /* Init banners which dynamically added/appended to the page */
    const observeAddedToContent = new MutationObserver(mutationList =>  
        mutationList.filter(m => m.type === 'childList').forEach(m => {  
            m.addedNodes.forEach(node => {
                $(node).find('.ox-banner-animated-text').each(function(){
                    initBanner($(this));
                    // observeVisibility.observe($(this)[0]);
                });
            });  
        }));    
    
    observeAddedToContent.observe(document.body, { attributes: true, childList: true, characterData: true, subtree: true });

    /* Init banner when it is visible in viewport */
    const observeVisibility = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initBanner(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });
    const banners = document.querySelectorAll(".ox-banner-animated-text");
        banners.forEach(banner => {
        observeVisibility.observe(banner);
    });
});