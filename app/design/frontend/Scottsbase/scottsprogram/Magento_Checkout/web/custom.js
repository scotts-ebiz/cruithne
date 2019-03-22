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
			$(".table-checkout-shipping-method tbody tr:first-child td:first-child").append(" <label class='cust-radio'></label>"); 
			

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

					$("div[name='billingAddresscheckmo.street.1']").css('display','block');
				}else if(count == 2){
					$("div[name='shippingAddress.street.2']").css('display','block');
					$("div[name='billingAddresscheckmo.street.2']").css('display','block');
					$('.cust-btn-add').css({"pointer-events": "none", "color": "#c2c2c2"});
				}
				else{
					$('.cust-btn-add').css({"pointer-events": "none", "color": "#c2c2c2"});
				}
			});
		}, 7000);	

	});
    //your js goes here

});

