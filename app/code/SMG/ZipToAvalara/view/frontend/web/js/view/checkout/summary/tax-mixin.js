/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([
    'jquery',
    'mage/translate',
    'ko',
    'mage/cookies'
], function ($, $t, ko) {
    'use strict';
    return function (targetModule) {
        return targetModule.extend({
            estimated: ko.observable($t('Tax')),

            /**
             * @return {*}
             */
            getValue: function () {
                var title = $.mage.cookies.get('estimated-tax') === 'true' ? $t('Estimated Tax') : $t('Tax');
                this.estimated(title);

                return this._super();
            }
        });
    };
});
