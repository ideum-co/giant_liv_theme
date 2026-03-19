require([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    $("body").on('click', ".js-thumb-nav", function(e){
        const $wrapper = $(this).closest(".product-item"),
         $image = $wrapper.find('.product-image-photo'),
         $dots = $wrapper.find('.ox-dots'),
         $dot = $dots.find('.dot'),
         $counter = $dots.find('.ox-dots__current'),
         count =  $image.length;//$dots.data('count');
        let current = $dots.data('current');
        $($image[current]).removeClass('show').addClass('hide');
        $($dot[current]).removeClass('active');
        if($(this).hasClass('prev')){
            current = (current - 1 + count) % count;
        } else {
            current = (current + 1) % count;
        } 
        $dots.data('current', current);
        $counter.text(current + 1);
        $($dot[current]).addClass('active');
        $($image[current]).removeClass('hide').addClass('show');
    });
});
