define([
    'uiComponent',
    'ko',
    'jquery',
], function (Component, ko, $) {
    return Component.extend({
        quizTemplate: {
            id: ko.observable(''),
            questionGroups: ko.observableArray([]),
            zipCodesOptionMappings: ko.observableArray([]),
        },
        questionGroup: ko.observable(null),
        questions: ko.observable({}),
        options: ko.observableArray([]),
        results: ko.observable({}),
        previousGroups: ko.observableArray([]),


        initialize: function () {
            this._super();

            this.loadTemplate();
            this.questionGroup.subscribe(function (newValue) {
                try {
                    this.options(newValue.questions[0].options);
                } catch (error) {
                    this.options([]);
                }
            }.bind(this));
        },

        /**
         * Load the quiz template data.
         */
        loadTemplate: function () {
            var self = this;

            $.ajax(
                '/quiz/template/template',
                {
                    dataType: 'json',
                    method: 'get',
                    success: function (data) {
                        this.quizTemplate.id(data.id);
                        this.quizTemplate.questionGroups(data.questionGroups);
                        this.quizTemplate.zipCodesOptionMappings(data.zipCodesOptionMappings);
                        this.initializeQuiz();
                    }.bind(self),
                },
            );
        },

        /**
         * Get the first question group and start the quiz.
         */
        initializeQuiz: function () {
            // Pull the first question group from the template.
            this.setQuestionGroup(this.quizTemplate.questionGroups()[0]);
        },

        /**
         * Set the current question group.
         *
         * @param questionGroup
         * @param previous
         */
        setQuestionGroup: function (questionGroup, previous) {
            if (this.questionGroup() && !previous) {
                this.previousGroups.push(this.questionGroup());
            }

            var questions = {};

            // Set the question group
            this.questionGroup(questionGroup);

            // Create an ID keyed object of the questions for this group.
            questionGroup.questions.forEach(function (question) {
                questions[question.id] = question;
            });

            this.questions(questions);
        },

        /**
         * Go back to the previous question.
         */
        previousQuestionGroup() {
            if (this.previousGroups().length) {
                this.setQuestionGroup(this.previousGroups.pop(), true);
            }
        },

        /**
         * Validate the responses and move to the appropriate question.
         */
        validateResponse: function () {
            // Get the transitions for the current group.
            var transitions = this.questionGroup().transitions;

            if (transitions.length === 1) {
                // There is only one transition, so pull that questionGroup.
                var id = transitions[0].destinationQuestionGroupId;
                var questionGroup = this.findQuestionGroup(id);
                console.log(questionGroup);

                this.setQuestionGroup(questionGroup);
            }

        },

        /**
         * Find the question group with the given ID.
         *
         * @param id
         */
        findQuestionGroup: function (id) {
            console.log(id);
            for (var i = 0; i < this.quizTemplate.questionGroups().length; i++) {
                if (this.quizTemplate.questionGroups()[i].id === id) {
                    return this.quizTemplate.questionGroups()[i];
                }
            }
        },
    })
});
