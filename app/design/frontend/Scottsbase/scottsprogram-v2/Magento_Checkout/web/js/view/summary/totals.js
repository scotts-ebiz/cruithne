define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total'
], function (ko, Component) {
    'use strict';

    return Component.extend({
        initialize() {
            this._super();

            this.subscriptionData = ko.observable(window.subscriptionData);

            const summaryInterval = setInterval(() => {
                let summaryContainer = document.querySelector('.opc-block-summary')
                if(summaryContainer) {
                    
                    const toggle = document.createElement('span');
                    toggle.classList.add('toggle-arrow');
                    summaryContainer.querySelector('.title').appendChild(toggle);

                    toggle.addEventListener('click', this.toggleSummary.bind(null, summaryContainer));

                    this.summary = summaryContainer;
                    clearInterval(summaryInterval);
                }
            }, 100);

        },

        /**
         * @return {*}
         */
        isDisplayed: function () {
            return this.isFullMode();
        },

        isTaxDisplayedInGrandTotal: function() {
            return window.checkoutConfig.includeTaxInGrandTotal;
        },

        getGrandTotal: function() {
            const found = this.elems().filter(elem => elem.name === 'checkout.sidebar.summary.totals.grand-total');
            return found ? found[0].getValue() : 0;
        },

        toggleSummary: function(summary) {
            let active = summary.classList.contains('active');
            active ? summary.classList.remove('active') : summary.classList.add('active');
        }
    });
});
