define([
    'uiComponent',
    'ko',
], function (Component, ko) {
    return Component.extend({
        initialize() {
            this.subscriptionID = ko.observable(window.sessionStorage.getItem('subscription_id'));

            // Clear out the subscription ID, we only needed it for this page.
            window.sessionStorage.removeItem('subscription_id');

            // There is no subscription ID, so redirect to the home page.
            if (!this.subscriptionID()) {
                window.location.href = '/';
            }
        },
    });
});
