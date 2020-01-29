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

        saveAndSendModal: ko.observable({
            visible: false
        }),

        saveAndSendSuccessModal: ko.observable({
            visible: false
        }),

        initialize(config) {
            const self = this;

            if (config.zip) {
                window.sessionStorage.setItem('lawn-zip', config.zip);
            } else {
                config.zip = window.sessionStorage.getItem('lawn-zip');
            }

            if (!config.zip) {
                window.location.href = '/quiz';
            }

            if (!config.quiz_id) {
                config.quiz_id = window.sessionStorage.getItem('quiz-id');
            }

            if (!config.quiz_id) {
                self.loadQuizResponses();
            } else {
                self.loadQuizResults(config.quiz_id, config.zip);
            }

            const lawnArea = window.sessionStorage.getItem('lawn-area');
            if (lawnArea) {
                self.lawnArea(lawnArea);
            }

            const lawnType = window.sessionStorage.getItem('lawn-type');
            if (lawnType) {
                self.lawnType(lawnType);
            }

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

                const seasons = uniqueSeasons.map((season) => {
                    const products = self.products().filter((product) => {
                        return product.season === season;
                    });

                    return {
                        season: season,
                        products: self.products().filter((product) => {
                            return product.season === season;
                        }),
                        total: products.reduce((price, product) => {
                            return price + (+product.price * +product.quantity);
                        }, 0),
                    }
                });

                return seasons;
            });

            // used to indicate which product is up next for delivery
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
                            if (minTimePassed) {
                                self.isLoading(false);
                            }
                            self.hasResults(true);
                            self.results(data);
                            window.sessionStorage.setItem('result', JSON.stringify(data));
                            window.sessionStorage.setItem('quiz-id', data.id);
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

        /**
         * Load the quiz from the session storage.
         */
        loadQuizResponses() {
            const quiz = window.sessionStorage.getItem('quiz');

            if (quiz && JSON.parse(quiz)) {
                this.quiz(JSON.parse(quiz));
                this.completeQuiz();
            } else {
                // Quiz not found, need to redirect.
                console.log('loading quiz responses');
                window.location.href = '/quiz';
            }
        },

        /**
         * Load the results from you quiz answers.
         */
        completeQuiz() {
            const self = this;
            let minTimePassed = false;
            let formKey = document.querySelector('input[name=form_key]').value;
            let quiz = self.quiz();
            quiz["key"] = formKey;
            quiz["lawnType"] = window.sessionStorage.getItem('lawn-type');
            quiz["lawnSize"] = window.sessionStorage.getItem('lawn-area');

            // Make sure loading screen appears for at least 3 seconds.
            setTimeout(() => {
                minTimePassed = true;
                if (self.hasResults()) {
                    self.isLoading(false);
                }
            }, 3000);

            $.ajax(
                `/rest/V1/recommendations/quiz/save`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(self.quiz()),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        if (data.error_message) {
                            alert('Error getting quiz data: ' + data.error_message + '. Please try again.');
                        } else {
                            if (Array.isArray(data)) {
                                data = data[0];
                            }

                            // Initialize the quiz with the template data.
                            if (minTimePassed) {
                                self.isLoading(false);
                            }
                            self.hasResults(true);
                            self.results(data);
                            window.sessionStorage.setItem('result', JSON.stringify(data));
                            window.sessionStorage.setItem('quiz-id', data.id);
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

        getSeasonIcon: function (product) {
            let icon = ''
            switch (product.season) {
                case 'Early Summer Feeding': icon = 'icon-summer.svg'; break;
                case 'Early Spring Feeding': icon = 'icon-early-spring.svg'; break;
                case 'Late Spring Feeding': icon = 'icon-late-spring.svg'; break;
                case 'Early Fall Feeding': icon = 'icon-fall.svg'; break;
            }

            return 'https://test_magento_image_repo.storage.googleapis.com/' + icon
        },

        togglePDP: function (product) {
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

        preventDefault: function () {
        },

        toggleSaveAndSendModal: function() {
            if( this.saveAndSendModal().visible) {
                $('body').removeClass('no-scroll');
            } else {
                $('body').addClass('no-scroll')
            }

            this.saveAndSendModal({
                ...this.saveAndSendModal(),
                visible: !this.saveAndSendModal().visible
            })
        },

        toggleSaveAndSendSuccessModal: function() {
            if( this.saveAndSendSuccessModal().visible) {
                $('body').removeClass('no-scroll');
            } else {
                $('body').addClass('no-scroll')
            }

            this.saveAndSendSuccessModal({
                ...this.saveAndSendSuccessModal(),
                visible: !this.saveAndSendSuccessModal().visible
            })
        }
    });
});

