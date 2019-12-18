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
            console.log(config);
            this.subscriptions = ko.observable(config.subscriptions);
            this.invoices = ko.observable(config.invoices);
            let self = this;

            $(document).on('click', '.cancel-subscription-modal', function(e) {
                e.preventDefault();
                $('.modal-popup--subscriptions').modal(options).modal('openModal');
            });

            $(document).on('click', '.close-subscription-modal', function(e) {
                e.preventDefault();
                $('.modal-popup--subscriptions').modal(options).modal('closeModal');
            });

            $(document).on('click', 'button#cancelSubscription', function() {
                self.cancelSubscription();
            });
        },
        cancelSubscription: function() {
            $.ajax({
                type: 'POST',
                url: window.location.origin + '/rest/V1/subscription/cancel',
                dataType: 'json',
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    var response = JSON.parse(response);
                    if( response.success === true ) {
                        $('.modal-popup--subscriptions').modal('closeModal');
                    } else {
                        alert( response.message );
                        $('.modal-popup--subscriptions').modal('closeModal');
                    }
                }
            })
        }
    });
});

