define([
    'jquery'
], function ($) {
    "use strict";
    /* Video Widget Code: Start */
    $(".heroVideo-video-link").on("click", function (e) {
        var embedCode = $(this).find(".embed-code").html();
        $("body").toggleClass("heroVideo-video-open");
        $("#heroVideo-video-container .video-frame").html(embedCode);
        e.preventDefault();
    });

    $(".heroVideo-video-overlay").on("click", function (e) {
        $("body").toggleClass("heroVideo-video-open");
        $("#heroVideo-video-container .video-frame").html('');
    });
    $(document).keyup(function(e) {
     if (e.key === "Escape") { // escape key maps to keycode `27`
         $("body").toggleClass("heroVideo-video-open");
        $("#heroVideo-video-container .video-frame").html('');
     }
    });
    /* Video Widget Code: End */
});