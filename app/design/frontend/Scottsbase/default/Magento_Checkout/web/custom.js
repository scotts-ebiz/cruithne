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
				}else if(count == 2){
					$("div[name='shippingAddress.street.2']").css('display','block');
					$('.cust-btn-add').css({"pointer-events": "none", "color": "#c2c2c2"});
				}
				else{
					$('.cust-btn-add').css({"pointer-events": "none", "color": "#c2c2c2"});
				}
			});
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

			if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
					$("select[name='region_id']").click();
					$('#shipping-method-buttons-container button').prop('disabled', false);
			}

			$(".checkout-shipping-address input:visible, select[name='region_id']").on('keyup change', function(e) {
				$('#shipping-method-buttons-container button').prop('disabled', true);
				if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
					$('#shipping-method-buttons-container button').prop('disabled', false);
				}
			});

		}, 7000);
	});

	/*------- Discount code - toggle --------*/
	$('#block-discount .title').click(function() {
		$('#block-discount .content').toggleClass('disc_active');
});

});
