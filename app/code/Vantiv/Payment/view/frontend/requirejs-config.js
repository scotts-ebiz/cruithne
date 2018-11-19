/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

var config = {
    map: {
        '*': {
            vantivPriceSubscription: 'Vantiv_Payment/js/price-subscription'
        }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'Vantiv_Payment/js/price-box-mixin': true
            },
            'Magento_Customer/js/model/authentication-popup': {
                'Vantiv_Payment/js/authentication-popup-mixin': true
            }
        }
    }
};
