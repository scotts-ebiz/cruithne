/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Vantiv_Payment/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data'
    ],
    function (
        $,
        Component,
        setPaymentMethodAction,
        additionalValidators,
        quote,
        customerData
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Vantiv_Payment/payment/vantiv-paypal-express'
            },

            /** Open window with  */
            showAcceptanceWindow: function (data, event) {
                window.open(
                    $(event.target).attr('href'),
                    'olcwhatispaypal',
                    'toolbar=no, location=no,' +
                    ' directories=no, status=no,' +
                    ' menubar=no, scrollbars=yes,' +
                    ' resizable=yes, ,left=0,' +
                    ' top=0, width=400, height=350'
                );

                return false;
            },

            /** Returns payment acceptance mark link path */
            getPaymentAcceptanceMarkHref: function () {
                return window.checkoutConfig.payment.vantivPaypalExpress.paymentAcceptanceMarkHref;
            },

            /** Returns payment acceptance mark image path */
            getPaymentAcceptanceMarkSrc: function () {
                return window.checkoutConfig.payment.vantivPaypalExpress.paymentAcceptanceMarkSrc;
            },

            /** Returns payment information data */
            getData: function () {
                var parent = this._super(),
                    additionalData = null;

                return $.extend(true, parent, {
                    'additional_data': additionalData
                });
            },

            /** Redirect to paypal */
            continueToPayPal: function () {
                if (additionalValidators.validate()) {
                    //update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(
                        function () {
                            customerData.invalidate(['cart']);
                            $.mage.redirect(
                                window.checkoutConfig.payment.vantivPaypalExpress.redirectUrl[quote.paymentMethod().method]
                            );
                        }
                    );

                    return false;
                }
            }
        });
    }
);
