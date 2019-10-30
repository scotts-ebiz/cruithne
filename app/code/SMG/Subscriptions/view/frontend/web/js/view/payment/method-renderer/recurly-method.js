define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
    ],
    function($, Component) {
        'use strict';

        setTimeout(function() {
            console.log('ready');
            recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');

        }, 100)

        return Component.extend({
            defaults: {
                template: 'SMG_Subscriptions/payment/recurly'
            },
 
            placeOrder: function() {
                event.preventDefault();
                var form = document.querySelector('.recurly-form');
                recurly.token(form, function(err, token) {
                    if( err ) {
                        console.log( err );
                    } else {
                        console.log(token);
                        //recurly.token(form, tokenHandler)
                        form.submit();
                    }
                })
            }
 
 
        });
    return recurltconf;
    }

);