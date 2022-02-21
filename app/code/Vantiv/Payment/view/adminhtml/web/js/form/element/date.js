/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/form/element/date'
], function (jQuery, date) {
    'use strict';

    return date.extend({
        reset: function() {
            this._super();

            var selector = this.input_type + '[name="' + this.inputName + '"]';

            if(!this.initialValue) {
                jQuery(selector).datepicker("setDate", null);
            } else {
                jQuery(selector).datepicker("setDate", this.initialValue);
            }
        }
    });
});
