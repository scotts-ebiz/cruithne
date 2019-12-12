define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Ui/js/modal/modal'
    ],
    function ($, Component, modal) {
        'use strict';

        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            buttons: [{
                text: 'Edit shipping address',
                class: '',
                click: function() {
                    this.closeModal();
                    window.location.href = '/checkout/#shipping';
                }
            }]
        };

        return Component.extend({
            defaults: {
                template: 'SMG_SubscriptionApi/payment/recurly'
            },

            initialize: function () {
                this._super();

                setTimeout(function () {
                    recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
                }, 2000);

                var self = this;

                $(document).on( 'click', 'button#removeFromOrder', function() {
                   self.createNewSubscription( $(this).attr('data-token'), true );
                });
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

            createNewSubscription: function (token_id, remove_not_allowed ) {
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
                        'token': token_id,
                        'quiz_id': quizID,
                        'plan': subscriptionPlan,
                        'remove_not_allowed': remove_not_allowed
                    }),
                    success: function (response) {
                        var response = JSON.parse( response );
                        if( response.success === false ) {
                            if( response.has_not_allowed_products === true ) {

                                var productsHtml = '';

                                productsHtml += '<h5>Cannot ship product</h5>';
                                productsHtml += '<p>We\'re sorry, but one or more of the items in your cart does not ship to your area. Review product(s) below.</p>';
                                productsHtml += '<div class="not-allowed-products-box">';
                                $.each( response.not_allowed_products, function( key, value ) {
                                    console.log( value.name );
                                    productsHtml += '<div class="">';
                                    productsHtml += '<div class="not-allowed-product-image></div>';
                                    productsHtml += '<div class="not-allowed-product-name"><h6>' + value.name + '</h6></div>';
                                    productsHtml += '<div class="not-allowed-product-price"><span>' + value.price + '</span></div>';
                                    productsHtml += '</div>';
                                });
                                productsHtml += '</div>';

                                $('body').append('<div id="popup-modal" class="popup-modal--remove-products">' + productsHtml + '<div><button type="button" class="sp-button sp-button--primary" id="removeFromOrder" data-token="' + token_id + '">Remove from order</button></div></div>');
                            } else {
                                $('body').append('<div id="popup-modal" class="popup-modal--remove-products">' + response.message + '</div>');
                            }

                            $('.popup-modal--remove-products').modal(options).modal('openModal');
                        } else {
                            self.createNewOrders();
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
                        'billing_address': address
                    } ),
                    success: function (response) {
                        var response = JSON.parse( response );
                        if ( response.success === true ) {
                            window.location.href = '/checkout/onepage/success';
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
                    rsco[0].setCustomValidity(true);

                    return false;
                } else {
                    rsco[0].setCustomValidity(false);
                }

                self.updateRecurlyFormData();

                recurly.token(recurlyForm, function (err, token) {
                    if (err) {
                        console.log(err);
                    } else {
                        self.createNewSubscription( token.id, false );
                    }
                })
            },


        });
    }
);
