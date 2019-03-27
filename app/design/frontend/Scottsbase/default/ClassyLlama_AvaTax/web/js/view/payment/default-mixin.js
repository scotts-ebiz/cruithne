
define([
    'ko',
    'underscore',
    'ClassyLlama_AvaTax/js/model/address-model',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'uiRegistry',
], function (ko, _, addressModel, paymentService, checkoutData, quote, checkoutDataResolver, registry) {
    'use strict';

    return function (target) {
        return target.extend({
            isPlaceOrderActionAllowed: ko.observable(false),

            /**
             * Initialize view.
             *
             * @return {exports}
             *
             */
            initialize: function () {
                var billingAddressCode,
                    billingAddressData,
                    defaultAddressData;

                this._super().initChildren();

                this.isPlaceOrderActionAllowed(false);

                quote.billingAddress.subscribe(function (address) {
                    this.handlePlaceOrderButton(address);
                }, this);

                addressModel.selectedAddress.subscribe(function (address) {
                    this.handlePlaceOrderButton(address);
                }, this);

                addressModel.error.subscribe(function (error) {
                    this.handlePlaceOrderButton(error);
                }, this);


                checkoutDataResolver.resolveBillingAddress();

                billingAddressCode = 'billingAddress' + this.getCode();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    defaultAddressData = checkoutProvider.get(billingAddressCode);

                    if (defaultAddressData === undefined) {
                        // Skip if payment does not have a billing address form
                        return;
                    }
                    billingAddressData = checkoutData.getBillingAddressFromData();

                    if (billingAddressData) {
                        checkoutProvider.set(
                            billingAddressCode,
                            $.extend(true, {}, defaultAddressData, billingAddressData)
                        );
                    }
                    checkoutProvider.on(billingAddressCode, function (providerBillingAddressData) {
                        checkoutData.setBillingAddressFromData(providerBillingAddressData);
                    }, billingAddressCode);
                });

                return this;
            },

            /**
             * Disable button when AvaTax could not validate address
             * @param address
             */
            handlePlaceOrderButton: function (address) {
                var isValidSelected = true;

                if(address !== null) {
                    isValidSelected = true;
                }

                if(addressModel.error() !== null) {
                    isValidSelected = false
                }

                if(addressModel.selectedAddress() !== null) {
                    if(!_.isEqual(addressModel.selectedAddress(), addressModel.validAddress())) {
                        isValidSelected = false;
                    }
                }

                this.isPlaceOrderActionAllowed(isValidSelected);
            }
        });
    };
});
