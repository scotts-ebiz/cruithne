define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/shipping'
    ],
    function(
        $,
        ko,
        Component
    ) {
        'use strict';
        return Component.extend({
            initialize: function () {
                var self = this;
                this._super();

                // Poll for the zip code field and update its value.
                if (window.sessionStorage.getItem('lawn-zip')) {
                    let zipInterval = setInterval(() => {
                        const input = $("input[name='postcode']");
                        if (input.length) {
                            input.val(window.sessionStorage.getItem('lawn-zip')).attr('readonly', true);
                            input.change();
                            clearInterval(zipInterval);
                        }
                    }, 100);
                }
            },
        });
    }
);
