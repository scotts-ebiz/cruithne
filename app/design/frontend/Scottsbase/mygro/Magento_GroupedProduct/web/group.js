require([
	'jquery', 
    'jquery/ui', 
    'jquery/validate', 
    'mage/translate'
	],
function($){
    "use strict";
	$(function() {
		var selectopt = $("#product_selector option:selected").val();
		var price = $("#product_selector option:selected").attr("price");
		
		$('.qty-group').attr('name', 'super_group['+selectopt+']');
		$('.qty-group').attr('data-selector', 'super_group['+selectopt+']');
		$(".product-detail-td .price").text(price);
		
		setTimeout(function(){
			$('#product_selector').on('change', function() {
				var id = $(this).val();
				var price = $("#product_selector option:selected").attr("price");
				
				$('.qty-group').attr('name', 'super_group['+id+']');
				$('.qty-group').attr('data-selector', 'super_group['+id+']');
				$(".product-detail-td .price").text(price);
			});
			
			$('#qtyplus').click(function add() {
				var $qtys = $(".qty-group");
				var a = $qtys.val();
				
				a++;
				$("#qtyminus").prop("disabled", !a);
				$qtys.val(a);
			});
			$("#qtyminus").prop("disabled", !$(".qty-group").val());

			$('#qtyminus').click(function subst() {
				var $qtys = $(".qty-group");
				var b = $qtys.val();
				if (b >= 1) {
					b--;
					$qtys.val(b);
				}
				else {
					$("#qtyminus").prop("disabled", true);
				}
			});
			
		}, 7000);
		
	});
});

