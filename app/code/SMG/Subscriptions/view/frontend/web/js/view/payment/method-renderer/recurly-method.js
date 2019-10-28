define(
    [
        'Magento_Checkout/js/view/payment/default',
        'recurly'
    ],
    function(Component) {
        'use strict';

        // recurly.configure('PUBLIC_KEY');

        return Component.extend({
            defaults: {
                template: 'SMG_Subscriptions/payment/recurly'
            },
 
            placeOrder: function(event) {
                event.preventDefault();
                var form = document.querySelector('.recurly-form');
                recurly.token(form, function(err, token) {
                    if( err ) {
                        console.log( err );
                    } else {
                        console.log(token);
                        form.submit();
                    }
                })
            }
 
 
        });
    }
);