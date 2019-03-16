/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/estimate-service'
], function (quote) {
    'use strict';

    var mixin = {
        /**
         * @return {*}
         */
        getValue: function () {
            var shippingMethod,
                price;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }

            shippingMethod = quote.shippingMethod();
            price = shippingMethod.amount;

            return this.getFormattedPrice(price);
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
