define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        if (window.location.hash === '#payment') {
            window.location.href = '/checkout';
        }

        rendererList.push(
            {
                type: 'recurly',
                component: 'SMG_SubscriptionApi/js/view/payment/method-renderer/recurly-method'
            }
        );

        return Component.extend({});
    }
);
