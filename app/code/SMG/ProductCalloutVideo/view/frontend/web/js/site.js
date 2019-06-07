define([
    'jquery'
], function ($) {
    "use strict";
    /* Product Callout Video Widget: Start */
    $(".video-link").on("click", function (e) {
        var embedCode = $(this).find(".embed-code").html();
        $("body").toggleClass("video-open");
        $("#video-container .video-frame").html(embedCode);
        e.preventDefault();
    });

    $(".video-overlay").on("click", function (e) {
        $("body").toggleClass("video-open");
        $("#video-container .video-frame").html('');
    });
    /* Product Callout Video Widget: End */
});
