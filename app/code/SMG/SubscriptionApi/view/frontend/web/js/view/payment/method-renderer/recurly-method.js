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
                }, 2000);
            },

            getShippingAddress: function() {
                var checkoutData = JSON.parse(localStorage['mage-cache-storage']);
                checkoutData = checkoutData['checkout-data'];

                return checkoutData.shippingAddressFromData;
            },

            getBillingAddress: function() {
                var checkoutData = JSON.parse(localStorage['mage-cache-storage']);
                checkoutData = checkoutData['checkout-data'];

                return checkoutData.billingAddressFromData;
            },

            createNewSubscription: function(token_id, cancel_existing) {
                event.preventDefault();
                var self = this;
                var form = document.querySelector('.recurly-form');
                var quiz = window.sessionStorage.getItem('quiz');
                quiz = JSON.parse(quiz);
                var subscriptionPlan = window.sessionStorage.getItem('subscription_plan');

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + '/rest/V1/subscription/create',
                    dataType: 'json',
                    contentType: 'application/json',
                    processData: false,
                    data: JSON.stringify( {
                        'token': token_id,
                        'quiz': quiz,
                        'plan': subscriptionPlan,
                        'cancel_existing': cancel_existing
                    } ),
                    success: function(response) {
                        if( response[0].success == true ) {
                            self.createNewOrders();
                        } else {
                            alert( response[0].message );
                        }
                    }
                });
            },

            createNewOrders: function() {
                event.preventDefault();
                var self = this;
                var formKey = document.querySelector('input[name=form_key]').value;
                var quiz = window.sessionStorage.getItem('quiz');
                quiz = JSON.parse(quiz);

                var isBillingSameAsShipping = ( $('input[name="billing-address-same-as-shipping"]:checked').val() == 'on' ) ? true : false;
                var address = ( isBillingSameAsShipping === false ) ? this.getBillingAddress() : this.getShippingAddress();

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + '/rest/V1/subscription/createorders',
                    dataType: 'json',
                    contentType: 'application/json',
                    processData: false,
                    data: JSON.stringify( { 'key': formKey, 'quiz_id': quiz.id, 'billing_address': address }),
                    success: function(response) {
                        if( response[0].success == true ) {
                            window.location.href = '/thank-you';
                        }
                    }
                })
            },

            updateRecurlyFormData: function() {
                // Check if customer has selected to use the same address for both billing and shipping
                var isBillingSameAsShipping = ( $('input[name="billing-address-same-as-shipping"]:checked').val() == 'on' ) ? true : false;

                // Get the billing address data based on the customer selection
                var address = ( isBillingSameAsShipping === false ) ? this.getBillingAddress() : this.getShippingAddress();

                // Get full state name by it's id
                var stateName = $('select[name="region_id"] option[value="' + address.region_id + '"]').attr('data-title');

                // Get full country name by it's id
                var countryName = $('select[name="country_id"] option[value="' + address.country_id + '"]').attr('data-title');

                // Update Recurly form
                $('input[data-recurly="address1"]').val(address.street[0]);
                $('input[data-recurly="city"]').val(address.city);
                $('input[data-recurly="state"]').val(stateName);
                $('input[data-recurly="country"]').val(countryName);
                $('input[data-recurly="postal_code"]').val(address.postcode);
            },
 
            myPlaceOrder: function() {
                event.preventDefault();
                var self = this;
                var recurlyForm = $('.recurly-form');

                self.updateRecurlyFormData();

                recurly.token(recurlyForm, function(err, token) {
                    if( err ) {
                        console.log( err );
                    } else {
                        $.ajax({
                            type: 'POST',
                            url: window.location.origin + '/rest/V1/subscription/check',
                            dataType: 'json',
                            contentType: 'application/json',
                            processData: false,
                            success: function(response) {
                                if( response[0].success === false && response[0].has_subscription === true ) {
                                    if( confirm( response[0].message ) ) {
                                        self.createNewSubscription(token.id, true);
                                    } else {
                                        window.location.href = response[0].redirect_url
                                    }
                                } else {
                                    self.createNewSubscription(token.id, false);
                                }
                            }
                        });
                    }
                })
            },
 
 
        });
    }

);