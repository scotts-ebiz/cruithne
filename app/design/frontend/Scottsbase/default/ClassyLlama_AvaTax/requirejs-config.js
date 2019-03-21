
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
            'Magento_Checkout/js/view/payment/default': {
                'ClassyLlama_AvaTax/js/view/payment/default-mixin': true
            }
        }
    }
};


