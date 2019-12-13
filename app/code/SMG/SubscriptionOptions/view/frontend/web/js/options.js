define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        hasResults: ko.observable(false),
        results: ko.observable({}),

        initialize(config) {
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
			const addonProducts = $('input[name="addon_products"]:checked').map(function() { return this.value }).get();
            const formKey = document.querySelector('input[name=form_key]').value;

			if (! subscriptionPlan) {
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
                            window.sessionStorage.setItem('subscription_plan', subscriptionPlan );
                        	window.location.href = '/checkout/#shipping';
                        } else {
                            alert( 'Error creating your order ' + data.message + '. Please try again.' );
                        }
                    },
                },
            );
        },
    });
});
