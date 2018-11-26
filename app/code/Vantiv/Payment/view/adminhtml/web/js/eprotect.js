/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/modal/alert'
    ],
    function ($, $t, alert) {
        'use strict';

        $.widget('mage.eprotect', {
            options: {
                radioButtonId: '#p_method_vantiv_cc',
                eprotectSuccessCode: 870
            },

            /**
             * Validation creation.
             * @protected
             */
            _create: function () {
                this.radioButton = $(this.options.radioButtonId);
                this.orderSubmit = order.submit.bind(order);
                order.submit = this.eprotectSubmitHandler.bind(this);

                var scriptUrl = this.options.config.scriptUrl;
                delete this.options.config.scriptUrl;

                require([scriptUrl], this.initEprotect.bind(this));
            },

            /**
             * Initialize Eprotect iFrame.
             */
            initEprotect: function () {
                if (typeof LitlePayframeClient === 'undefined') {
                    alert({
                        title: $t('Credit and debit card payment method.'),
                        content: $t('Couldn\'t load Vantiv Payment script. Vantiv Credit Card payment is not available.')
                    });
                } else {
                    this.options.config.callback = this.eprotectCallback.bind(this);
                    this.payframeClient = new LitlePayframeClient(this.options.config);
                }
            },

            /**
             * Check if CC method is selected by user.
             *
             * @returns bool
             */
            isMethodActive: function () {
                return this.radioButton.is(":checked");
            },

            /**
             * New handler for Order submit.
             */
            eprotectSubmitHandler: function () {
                if (this.radioButton.is(":checked")) {
                    return this.submitEprotect()
                } else {
                    return this.orderSubmit();
                }
            },

            /**
             * Submit Eprotect iFrame.
             */
            submitEprotect: function () {
                this.payframeClient.getPaypageRegistrationId({
                    "id": Math.floor(Math.random() * 999999),
                    "orderId": ''
                });
            },

            /**
             * Eprotect submit callback.
             *
             * @param responseData
             */
            eprotectCallback: function (responseData) {
                if (responseData.response == this.options.eprotectSuccessCode) {
                    this.setResponseData(responseData);
                    this.orderSubmit();
                } else {
                    alert({
                        title: $t('Credit and debit card payment method.'),
                        content: $t('An error occurred') + ': ' + $t(responseData.message)
                    });
                }
            },

            /**
             * Set Response Data to hidden inputs.
             *
             * @param responseData
             */
            setResponseData: function (responseData) {
                $("#payment-vantiv-cc-paypage-registration-id").val(responseData.paypageRegistrationId);
                $("#payment-vantiv-cc-type").val(responseData.type);
            }
        });

        return $.mage.eprotect;
    }
);
