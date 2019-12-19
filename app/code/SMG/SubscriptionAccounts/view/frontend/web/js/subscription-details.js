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
            this.invoices = ko.observable(config.invoices);
            let self = this;

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

            $(document).on('click', '.trigger-modal', function (e) {
                e.preventDefault();
                cancelSubscriptionModal.openModal();
            });

            $(document).on('click', '.cancel-subscription-modal', function (e) {
                e.preventDefault();
                cancelSubscriptionModal.openModal();
            });

            $(document).on('click', '.close-subscription-modal', function (e) {
                e.preventDefault();
                cancelSubscriptionModal.closeModal();
            });

            $(document).on('click', 'button#cancelSubscription', function () {
                self.cancelSubscription();
            });
        },

        cancelSubscription: function () {
            $.ajax({
                type: 'POST',
                url: window.location.origin + '/rest/V1/subscription/cancel',
                dataType: 'json',
                contentType: 'application/json',
                processData: false,
                success: function (response) {
                    var response = JSON.parse(response);
                    if (response.success === true) {
                        cancelSubscriptionModal.closeModal();
                    } else {
                        alert(response.message);
                        cancelSubscriptionModal.closeModal();
                    }
                }
            })
        }
    });
});

