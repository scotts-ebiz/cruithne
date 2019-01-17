/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';

    return function (target) {
        var shippingAddressFromData = target.getShippingAddressFromData();

        if (shippingAddressFromData !== null && shippingAddressFromData.postcode !== '') {
            $.mage.cookies.set('estimated-tax', false);
        }

        if (shippingAddressFromData === null || shippingAddressFromData.postcode === '') {
            target.getShippingAddressFromData = function () {
                var zipCode = $.mage.cookies.get('zip-code');

                if (zipCode) {
                    $.mage.cookies.set('estimated-tax', true);
                }

                return {
                    'postcode': zipCode ? zipCode : ''
                };
            };
        }

        return target;
    };
});
