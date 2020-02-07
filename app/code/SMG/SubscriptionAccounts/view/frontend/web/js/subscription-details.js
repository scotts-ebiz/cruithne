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
            this.loading = ko.observable(false);
            this.type = ko.computed(() => {
                return this.subscriptions().subscription.subscription_type;
            });

            this.latestInvoice = ko.computed(() => {
                return Array.isArray(this.subscriptions().invoices) && this.subscriptions().invoices[0];
            });

            // Calculate the taxes of the next order.
            this.taxes = ko.computed(() => {
                const order = this.subscriptions().nextOrder;
                const addon = this.subscriptions().addonOrder;
                let taxes = 0;

                if (order) {
                    taxes += +order.order.tax_amount;
                }


                if (this.subscriptions().initialOrder && addon) {
                    taxes += +addon.order.tax_amount;
                }

                return taxes.toFixed(2);
            });

            // Calculate the grand total of the next order.
            this.total = ko.computed(() => {
                const order = this.subscriptions().nextOrder;
                const addon = this.subscriptions().addonOrder;
                let total = 0;

                if (order) {
                    total += +order.order.grand_total;
                }

                if (this.subscriptions().initialOrder && addon) {
                    total += +addon.order.grand_total;
                }

                return total.toFixed(2);
            });

            // We need some reliable way to tell when the dom is finished
            // loading. domReady! doesn't seem to be working, so we delay
            // instantiating the modal for a second
            setTimeout(() => {
                cancelSubscriptionModal = cancelSubscriptionModal({
                    type: 'popup',
                    innerScroll: false,
                    buttons: [],
                    focus: 'none',
                }, $('#popup-modal'));
            }, 1000)
        },

        capitalizeString(string) {
            return string[0].toUpperCase() + string.slice(1).toLowerCase();
        },

        displaySubscriptionModal: function () {
            cancelSubscriptionModal.openModal();
            this.toggleModalContent('before');
        },

        closeSubscriptionModal: function () {
            cancelSubscriptionModal.closeModal();
        },

        toggleModalContent: function(section) {
            $('#popup-modal > div').css('display', 'none');
            $('#popup-modal #' + section).css('display', 'block');
        },

        closeCancellation: function() {
            this.closeSubscriptionModal();
            window.location.reload();
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
                        self.toggleModalContent('success');                        
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

