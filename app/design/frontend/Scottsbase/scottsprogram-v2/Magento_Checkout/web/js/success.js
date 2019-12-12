define([
    'uiComponent',
    'ko',
], function (Component, ko) {
    return Component.extend({
        orderNumber: ko.observable(''),
        orderUrl: ko.observable(''),

        initialize(config) {
            this.orderNumber(config.order_number);
            this.orderUrl(config.order_url);
        },
    });
});
