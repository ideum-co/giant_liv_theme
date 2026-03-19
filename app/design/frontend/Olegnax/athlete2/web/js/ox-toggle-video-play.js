require([
    'jquery'
], function ($) {
    'use strict';
    $("body").on('click', ".js-video-pause-btn", function (event) {
            event.preventDefault();
            var video = $(this).closest('.ox-video-wrapper').find('video').get( 0 );
            if (video && video.tagName.toLowerCase() === 'video') {
                OxVideoTogglePause(video, $(this));
            }
    });
    async function oxPlayVideo (video) {
        try {
            await video.play();
        } catch (err) {
            console.error(err);
        }
    }
    function oxPauseVideo(video) {
        if(video.currentTime > 0 && !video.paused && !video.ended && video.readyState > 2){
            video.pause();
        }
    }
    function OxVideoTogglePause(video, btn) {
        if (video.paused) {
            oxPlayVideo(video);
            btn.removeClass('paused');
        } else {
            oxPauseVideo(video);
            btn.addClass('paused');
        }
    }
});