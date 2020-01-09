require(['jquery', 'Gigya_GigyaIM/js/gigya_script'], function($, gigya){

	window.onGigyaServiceReady = function (serviceName) {
		gigya.Functions.performGigyaActions();
		$('.after-gigya-login').css( { 'display': 'block' } );

		$(document).on('click', '.gigya-composite-control-link', function() {
			setTimeout( function() {
				if( $('.gigya-screen-content .gigya-register-form').length > 0 ) {
					$('.after-gigya-register').css({'display':'block'})
					$('.after-gigya-login').css({'display':'none'})
				} else {
					$('.after-gigya-login').css({'display':'block'})
					$('.after-gigya-register').css({'display':'none'})
				}
			}, 1000)
		})
	};
	
});
