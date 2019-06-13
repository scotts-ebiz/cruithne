define([
    'jquery'
], function ($) {
    "use strict";
    /* Video Widget Code: Start */
    $(".heroVideoBtn-video-link").on("click", function (e) {
        var embedCode = $(this).find(".embed-code").html();
        $("body").toggleClass("heroVideoBtn-video-open");
        $("#heroVideoBtn-video-container .video-frame").html(embedCode);
        e.preventDefault();
    });

    $(".heroVideoBtn-video-overlay").on("click", function (e) {
        $("body").toggleClass("heroVideoBtn-video-open");
        $("#heroVideoBtn-video-container .video-frame").html('');
    });
    /* Video Widget Code: End */
});