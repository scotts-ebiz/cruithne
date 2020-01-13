define([
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery',
    'domReady!'
], function (Component, ko, cancelSubscriptionModal, $) {
    var cancelSubscriptionModal;

    return Component.extend({
        initialize(config) {
            this.subscriptions = ko.observable(config.subscriptions);
            this.success = ko.observable(null);
            this.loading = ko.observable(false);

            // We need some reliable way to tell when the dom is finished
            // loading. domReady! doesn't seem to be working, so we delay
            // instantiating the modal for a second
            setTimeout(() => {
                cancelSubscriptionModal = cancelSubscriptionModal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    buttons: [],
                    opened: function ($Event) {
                        $('.modal-header').remove();
                    }
                }, $('#popup-modal'));
            }, 1000)
        },

        displaySubscriptionModal: function () {
            cancelSubscriptionModal.openModal();
        },

        closeSubscriptionModal: function () {
            cancelSubscriptionModal.closeModal();
        },

        cancelSubscription: function () {
            let self = this;
            self.loading(true);

            $.ajax({
                type: 'POST',
                url: window.location.origin + '/rest/V1/subscription/cancel',
                dataType: 'json',
                contentType: 'application/json',
                processData: false,
                success: function (response) {
                    var response = JSON.parse(response);
                    if (response.success === true) {
                        self.closeSubscriptionModal();
                        self.success('Your subscription has been canceled.');
                        window.location.reload();

                        setTimeout(() => {
                            self.success(null);
                        }, 5000);
                    } else {
                        alert(response.message);
                        self.closeSubscriptionModal();
                        self.loading(false);
                    }
                },
                error() {
                    self.loading(false);
                }
            })
        }
    });
});

