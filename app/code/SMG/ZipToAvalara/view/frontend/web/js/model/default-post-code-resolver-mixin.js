/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';

    return function (target) {
        /**
         * Resolve default post code
         *
         * @returns {String|null}
         */
        target.resolve = function () {
            var zipCode = $.mage.cookies.get('zip-code'),
                postCode = $('input[name="postcode"]').val();

            if (zipCode && postCode) {
                $.mage.cookies.set('estimated-tax', true);
            }

            return zipCode ? zipCode : window.checkoutConfig.defaultPostcode;
        };

        return target;
    };
});
