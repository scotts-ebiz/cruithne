define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        initialize(config) {
            console.log(config);
            this.subscriptions = ko.observable(config.subscriptions);
            this.invoices = ko.observable(config.invoices);
        },
    });
});

