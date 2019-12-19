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
            // this.subscriptions = ko.observable(config.subscriptions);
            this.subscriptions = ko.observable({
                "is_annual": true,
                "subscription_type": "Annual",
                "main_subscription": {
                    "invoice_number": 1260,
                    "starts_at": "Dec 16, 2019",
                    "ends_at": "Dec 16, 2020",
                    "next_billing_date": "December 16, 2020",
                    "cc_last_four": "1111",
                    "addon_count": 0,
                    "addon_total_amount": "0.00",
                    "main_total_amount": "166.60",
                    "total_amount": "166.60"
                },
                "active_subscription": {
                    "invoice_number": 1260
                },
                "invoices": [{
                    "invoice_number": 1260,
                    "created_at": "Dec 16, 2019",
                    "due_on": "Dec 16, 2019",
                    "paid": "YES",
                    "total": "166.60"
                }]
            });

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
                    } else {
                        alert(response.message);
                        self.closeSubscriptionModal();
                    }
                }
            })
        }
    });
});

