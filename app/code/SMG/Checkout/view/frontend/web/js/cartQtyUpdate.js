define([
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Customer/js/customer-data'
], function ($, getTotalsAction, customerData) {
 
    $(document).ready(function(){
		$(document).on('click', '.update_cust_btn', function(){
            var form = $('form#form-validate');
            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                showLoader: true,
                success: function (res) {
                    var parsedResponse = $.parseHTML(res);
                    var result = $(parsedResponse).find("#form-validate");
					var content = $(parsedResponse).find("#maincontent");
					var messages = $(parsedResponse).find(".messages");
                    var sections = ['cart'];
					
					$(".messages").replaceWith(messages);
                    $("#form-validate").replaceWith(result);
					$("#ajax_event").html($(res).find("#ajax_event").html());

					/* Minicart reloading */
                    customerData.reload(['cart', 'magepal-gtm-jsdatalayer'], true);
 
                    /* Totals summary reloading */
                    var deferred = $.Deferred();
                    getTotalsAction([], deferred);
					 
					if($('#form-validate').length == 0){
						if($("body").hasClass("empty-cart-page") != 'empty-cart-page'){
							$("body").addClass("empty-cart-page");
						}
						$('meta[name=title]').replaceWith('<meta name="title" content="Your Cart is Empty">');
						$("head title").replaceWith("<title>Your Cart is Empty</title>");
						$("#maincontent").replaceWith(content);
					} 
					
                },
                error: function (xhr, status, error) {
                    var err = eval("(" + xhr.responseText + ")");
                    console.log(err.Message);
                }
            });
        });
    });
});