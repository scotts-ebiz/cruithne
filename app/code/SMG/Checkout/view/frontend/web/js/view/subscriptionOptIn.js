define([
    'jquery',
    'ko',
    'uiComponent'
     ], function ($, ko, Component) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'SMG_Checkout/subscriptionOptIn'
            },
            visibleOptinBlock: function () {
                //put your condition here. I assumed this as your condition
                var enable = window.checkoutConfig.opt_in_status;
                var opt_in_disclaimer = window.checkoutConfig.opt_in_disclaimer;
                //if enable return false to not to show block else return true to display        
                if(enable == 1){

                 jQuery( "#shipping-method-buttons-container" ).after(function() {
                    return "<div class='optin-disclaimer'><p>*"+opt_in_disclaimer+"</p></div>";
                 });

                 return true;
                }else{
                 return false;
                }
            },
			getHeadline: function () {
                return window.checkoutConfig.opt_in_headline;
            }
        });
    }
);