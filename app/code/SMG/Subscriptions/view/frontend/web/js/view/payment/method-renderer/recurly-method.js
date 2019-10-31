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
                template: 'SMG_Subscriptions/payment/recurly'
            },

            initialize: function() {
                this._super();
                
                setTimeout(function() {
                    recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
                    $('input[data-recurly="first_name"]').val(customerData.firstname);
                    $('input[data-recurly="last_name"]').val(customerData.lastname);
                    $('input[data-recurly="address1"]').val(customerData.addresses[customerData.default_billing].street[0]);
                    $('input[data-recurly="city"]').val(customerData.addresses[customerData.default_billing].city);
                    $('input[data-recurly="country"]').val(customerData.addresses[customerData.default_billing].country_id);
                    $('input[data-recurly="state"]').val(customerData.addresses[customerData.default_billing].region.region_code);
                    $('input[data-recurly="postal_code"]').val(customerData.addresses[customerData.default_billing].postcode);
                }, 2000)
            },
 
            myPlaceOrder: function() {
                event.preventDefault();

                var self = this;

                var form = document.querySelector('.recurly-form');
                var orderForm = document.querySelector('#co-payment-form');
                recurly.token(form, function(err, token) {
                    if( err ) {
                        console.log( err );
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
                                    self.placeOrder();
                                } else {
                                    console.log(response);
                                }
                            }
                        });
                    }
                })
            },
 
 
        });
    }

);