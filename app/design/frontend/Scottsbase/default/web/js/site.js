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

    alert('New Test?');

    $('#tablabel__1').click(function(){
        if ($("#divOne").hasClass("MojoRisen")) {
            alert('MojoRisen was already there!');
        } else {
            $('#divOne').addClass('MojoRisen');
            alert('You just added MojoRisen!')
        };

        /*$('#divOne').addClass('asdf');*/
    });


});