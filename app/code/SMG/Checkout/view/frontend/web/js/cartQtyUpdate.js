define([
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/shipping-rate-processor/new-address',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/model/quote',
], function ($, getTotalsAction, cartCache, shippingService, customerData, newAddress, checkoutDataResolver, quote) {

    return function(config) {
        $(document).on('click', '.update_cust_btn', function(){
            var form = $('form#form-validate');
            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                showLoader: true,
                async: true,
                success: function (res) {
                    var parsedResponse = $.parseHTML(res);
                    var result = $(parsedResponse).find("#form-validate");
                    var content = $(parsedResponse).find("#maincontent");
                    var messages = $(parsedResponse).find(".messages");
                    var totals = quote.getTotals()();
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
                    }else{
                        if($(res).find("#coupon_code").val().length == 0 && (totals && totals.discount_amount != 0)){
                            location.reload();
                        }
                    }

                },
                error: function (xhr, status, error) {
                    var err = eval("(" + xhr.responseText + ")");
                    console.log(err.Message);
                }
            });
        });

        // This reloads the shipping method when the cart changes
        // because it may affect whether free shipping is applied.
        var cart = customerData.get('cart');
        cart.subscribe(function(data) {
            var checkoutData = customerData.get('checkout-data')();

            if (checkoutData.shippingAddressFromData) {
                var region = checkoutData.shippingAddressFromData.region;
                var countryId = checkoutData.shippingAddressFromData.country_id;
                var postcode = checkoutData.shippingAddressFromData.postcode;
            }

            var shippingAddressFromData = {
                "region": region || "",
                "countryId": countryId || checkoutConfig.originCountryCode || 'US',
                "postcode" : postcode || ""
            };
            shippingAddressFromData.getCacheKey = function(){ return 'new-customer-address' + Date.now()};
            var checkRates = newAddress.getRates(shippingAddressFromData);
            $.when(checkRates).done(function() {
                 checkoutDataResolver.resolveShippingRates(shippingService.getShippingRates());
            });
        });
    }
});