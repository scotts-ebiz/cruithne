define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'uiRegistry',
        'mage/translate',
        'Magento_Checkout/js/model/shipping-rate-service'
    ],function (
        $,
        _,
        Component,
        ko,
        customer,
        addressList,
        addressConverter,
        quote,
        createShippingAddress,
        selectShippingAddress,
        shippingRatesValidator,
        formPopUpState,
        shippingService,
        selectShippingMethodAction,
        rateRegistry,
        setShippingInformationAction,
        stepNavigator,
        modal,
        checkoutDataResolver,
        checkoutData,
        registry,
        $t) {
    'use strict';

    var mixin = {
    getEmail: function () {
                if(quote.guestEmail) return quote.guestEmail;
                else return window.checkoutConfig.customerData.email;
    },
    setShippingInformation: function () {
            if (this.validateShippingInformation()) {
				
                var email = this.getEmail();
				var zipCode = $("input[name='postcode']").val();
				var enable = window.checkoutConfig.opt_in_status;
				var storename = window.checkoutConfig.storename;
				var storecode = window.checkoutConfig.storecode;

                quote.billingAddress(null);
                checkoutDataResolver.resolveBillingAddress();
                setShippingInformationAction().done(
                    function () {
                       if($('#subscriptionOptIn').is(":checked") && email != null && enable == 1)
                        {
                             zaius.subscribe({list_id: storecode,email: email ,zip: zipCode ,acquisition_method: 'Magento' ,acquisition_source: storename});
                        }

                        stepNavigator.next();
                    }
                );
            }
        }
    };
    
    return function (target) { // target == Result that Magento_Ui/.../default returns.
      return target.extend(mixin); // new result that all other modules receive 
    };
});