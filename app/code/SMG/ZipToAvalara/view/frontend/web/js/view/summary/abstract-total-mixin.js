/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([], function () {
    'use strict';

    var mixin = {
        /**
         * @return {*}
         */
        isFullMode: function () {
            return this.getTotals();
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
