define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/checkout-data',
], function (Component, ko, $, customerData, checkoutData) {
    return Component.extend({
        hasResults: ko.observable(false),
        results: ko.observable({}),

        pdp: ko.observable({
            visible: false,
            activeTab: 'learn', // 'learn' or 'product_specs'
            mode: 'subscription', // 'plan' or 'subscription'
            product: null,
        }),

        initialize(config) {
            const self = this;

            this.customer = customerData.get('customer');

            this.loading = ko.observable(false);

            if (config.zip) {
                window.sessionStorage.setItem('lawn-zip', config.zip);
            }

            if (!window.sessionStorage.getItem('lawn-zip')) {
                window.location.href = '/quiz';
            }

            if (config.quiz_id) {
                this.loadQuizResults(config.quiz_id, config.zip);
            } else {
                this.getResults();
            }

            this.subscriptionType = ko.observable('annual');

            this.products = ko.computed(() => {
                return this.results().plan
                    ? this.results().plan.coreProducts
                    : [];
            });

            this.seasons = ko.computed(() => {
                const uniqueSeasons = self.products().reduce((items, product) => {
                    if (items.indexOf(product.season) === -1) {
                        items.push(product.season);
                    }

                    return items;
                }, []);

                const seasons = uniqueSeasons.map((season) => {
                    const products = self.products().filter((product) => {
                        return product.season === season;
                    });

                    let prodMap = {};

                    products.forEach((product) => {
                        if (prodMap[product.prodId]) {
                            prodMap[product.prodId] += 1;
                            return;
                        }

                        prodMap[product.prodId] = product.quantity;
                    });

                    const newProducts = [];

                    products.forEach(product => {
                        if (
                            !newProducts.some(prod => {
                                return prod.prodId === product.prodId
                            })
                        ) {
                            let newProd = {
                                ...product
                            };
                            newProd.quantity = prodMap[newProd.prodId]
                            newProducts.push(newProd);
                        }
                    });

                    return {
                        season,
                        products: newProducts,
                        total: products.reduce((price, product) => {
                            return price + (+product.price * +product.quantity);
                        }, 0),
                    }
                });

                return seasons.sort((a, b) => {
                    try {
                        return new Date(a.products[0].applicationStartDate) > new Date(b.products[0].applicationStartDate) ? 1 : -1;
                    } catch (error) {
                        return 0;
                    }
                });
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

            this.selectedAddOns = ko.observableArray([]);
            this.selectPlan = (data, event) => {
                if (event.target.checked) {
                    self.subscriptionType(event.target.value);
                }
            }

            // Clear shipping info if we came here from pressing the back button.
            checkoutData.setShippingAddressFromData({});
        },

        /**
         * Load the quiz results from the recommendation API.
         *
         * @param id
         * @param zip
         */
        loadQuizResults(id, zip) {
            const self = this;
            const formKey = document.querySelector('input[name=form_key]').value;

            const request = {
                key: formKey,
                id: id,
                zip: zip
            };

            this.loading(true);

            $.ajax(
                '/rest/V1/recommendations/quiz/result',
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(request),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        if (Array.isArray(data) && data[0]) {
                            data = data[0];
                        }

                        // An active or subscription with this quiz ID already exists.
                        if (data.subscription && data.subscription.status !== 'pending') {
                            window.location.href = self.customer().firstname ? '/your-plan' : '/quiz';
                            return;
                        }

                        if (data.error_message) {
                            alert('Error getting quiz data: ' + data.error_message + '. Please try again.');
                            window.location.href = '/quiz';
                        } else {
                            self.hasResults(true);
                            self.results(data);
                            window.sessionStorage.setItem('result', JSON.stringify(data));
                            window.sessionStorage.setItem('quiz-id', data.id);
                            window.sessionStorage.setItem('lawn-zip', request.zip);

                            // Check if we are using invalid zones (Hawaii, Alaska)
                            self.hasValidZone();
                        }
                    },
                    error(response) {
                        response = JSON.parse(response.responseText);

                        if (Array.isArray(response)) {
                            response = response[0];
                        }

                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    },
                    complete() {
                        self.loading(false);
                    }
                }
            )
        },

        /**
         * Check if we have an invalid zone.
         */
        hasValidZone() {
            if (['Zone 11', 'Zone 12'].indexOf(this.results().plan.zoneName) >= 0) {
                window.location.href = '/your-results';
                return false;
            }

            return true;
        },

        /**
         * Load the quiz from the session storage.
         */
        getResults() {
            const result = window.sessionStorage.getItem('result');

            if (result && JSON.parse(result)) {
                this.hasResults(true);
                this.results(JSON.parse(result));
                this.hasValidZone();
            } else {
                // Quiz not found, need to redirect.
                window.location.href = '/quiz';
            }
        },

        proceedToCheckout() {
            const subscriptionPlan = $('input[name="subscription_plan"]:checked').val();
            const formKey = document.querySelector('input[name=form_key]').value;

            if (!subscriptionPlan) {
                alert('You must select a subscription plan.');
            }

            const self = this;

            this.loading(true);

            if (!self.hasValidZone()) {
                return;
            }

            $.ajax(
                `/rest/V1/subscription/process`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({
                        key: formKey,
                        subscription_plan: subscriptionPlan,
                        data: self.results(),
                        addons: self.selectedAddOns(),
                    }),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        data = JSON.parse(data);

                        if (data.success === true) {
                            window.sessionStorage.setItem('subscription_plan', subscriptionPlan);
                            window.location.href = '/checkout/#shipping';
                        } else {
                            self.loading(false);
                            alert( 'Error creating your order ' + data.message + '. Please try again.' );
                        }
                    },
                    error(response) {
                        self.loading(false);
                        response = JSON.parse(response.responseText);

                        if (Array.isArray(response)) {
                            response = response[0];
                        }

                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    },
                },
            );
        },
        formatDate: function (_date) {
            const date = new Date(_date);

            return [
                date.getMonth() + 1, // Months are 0 based
                date.getDate(),
                date.getFullYear().toString().slice(2)
            ].join('/');
        },

        productFeatures: function (product) {
            return [
                product.miniClaim1,
                product.miniClaim2,
                product.miniClaim3,
            ].filter(x => !!x);
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
                return num;
            }
        },

        togglePDP(product, event) {
            if (this.pdp().visible) {
                if (event.target.id !== 'pdp-modal-wrapper' && !event.target.classList.contains('pdp-modal-close')) {
                    return true;
                }

                // hide
                $('body').removeClass('no-scroll');

            } else {
                // show
                $('body').addClass('no-scroll');
            }

            this.pdp({
                ...this.pdp(),
                visible: !this.pdp().visible,
                product: product
            });
        },

        setPDPTab(tab) {
            this.pdp({ ...this.pdp(), activeTab: tab })
        },
    });
});
