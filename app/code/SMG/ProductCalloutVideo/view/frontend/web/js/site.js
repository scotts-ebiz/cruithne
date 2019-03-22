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


    /* Scottsprogram Landing Page Code: Start */

    $('#tablabel__1').click(function () {
        if ($("#tabcontent__1").hasClass("active")) {
            //Do nothing
        } else {
            //Add or Remove Class for tablabel
            $('#tablabel__1').addClass('active');
            $('#tablabel__2').removeClass('active');
            $('#tablabel__3').removeClass('active');
            $('#tablabel__4').removeClass('active');
            $('#tablabel__5').removeClass('active');
            $('#tablabel__6').removeClass('active');

            //Add or remove Class for tabcontent
            $('#tabcontent__1').addClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__2').click(function () {
        if ($("#tabcontent__2").hasClass("active")) {
            //Do nothing
        } else {
            //Add or Remove Class for tablabel
            $('#tablabel__2').addClass('active');
            $('#tablabel__1').removeClass('active');
            $('#tablabel__3').removeClass('active');
            $('#tablabel__4').removeClass('active');
            $('#tablabel__5').removeClass('active');
            $('#tablabel__6').removeClass('active');

            //Add or remove Class for tabcontent
            $('#tabcontent__2').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__3').click(function () {
        if ($("#tabcontent__3").hasClass("active")) {
            //Do nothing
        } else {
            //Add or Remove Class for tablabel
            $('#tablabel__3').addClass('active');
            $('#tablabel__1').removeClass('active');
            $('#tablabel__2').removeClass('active');
            $('#tablabel__4').removeClass('active');
            $('#tablabel__5').removeClass('active');
            $('#tablabel__6').removeClass('active');

            //Add or remove Class for tabcontent
            $('#tabcontent__3').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__4').click(function () {
        if ($("#tabcontent__4").hasClass("active")) {
            //Do nothing
        } else {
            //Add or Remove Class for tablabel
            $('#tablabel__4').addClass('active');
            $('#tablabel__1').removeClass('active');
            $('#tablabel__2').removeClass('active');
            $('#tablabel__3').removeClass('active');
            $('#tablabel__5').removeClass('active');
            $('#tablabel__6').removeClass('active');

            //Add or remove Class for tabcontent
            $('#tabcontent__4').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__5').click(function () {
        if ($("#tabcontent__5").hasClass("active")) {
            //Do nothing
        } else {
            //Add or Remove Class for tablabel
            $('#tablabel__5').addClass('active');
            $('#tablabel__1').removeClass('active');
            $('#tablabel__2').removeClass('active');
            $('#tablabel__3').removeClass('active');
            $('#tablabel__4').removeClass('active');
            $('#tablabel__6').removeClass('active');

            //Add or remove Class for tabcontent
            $('#tabcontent__5').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__6').click(function () {
        if ($("#tabcontent__6").hasClass("active")) {
            //Do nothing
        } else {//Add or Remove Class for tablabel
            $('#tablabel__6').addClass('active');
            $('#tablabel__1').removeClass('active');
            $('#tablabel__2').removeClass('active');
            $('#tablabel__3').removeClass('active');
            $('#tablabel__4').removeClass('active');
            $('#tablabel__5').removeClass('active');

            //Add or remove Class for tabcontent
            $('#tabcontent__6').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
        };

        /* Scottsprogram Landing Page Code: End */
    });

});
