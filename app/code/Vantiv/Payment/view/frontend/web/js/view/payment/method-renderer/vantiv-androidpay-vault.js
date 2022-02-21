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
            template: 'Vantiv_Payment/payment/vantiv-androidpay-vault'
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
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.ccLast4;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.ccExpMonth + '/' + this.details.ccExpYear;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.ccType;
        }

    });
});
