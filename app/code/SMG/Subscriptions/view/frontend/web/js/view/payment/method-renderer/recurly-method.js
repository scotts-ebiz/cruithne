define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/checkout-data',
        'domReady!',
    ],
    function($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SMG_Subscriptions/payment/recurly'
            },

            initialize: function() {
                this._super();

                console.log(checkoutConfig);

                // @todo this shouldn't need a setTimeout
                // @todo we should be pulling customer data from the checkout not from the customer data. First time through a customer doesn't have an address in customer data
                setTimeout(function() {
                    recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
                    $('input[data-recurly="first_name"]').val(customerData.firstname);
                    $('input[data-recurly="last_name"]').val(customerData.lastname);
                    $('input[data-recurly="address1"]').val(customerData.addresses[0].street[0]);
                    $('input[data-recurly="city"]').val(customerData.addresses[0].city);
                    $('input[data-recurly="country"]').val(customerData.addresses[0].country_id);
                    $('input[data-recurly="state"]').val(customerData.addresses[0].region.region_code);
                    $('input[data-recurly="postal_code"]').val(customerData.addresses[0].postcode);
                }, 2000)
            },
 
            myPlaceOrder: function() {
                event.preventDefault();

                var self = this;

                var form = document.querySelector('.recurly-form');
                recurly.token(form, function(err, token) {

                    console.log(err, token);

                    if ( err ) {
                        alert( err.message );
                    } else {
                        
                        $.ajax({
                            type: 'POST',
                            url: window.location.origin + '/rest/V1/subscriptions/new',
                            dataType: 'json',
                            contentType: 'application/json',
                            processData: false,
                            data: JSON.stringify({'token': token.id, 'order': checkoutConfig}),
                            success: function(response) {
                                if( response[0].success == true ) {
                                    alert( response[0].message );
                                } else {
                                    alert( response[0].message );
                                }
                            }
                        });
                    }
                })
            },
 
 
        });
    }

);