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
			
			$(".street > .control").append("<a class='cust-btn-add' href='javascript:void(0)'>Add New Line</a>");
			
			
			$("input[name='postcode']").attr('pattern', '[0-9]*');
			$("input[name='postcode']").attr('inputmode', 'numeric');
			$("input[name='telephone']").attr('pattern', '[0-9]*');
			$("input[name='telephone']").attr('inputmode', 'numeric');
		
			var count = 0;
			$('.cust-btn-add').on("click", function(){
				count += 1;
				if (count == 1) {
					$("div[name='shippingAddress.street.1']").css('display','block');
					$("div[name='billingAddressauthorizenet_directpost.street.1']").css('display','block');
					$("div[name='billingAddresscheckmo.street.1']").css('display','block');
				}else if(count == 2){
					$("div[name='shippingAddress.street.2']").css('display','block');
					$("div[name='billingAddressauthorizenet_directpost.street.2']").css('display','block');
					$("div[name='billingAddresscheckmo.street.2']").css('display','block');
					$('.cust-btn-add').css({"pointer-events": "none", "color": "#c2c2c2"});
				}
				else{ 
					$('.cust-btn-add').css({"pointer-events": "none", "color": "#c2c2c2"});
				}
			});
			$('.form-shipping-address input:visible').keyup(function() {
				var str = $(this).val();
				var nval = str.replace(/[&\/\\#,+()$~%.'":*?<>{}@]/g, '');
				$(this).val(nval);
			});

			if ($("input[name='postcode']").val() != '') {
				if($("input[name='postcode']").val() >= 5){
					$('#shipping-method-buttons-container button').prop('disabled', false);
				}
			}
		}, 7000);
		setTimeout(function(){
			if ($("input[name='postcode']").val() != '') {
				if($("input[name='postcode']").val() >= 5){
					$('#shipping-method-buttons-container button').prop('disabled', false);
				}
			}
		}, 12000);
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
});

