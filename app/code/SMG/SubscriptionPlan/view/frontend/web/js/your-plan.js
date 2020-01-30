define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        hasResults: ko.observable(false),
        lawnArea: ko.observable(0),
        lawnType: ko.observable(null),
        isLoading: ko.observable(true),
        quiz: ko.observable(null),
        results: ko.observable({}),
        activeSeasonIndex: ko.observable(0), // for the tab menu

        pdp: ko.observable({
            visible: false,
            activeTab: 'learn', // 'learn' or 'product_specs'
            mode: 'plan', // 'plan' or 'subscription'
            product: null,
        }),

        initialize(config) {
            const self = this;

            self.subscription = ko.observable(config.subscription);
            self.customerFirstName = ko.observable(config.customer_first_name);

            self.loadQuizResults(self.subscription().quiz_id, self.subscription().lawn_zip);

            self.products = ko.computed(function () {
                return self.results().plan
                    ? self.results().plan.coreProducts
                    : []
            });

            self.seasons = ko.computed(() => {
                const uniqueSeasons = self.products().reduce((items, product) => {
                    if (items.indexOf(product.season) === -1) {
                        items.push(product.season);
                    }

                    return items;
                }, []);

                const seasons = uniqueSeasons.map(season => {
                    const products = self.products().filter(product => {
                        return product.season === season;
                    });

                    let prodMap = {};

                    products.forEach(product => {
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
                            newProd.quantity = prodMap[newProd.prodId];
                            newProducts.push(newProd);
                        }
                    });


                    return {
                        season,
                        products: newProducts,
                        total: products.reduce((price, product) => {
                            return price + +product.price * +product.quantity;
                        }, 0)
                    };
                });

                return seasons;
            });

            // Used to indicate which product is up next for delivery
            self.nextAvailableProduct = ko.computed(function () {
                const currentDate = new Date();
                const products = self.products().slice();

                let nextProductIndex = -1;
                products.forEach(function (product, index) {
                    if (new Date(product.applicationStartDate) < currentDate)
                        nextProductIndex = index
                });

                return (nextProductIndex + 1) % products.length
            });

            self.activeSeason = ko.computed(function () {
                return self.seasons()[self.activeSeasonIndex()]
            });

        },

        toggleAccordion(index) {
            const accordionTabs = Array.from(document.querySelectorAll('.accordion > li'));
            const isActive = accordionTabs[index].classList.contains('active');

            isActive ? accordionTabs[index].classList.remove('active') : accordionTabs[index].classList.add('active');

            accordionTabs[index].scrollIntoView({behavior: "smooth"});
        },

        loadQuizResults(id, zip) {
            const self = this;
            let minTimePassed = false;
            let formKey = document.querySelector('input[name=form_key]').value;

            const request = {
                key: formKey,
                id: id,
                zip: zip
            };

            // Make sure loading screen appears for at least 3 seconds.
            setTimeout(() => {
                minTimePassed = true;
                if (self.hasResults()) {
                    self.isLoading(false);
                }
            }, 3000);

            $.ajax(
                `/rest/V1/recommendations/quiz/result`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(request),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        if (data.error_message) {
                            alert('Error getting quiz data: ' + data.error_message + '. Please try again.');
                            window.location.href = '/quiz';
                        } else {
                            if (Array.isArray(data)) {
                                data = data[0];
                            }

                            // Initialize the quiz with the template data.
                            self.hasResults(true);
                            self.results(data);
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
                },
            );
        },

        formatDate: function (_date) {
            const date = new Date(_date);

            return [
                date.getUTCMonth() + 1, // Months are 0 based
                date.getUTCDate(),
                date.getUTCFullYear().toString().slice(2)
            ].join('/')
        },

        productFeatures: function (product) {
            return [
                { image: product.miniClaim1, text: product.miniClaim1Description },
                { image: product.miniClaim2, text: product.miniClaim2Description },
                { image: product.miniClaim3, text: product.miniClaim3Description },
            ].filter(x => !!x.image)
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

        getSeasonIcon(product) {
            let icon = '';

            switch (product.season) {
                // Summer
                case 'Early Summer':
                case 'Early Summer Seeding':
                case 'Early Summer Feeding':
                case 'Late Summer':
                case 'Late Summer Feeding':
                    icon = 'icon-summer.svg';
                    break;

                // Spring
                case 'Early Spring':
                case 'Early Spring Feeding':
                    icon = 'icon-early-spring.svg';
                    break;

                case 'Late Spring':
                case 'Late Spring Feeding':
                case 'Late Spring Seeding':
                case 'Late Spring Grub':
                    icon = 'icon-late-spring.svg';
                    break;

                // Fall
                case 'Early Fall':
                case 'Early Fall Seeding':
                case 'Early Fall Feeding':
                case 'Late Fall':
                case 'Late Fall Feeding':
                    icon = 'icon-fall.svg';
                    break;
            }

            return 'https://test_magento_image_repo.storage.googleapis.com/' + icon
        },

        togglePDP(product) {
            if (this.pdp().visible) {
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

        setPDPTab: function (tab) {
            this.pdp({ ...this.pdp(), activeTab: tab })
        },

        formatNumber: function(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    });
});

