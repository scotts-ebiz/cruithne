/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/default-post-code-resolver': {
                'SMG_ZipToAvalara/js/model/default-post-code-resolver-mixin': true
            },
            'Magento_Checkout/js/view/summary/abstract-total': {
                'SMG_ZipToAvalara/js/view/summary/abstract-total-mixin': true
            },
            'Magento_Checkout/js/view/summary/shipping': {
                'SMG_ZipToAvalara/js/view/summary/shipping-mixin': true
            },
            'Magento_Checkout/js/checkout-data': {
                'SMG_ZipToAvalara/js/checkout-data-mixin': true
            },
            'Magento_Tax/js/view/checkout/summary/tax': {
                'SMG_ZipToAvalara/js/view/checkout/summary/tax-mixin': true
            },
            'Magento_Checkout/js/model/shipping-rates-validator': {
                'SMG_ZipToAvalara/js/model/shipping-rates-validator-mixin': true
            }
        }
    }
};
