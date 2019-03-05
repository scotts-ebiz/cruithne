jQuery(document).ready(function($) {
	/* Product Callout Video Widget: Start */
    $(".video-link").on("click", function(e) {
	    var embedCode = $(this).find(".embed-code").html();
		$("body").toggleClass("video-open");
		$("#video-container .video-frame").html(embedCode);
		e.preventDefault();
	});

	$(".video-overlay").on("click", function(e) {
		$("body").toggleClass("video-open");
		$("#video-container .video-frame").html('');
	});
    /* Product Callout Video Widget: End */


    /* Scottsprogram Landing Page Code: Start */

    $('#tablabel__1').click(function(){
        if ($("#tabcontent__1").hasClass("active")) {
            alert('Tabcontent__1 already has Active class!');
        } else {
            $('#tabcontent__1').addClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__2').click(function(){
        if ($("#tabcontent__2").hasClass("active")) {
            alert('Tabcontent__2 already has Active class!');
        } else {
            $('#tabcontent__2').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__3').click(function(){
        if ($("#tabcontent__3").hasClass("active")) {
            alert('Tabcontent__3 already has Active class!');
        } else {
            $('#tabcontent__3').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__4').click(function(){
        if ($("#tabcontent__4").hasClass("active")) {
            alert('Tabcontent__4 already has Active class!');
        } else {
            $('#tabcontent__4').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__5').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__5').click(function(){
        if ($("#tabcontent__5").hasClass("active")) {
            alert('Tabcontent__5 already has Active class!');
        } else {
            $('#tabcontent__5').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__6').removeClass('active');
        };
    });

    $('#tablabel__6').click(function(){
        if ($("#tabcontent__6").hasClass("active")) {
            alert('Tabcontent__6 already has Active class!');
        } else {
            $('#tabcontent__6').addClass('active');
            $('#tabcontent__1').removeClass('active');
            $('#tabcontent__2').removeClass('active');
            $('#tabcontent__3').removeClass('active');
            $('#tabcontent__4').removeClass('active');
            $('#tabcontent__5').removeClass('active');
        };
    });

    /* Scottsprogram Landing Page Code: End */

});