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

            /**
             * Validation creation.
             *
             * @protected
             */
            _create: function () {
                $('#vantiv-cc-form').submit(this.submitEprotect.bind(this));
                this.options.submitFlag = false;

                var scriptUrl = this.options.config.scriptUrl;
                delete this.options.config.scriptUrl;

                require([scriptUrl], this.initEprotect.bind(this));
            },

            /**
             * Init payframe client.
             */
            initEprotect: function () {
                this.options.config.callback = this.eprotectCallback.bind(this);
                this.payframeClient = new LitlePayframeClient(this.options.config);
            },

            /**
             * Submit eProtect iFrame.
             */
            submitEprotect: function () {

                if (!this.options.submitFlag) {
                    this.payframeClient.getPaypageRegistrationId({
                        "id": Math.floor(Math.random() * 999999),
                        "orderId": ""
                    });

                    return false;
                } else {
                    return true;
                }
            },

            /**
             * eProtect submit callback.
             *
             * @param responseData
             */
            eprotectCallback: function (responseData) {
                if (responseData.response == "870") {
                    this.options.submitFlag = true;

                    $("#vantiv-paypage-registration-id").val(responseData.paypageRegistrationId);
                    $("#vantiv-cc-type").val(responseData.type);
                    $("#vantiv-cc-last-four").val(responseData.lastFour);
                    $("#vantiv-cc-exp-month").val(responseData.expMonth);
                    $("#vantiv-cc-exp-year").val(responseData.expYear);
                    $('#vantiv-cc-form').submit();
                } else {
                    alert({
                        title: $t('Credit Card Form'),
                        content: $t('An error occurred: ') + $t(responseData.message)
                    });
                }
            }
        });

        return $.mage.eprotect;
    }
);
