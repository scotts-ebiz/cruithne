define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'recurly'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'paymentmethod',
                component: 'SMG_Subscriptions/js/view/payment/method-renderer/recurly-method'
            }
        );

        return Component.extend({});
    }
);