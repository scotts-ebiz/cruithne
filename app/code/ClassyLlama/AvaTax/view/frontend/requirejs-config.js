/**
 * ClassyLlama_AvaTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright  Copyright (c) 2016 Avalara, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

var config = {
    map: {
        '*': {
            "Magento_Checkout/js/model/shipping-save-processor/gift-registry": 'ClassyLlama_AvaTax/js/model/shipping-save-processor/gift-registry',
            "Magento_Tax/template/checkout/cart/totals/tax": 'ClassyLlama_AvaTax/template/checkout/cart/totals/tax',
            "Magento_Tax/template/checkout/summary/tax": 'ClassyLlama_AvaTax/template/checkout/summary/tax',
            // Add the following alias to provide compatibility with Magento 2.2
            addressValidation: 'ClassyLlama_AvaTax/js/addressValidation'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/shipping-save-processor/default': {
                'ClassyLlama_AvaTax/js/model/shipping-save-processor/default': true
            }
        }
    }
};
