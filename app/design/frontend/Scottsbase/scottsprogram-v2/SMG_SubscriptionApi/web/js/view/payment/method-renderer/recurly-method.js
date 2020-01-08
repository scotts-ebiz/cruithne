define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'domReady!',
    ],
    function (ko, $, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SMG_SubscriptionApi/payment/recurly'
            },

            initialize: function () {
                this._super();
                this.subscriptionType = ko.observable(window.sessionStorage.getItem('subscription_plan'));
                this.loading = ko.observable(false);

                setTimeout(function () {
                    recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
                }, 2000);
            },

            getShippingAddress: function () {
                var checkoutData = JSON.parse(localStorage['mage-cache-storage']);
                checkoutData = checkoutData['checkout-data'];

                return checkoutData.shippingAddressFromData;
            },

            getBillingAddress: function () {
                var checkoutData = JSON.parse(localStorage['mage-cache-storage']);
                checkoutData = checkoutData['checkout-data'];

                return checkoutData.billingAddressFromData;
            },

            createNewSubscription: function (token_id) {
                var self = this;
                var form = document.querySelector('.recurly-form');
                var quizID = window.sessionStorage.getItem('quiz-id');
                var subscriptionPlan = window.sessionStorage.getItem('subscription_plan');

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + '/rest/V1/subscription/create',
                    dataType: 'json',
                    contentType: 'application/json',
                    processData: false,
                    showLoader: true,
                    data: JSON.stringify({
                        'token': token_id
                    }),
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.success === true) {
                            self.createNewOrders();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            },

            createNewOrders: function () {
                var self = this;
                var formKey = document.querySelector('input[name=form_key]').value;
                var quizID = window.sessionStorage.getItem('quiz-id');

                var isBillingSameAsShipping = ($('input[name="billing-address-same-as-shipping"]:checked').val() == 'on') ? true : false;
                var address = (isBillingSameAsShipping === false) ? this.getBillingAddress() : this.getShippingAddress();

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + '/rest/V1/subscription/createorders',
                    dataType: 'json',
                    contentType: 'application/json',
                    processData: false,
                    showLoader: true,
                    data: JSON.stringify( {
                        'key': formKey,
                        'quiz_id': quizID,
                        'billing_address': address,
                        'billing_same_as_shipping': isBillingSameAsShipping,
                    } ),
                    success: function (response) {
                        if (Array.isArray(response)) {
                            response = response[0];
                        }

                        if (response.success === true) {
                            window.sessionStorage.setItem('subscription_id', response.subscription_id);
                            window.location.href = '/success';
                        }
                    },
                    error: function (response) {
                        response = JSON.parse(response.responseText);

                        if (Array.isArray(response)) {
                            response = response[0];
                        }

                        // This exists because sending invoices is return 500
                        // error codes, however, the response was successful.
                        if (response.success === true) {
                            window.sessionStorage.setItem('subscription_id', response.subscription_id);
                            window.location.href = '/success';
                        }
                    }
                })
            },

            updateRecurlyFormData: function () {
                // Check if customer has selected to use the same address for both billing and shipping
                var isBillingSameAsShipping = ($('input[name="billing-address-same-as-shipping"]:checked').val() == 'on') ? true : false;

                // Get the billing address data based on the customer selection
                var address = (isBillingSameAsShipping === false) ? this.getBillingAddress() : this.getShippingAddress();

                // Get full state name by it's id
                var stateName = $('select[name="region_id"] option[value="' + address.region_id + '"]').attr('data-title');

                // Get full country name by it's id
                var countryName = $('select[name="country_id"] option[value="' + address.country_id + '"]').attr('data-title');

                // Update Recurly form
                $('input[data-recurly="first_name"]').val(address.firstname);
                $('input[data-recurly="last_name"]').val(address.lastname);
                $('input[data-recurly="address1"]').val(address.street[0]);
                $('input[data-recurly="city"]').val(address.city);
                $('input[data-recurly="state"]').val(stateName);
                $('input[data-recurly="country"]').val(countryName);
                $('input[data-recurly="postal_code"]').val(address.postcode);
            },

            myPlaceOrder: function () {
                var self = this;
                var recurlyForm = $('.recurly-form');
                var rsco = $('input[name="rsco_accept"]');

                if (!rsco[0].checked) {
                    rsco[0].setCustomValidity('This field is required.');

                    return false;
                } else {
                    rsco[0].setCustomValidity('');
                }

                self.updateRecurlyFormData();

                recurly.token(recurlyForm, function (err, token) {
                    if (err) {
                        alert(err.message);
                    } else {
                        self.createNewSubscription( token.id );
                    }
                })
            },
        });
    }
);
