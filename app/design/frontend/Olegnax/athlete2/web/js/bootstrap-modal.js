define(['jquery'], function($) {
    'use strict';

    function closeModal($modal) {
        $modal.removeClass('in');
        $('body').removeClass('faq-modal-open modal-open');
        $modal.off('click.dismiss');
        var $backdrop = $('.faq-modal-backdrop, .modal-backdrop');
        $backdrop.removeClass('in');
        setTimeout(function() { $backdrop.remove(); }, 150);
    }

    $(document).on('click', '[data-toggle="modal"]', function(e) {
        e.preventDefault();
        var target = $(this).data('target') || $(this).attr('href');
        var $modal = $(target);
        if ($modal.length) {
            $('.faq-modal-backdrop, .modal-backdrop').remove();
            var $backdrop = $('<div class="faq-modal-backdrop fade"></div>').appendTo('body');
            $modal.addClass('in');
            $('body').addClass('faq-modal-open modal-open');
            setTimeout(function() { $backdrop.addClass('in'); }, 10);
            $modal.off('click.dismiss').on('click.dismiss', '[data-dismiss="modal"]', function() {
                closeModal($modal);
            });
            $backdrop.on('click', function() {
                closeModal($modal);
            });
        }
    });

    $(document).on('keyup', function(e) {
        if (e.key === 'Escape') {
            var $modal = $('.modal.in');
            if ($modal.length) {
                closeModal($modal);
            }
        }
    });

    $(document).on('click', '.trigger[data-triggerid]', function(e) {
        e.preventDefault();
        var btnId = $(this).data('triggerid');
        $('#' + btnId).trigger('click');
    });
});
