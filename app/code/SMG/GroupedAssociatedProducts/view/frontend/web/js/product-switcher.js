/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (target) {
        ("#super-product-table").html("<h2>jQuery Hello World</h2>");
        return target;
    };
});
