/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push({
            type: 'vantiv_cc',
            component: 'Vantiv_Payment/js/view/payment/method-renderer/vantiv-cc'
        });

        rendererList.push({
            type: 'vantiv_echeck',
            component: 'Vantiv_Payment/js/view/payment/method-renderer/vantiv-echeck'
        });

        rendererList.push({
            type: 'vantiv_paypal_express',
            component: 'Vantiv_Payment/js/view/payment/method-renderer/vantiv-paypal-express'
        });

        return Component;
    }
);
