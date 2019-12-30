define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/view/customer',
    'jquery',
], function (Component, ko, customer, $) {

    return Component.extend({
        initialize(config) {
            this.hasSubscriptions = ko.observable(config.hasSubscriptions);
            this.isLoggedIn = ko.observable(config.isLoggedIn);
        },
    });
});