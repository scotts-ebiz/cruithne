/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault'
], function ($, VaultComponent) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Vantiv_Payment/payment/vantiv-echeck-vault'
        },

        /**
         * Get public hash.
         *
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },

        /**
         * Get last 3 digits of bank account number.
         *
         * @returns {String}
         */
        getMaskedAccountNumber: function () {
            return this.details.maskedAccountNumber;
        },

        /**
         * Get routing number.
         *
         * @returns {String}
         */
        getEcheckRoutingNumber: function () {
            return this.details.echeckRoutingNumber;
        },

        /**
         * Get account type.
         *
         * @returns {String}
         */
        getEcheckAccountType: function () {
            return this.details.echeckAccountType;
        }

    });
});
