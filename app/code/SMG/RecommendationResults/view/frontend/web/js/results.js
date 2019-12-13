define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        hasResults: ko.observable(false),
        lawnArea: ko.observable(0),
        category: ko.observable('learn'),
        showPDP: ko.observable(false),
        lawnType: ko.observable(null),
        isLoading: ko.observable(true),
        quiz: ko.observable(null),
        results: ko.observable({}),
        activeProductIndex: ko.observable(0), // for the tab menu

        initialize(config) {
            const self = this

            if (config.zip) {
                window.sessionStorage.setItem('lawn-zip', config.zip);
            }

            if (!window.sessionStorage.getItem('lawn-zip')) {
                window.location.href = '/quiz';
            }

            if (!config.quiz_id) {
                self.loadQuizResponses();
            } else {
                self.getCompletedQuiz(config.quiz_id);
            }

            const lawnArea = window.sessionStorage.getItem('lawn-area')
            if (lawnArea) {
                self.lawnArea(lawnArea)
            }

            const lawnType = window.sessionStorage.getItem('lawn-type')
            if (lawnType) {
                self.lawnType(lawnType)
            }

            self.products = ko.computed(function () {
                return self.results().plan
                    ? self.results().plan.coreProducts
                    : []
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

            self.activeProduct = ko.computed(function () {
                return self.products()[self.activeProductIndex()]
            });
        },

        getCompletedQuiz(id) {
            const self = this;
            let minTimePassed = false;
            let formKey = document.querySelector('input[name=form_key]').value;

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
                    data: JSON.stringify({ key: formKey, id: id }),
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

        togglePDP: function () {
            if (this.showPDP()) {
                // hide
                $('body').removeClass('no-scroll');

            } else {
                // show
                $('body').addClass('no-scroll');
            }

            this.showPDP(!this.showPDP());
        },

        addToOrder: function () {
            // TODO: Add the product to the order
            this.togglePDP();
        }
    });
});

