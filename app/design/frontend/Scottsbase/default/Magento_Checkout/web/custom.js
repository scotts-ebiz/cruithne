require([
	'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate'
	],
function($){
    "use strict";
	$(function() {

		setTimeout(function(){

			$("input[name='postcode']").attr('pattern', '[0-9]*');
			$("input[name='postcode']").attr('inputmode', 'numeric');
			$("input[name='telephone']").attr('pattern', '[0-9]*');
			$("input[name='telephone']").attr('inputmode', 'numeric');

			var count = 0;
			$('.form-shipping-address input:visible').keyup(function() {
				var str = $(this).val();
				var nval = str.replace(/[&\/\\#,+$~%*?<>{}@!^]/g, '');
				$(this).val(nval);
			});
			$(".form-shipping-address .street input").keyup(function() {
				var str = $(this).val();
				var nval = str.replace(/  +/g, ' ');
				$(this).val(nval);
			});

            /*------- Shipping Page - Enable "Next" btn if fields are not empty  --------*/
			if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
					$("select[name='region_id']").click();
					$('#shipping-method-buttons-container button').prop('disabled', false);
			}

            /*------- Shipping Page - Disable "Next" btn if fields are empty  --------*/
			$(".checkout-shipping-address input:visible, select[name='region_id']").on('keyup change', function(e) {
				$('#shipping-method-buttons-container button').prop('disabled', true);
				if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
					$('#shipping-method-buttons-container button').prop('disabled', false);
				}
			});

		}, 7000);
	});

	/*------- Sticky Header --------*/
	$(window).scroll(function(){
		var sticky = $('.custom-checkout-btn-wrap');

		if(typeof  sticky != "undefined" && sticky){

			if ($(window).scrollTop() >= 200) {
				$('.custom-checkout-btn-wrap').addClass('stickyCart');
				$('.custom-checkout-btn-wrap').removeClass('slide-up');
			}
			else {
				$('.custom-checkout-btn-wrap').removeClass('stickyCart');
				$('.custom-checkout-btn-wrap').addClass('slide-up');
			}

		}
	});

	/*------- Discount code - toggle --------*/
	$('#block-discount .title').click(function() {
		$('#block-discount .content').toggleClass('disc_active');
    });
});