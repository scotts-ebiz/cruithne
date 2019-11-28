define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'domReady!',
    ],
    function($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SMG_SubscriptionApi/payment/recurly'
            },

            initialize: function() {
                this._super();
                
                setTimeout(function() {
                    recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
                }, 2000)
            },

            createNewSubscription: function(cancel_existing) {
                event.preventDefault();

                var self = this;

                var form = document.querySelector('.recurly-form');
                var plan_code = $('input[name="subscription_plan"]').val();
                var addon_products = [];
                $("input[name='addon_products[]']:checked").each(function () {
                    addon_products.push($(this).val());
                });

                recurly.token(form, function(err, token) {
                    console.log(token);
                    if( err ) {
                        alert( err.message );
                    } else {
                        $.ajax({
                            type: 'POST',
                            url: window.location.origin + '/rest/V1/subscriptions/new',
                            dataType: 'json',
                            contentType: 'application/json',
                            processData: false,
                            data: JSON.stringify({'token': token.id, 'order': checkoutConfig, 'cancel_existing': cancel_existing, 'addon_products': addon_products, 'plan_code': plan_code }),
                            success: function(response) {
                                console.log(response);

                                if( response[0].success == true ) {
                                    self.placeOrder();
                                } else {
                                    alert( response[0].message );
                                }
                            }
                        });
                    }
                });
            },
 
            myPlaceOrder: function() {
                event.preventDefault();

                var self = this;

                var form = document.querySelector('.recurly-form');

                recurly.token(form, function(err, token) {
                    console.log(token);
                    if( err ) {
                        alert( err.message );
                    } else {
                        
                        // $.ajax({
                        //     type: 'POST',
                        //     url: window.location.origin + '/rest/V1/subscriptions/check',
                        //     dataType: 'json',
                        //     contentType: 'application/json',
                        //     processData: false,
                        //     data: JSON.stringify({'token': token.id, 'order': checkoutConfig}),
                        //     success: function(response) {
                        //         console.log(response);

                        //         if( response[0].success === false && response[0].has_subscription === true ) {
                        //             if( confirm( response[0].message ) ) {
                        //                 self.createNewSubscription(true);
                        //             } else {
                        //                 window.location.href = response[0].redirect_url
                        //             }
                        //         } else {
                        //             self.createNewSubscription(false);
                        //         }
                        //     }
                        // });
                    }
                })
            },
 
 
        });
    }

);