define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        hasResults: ko.observable(false),
        quiz: ko.observable(null),
        results: ko.observable({}),

        initialize(config) {
            if (!config.quiz_id) {
                this.loadQuizResponses();
            } else {
                this.getCompletedQuiz(config.quiz_id);
            }
        },

		proceedToCheckout() {
			var subscriptionPlan = $('input[name="subscription_plan"]').val();
			var addonProducts = $('input[name="addon_products"]:checked').map(function() { return this.value }).get();
			if( ! subscriptionPlan ) {
				alert('You must select a subscription plan.');
			}

			const self = this;

			$.ajax(
                `/rest/V1/recommendations/quiz/process`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify( { subscription_plan: subscriptionPlan, data: self.results(), addons: addonProducts } ),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        var data = JSON.parse(data);
                        if( data.success === true ) {
                        	window.location.href = '/checkout';
                        } else {
                            alert( 'Error creating your order ' + data.message + '. Please try again.' );
                        }
                    },
                },
            );
        },

        getCompletedQuiz(id) {
            const self = this;

            $.ajax(
                `/rest/V1/recommendations/quiz/result`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ id: id }),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        if (data.error_message) {
                            alert( 'Error getting quiz data: ' + data.error_message + '. Please try again.');
                        } else {
                            if (Array.isArray(data)) {
                                data = data[0];
                            }

                            self.hasResults(true);
                            self.results(data);
                            console.log(data);
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
            const quizData = JSON.parse(quiz);

            if (quiz && JSON.parse(quiz)) {
                this.quiz(JSON.parse(quiz));
                this.getCompletedQuiz(quizData.id);
            } else {
                // Quiz not found, need to redirect.
                window.location.href = '/your-plan/quiz';
            }
        },
    });
});
