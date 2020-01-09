require(['jquery'], function($){

	window.onGigyaServiceReady = function () {
		alert('ready')
	};

	function changeAfterGigyaContent() {
		setTimeout(function() {
			console.log($('.gigya-screen-content .gigya-register-form').length);
			if( $('.gigya-screen-content .gigya-register-form').length > 0 ) {
				$('.after-gigya-register').css({'display':'block'})
				$('.after-gigya-login').css({'display':'none'})
			} else {
				$('.after-gigya-login').css({'display':'block'})
				$('.after-gigya-register').css({'display':'none'})
			}
		}, 3000)
	}

	$(document).on('click', '.gigya-composite-control-link', function() {
		changeAfterGigyaContent();
	})
});
