define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        hasResults: ko.observable(false),
        isLoading: ko.observable(true),
        quiz: ko.observable(null),
        results: ko.observable({}),

        initialize(config) {
            if (!config.quiz_id) {
                this.loadQuizResponses();
            } else {
                this.getCompletedQuiz(config.quiz_id);
            }
        },

        getCompletedQuiz(id) {
            const self = this;
            let minTimePassed = false;

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

                            // Initialize the quiz with the template data.
                            if (minTimePassed) {
                                self.isLoading(false);
                            }
                            self.hasResults(true);
                            self.results(data);
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
                window.location.href = '/your-plan/quiz';
            }
        },

        /**
         * Load the results from you quiz answers.
         */
        completeQuiz() {
            const self = this;
            let minTimePassed = false;

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
                            alert( 'Error getting quiz data: ' + data.error_message + '. Please try again.');
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
                        }
                    },
                },
            );
        },
    });
});
