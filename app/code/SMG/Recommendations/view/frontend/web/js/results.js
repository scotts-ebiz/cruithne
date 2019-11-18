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

        initialize() {
            this.loadQuizResponses();
        },

        /**
         * Load the quiz from the session storage.
         */
        loadQuizResponses() {
            const quiz = window.sessionStorage.getItem('quiz');
            if (quiz && JSON.parse(quiz)) {
                this.quiz(JSON.parse(quiz));
                this.loadResults();
            } else {
                // Quiz not found, need to redirect.
                window.location.href = '/quiz';
            }
        },

        /**
         * Load the results from you quiz answers.
         */
        loadResults() {
            const self = this;

            $.ajax(
                `/quiz/${self.quiz().id}/completeQuiz`,
                {
                    data: self.quiz(),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        if (data.error_message) {
                            alert( 'Error getting quiz data: ' + data.error_message + '. Please try again.');
                        } else {
                            // Initialize the quiz with the template data.
                            self.isLoading(false);
                            self.hasResults(true);
                            self.results(data);
                        }
                    },
                },
            );
        },
    });
});
