define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    // Modal options
    var options = {
        type: 'popup',
        responsive: true,
        innerScroll: true,
        buttons: [],
        opened: function($Event) {
            $('.modal-header').remove();
        }
    };

    return Component.extend({
        initialize(config) {
            this.subscriptions = ko.observable(config.subscriptions);
        },
        displaySubscriptionModal: function() {
            $('.modal-popup--subscriptions').modal(options).modal('openModal');
        },
        closeSubscriptionModal: function() {
            $('.modal-popup--subscriptions').modal(options).modal('closeModal');
        },
        cancelSubscription: function() {
            let self = this;

            $.ajax({
                type: 'POST',
                url: window.location.origin + '/rest/V1/subscription/cancel',
                dataType: 'json',
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    var response = JSON.parse(response);
                    if( response.success === true ) {
                        self.closeSubscriptionModal();
                    } else {
                        alert( response.message );
                        self.closeSubscriptionModal();
                    }
                }
            })
        }
    });
});

