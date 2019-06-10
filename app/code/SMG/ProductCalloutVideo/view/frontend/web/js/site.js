define([
    'jquery'
], function ($) {
    "use strict";
    /* Product Callout Video Widget: Start */
    $(".productCalloutVideo-video-link").on("click", function (e) {
        var embedCode = $(this).find(".embed-code").html();
        $("body").toggleClass("video-open");
        $("#productCalloutVideo-video-container .video-frame").html(embedCode);
        e.preventDefault();
    });

    $(".productCalloutVideo-video-overlay").on("click", function (e) {
        $("body").toggleClass("video-open");
        $("#productCalloutVideo-video-container .video-frame").html('');
    });
    /* Product Callout Video Widget: End */
});
