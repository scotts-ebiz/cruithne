define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        hasResults: ko.observable(false),
        results: ko.observable({}),

        activeProduct: ko.observable(null),
        pdp: ko.observable({
            visible: false,
            activeTab: 'learn', // 'learn' or 'product_specs'
            mode: 'subscription', // 'plan' or 'subscription'
        }),

        initialize(config) {
            const self = this;

            if (config.zip) {
                window.sessionStorage.setItem('lawn-zip', config.zip);
            }

            if (!window.sessionStorage.getItem('lawn-zip')) {
                window.location.href = '/quiz';
            }

            if (config.quiz_id) {
                this.loadQuizResults(config.quiz_id);
            } else {
                this.getResults();
            }

            this.subscriptionType = ko.observable('annual');

            this.products = ko.computed(() => {
                return this.results().plan
                    ? this.results().plan.coreProducts
                    : [];
            });

            this.total = ko.computed(() => {
                return this.products().reduce((sum, product) => {
                    return sum + (+product.price * +product.quantity);
                }, 0);
            });

            this.addOn = ko.computed(() => {
                return this.results().plan
                    ? this.results().plan.addOnProducts && this.results().plan.addOnProducts[0]
                    : null;
            });

            this.selectPlan = (data, event) => {
                if (event.target.checked) {
                    self.subscriptionType(event.target.value);
                }
            }
        },

        /**
         * Load the quiz results from the recommendation API.
         *
         * @param id
         */
        loadQuizResults(id) {
            const self = this;
            const formKey = document.querySelector('input[name=form_key]').value;

            $.ajax(
                '/rest/V1/recommendations/quiz/result',
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ key: formKey, id: id }),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        if (Array.isArray(data) && data[0]) {
                            data = data[0];
                        }

                        if (data.error_message) {
                            alert('Error getting quiz data: ' + data.error_message + '. Please try again.');
                            window.location.href = '/quiz';
                        } else {
                            self.hasResults(true);
                            self.results(data);
                            window.sessionStorage.setItem('result', JSON.stringify(data));
                            window.sessionStorage.setItem('quiz-id', data.id);
                        }
                    },
                }
            )
        },

        /**
         * Load the quiz from the session storage.
         */
        getResults() {
            const result = window.sessionStorage.getItem('result');

            if (result && JSON.parse(result)) {
                this.hasResults(true);
                this.results(JSON.parse(result));
            } else {
                // Quiz not found, need to redirect.
                window.location.href = '/quiz';
            }
        },

        proceedToCheckout() {
            const subscriptionPlan = $('input[name="subscription_plan"]:checked').val();
            const addonProducts = $('input[name="addon_products"]:checked').map(function () { return this.value }).get();
            const formKey = document.querySelector('input[name=form_key]').value;

            if (!subscriptionPlan) {
                alert('You must select a subscription plan.');
            }

            const self = this;

            $.ajax(
                `/rest/V1/subscription/process`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({
                        key: formKey,
                        subscription_plan: subscriptionPlan,
                        data: self.results(),
                        addons: addonProducts,
                    }),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        data = JSON.parse(data);

                        if (data.success === true) {
                            window.sessionStorage.setItem('subscription_plan', subscriptionPlan);
                            window.location.href = '/checkout/#shipping';
                        } else {
                            alert('Error creating your order ' + data.message + '. Please try again.');
                        }
                    },
                },
            );
        },
        formatDate: function (_date) {
            const date = new Date(_date)

            return [
                date.getMonth() + 1, // Months are 0 based
                date.getDate(),
                date.getFullYear().toString().slice(2)
            ].join('/')
        },

        productFeatures: function (product) {
            return [
                product.miniClaim1,
                product.miniClaim2,
                product.miniClaim3,
            ].filter(x => !!x)
        },

        formatCurrency: function (num) {
            try {
                const format = Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 2
                });

                return format.format(num);
            } catch (e) {
                return num
            }
        },

        togglePDP: function (product) {
            if (this.pdp().visible) {
                // hide
                $('body').removeClass('no-scroll');

            } else {
                // show
                $('body').addClass('no-scroll');
            }

            this.activeProduct(product);
            this.pdp({
                ...this.pdp(),
                visible: !this.pdp().visible
            });
        },

        setPDPTab: function (tab) {
            this.pdp({ ...this.pdp(), activeTab: tab })
        },

        addToOrder: function () {
            // TODO: Add the product to the order
            this.togglePDP();
        },

        preventDefault: function () {
        }
    });
});
