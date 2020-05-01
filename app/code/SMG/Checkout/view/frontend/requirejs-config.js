var config = {
    map: {
        '*': {
            ajaxQty: 'SMG_Checkout/js/cartQtyUpdate',
            'Magento_Checkout/js/model/checkout-data-resolver': 'SMG_Checkout/js/model/checkout-data-resolver'
        }
    },
    config: {
            mixins: {
                'Magento_Checkout/js/view/shipping': {
                    'SMG_Checkout/js/view/shipping-mixin': true
                }
            }
        }
};