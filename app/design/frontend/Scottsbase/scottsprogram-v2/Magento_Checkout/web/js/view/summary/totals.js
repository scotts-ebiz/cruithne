define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total'
], function (ko, Component) {
    'use strict';

    return Component.extend({
        initialize() {
            this._super();

            this.subscriptionData = ko.observable(window.subscriptionData);
            this.result = ko.observable(window.sessionStorage.getItem('result') );
        },

        /**
         * @return {*}
         */
        isDisplayed: function () {
            return this.isFullMode();
        }
    });
});
