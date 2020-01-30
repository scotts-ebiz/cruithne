var config = {
    paths: {
        'Magento_Checkout/js/view/shipping': 'SMG_SubscriptionApi/js/view/shipping',
        'recurly': 'https://js.recurly.com/v4/recurly'
    },
    shim: {
        'recurly': {
            exports: 'recurly'
        }
    }
};
