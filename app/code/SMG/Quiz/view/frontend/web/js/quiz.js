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

        quiz: ko.observable({}),

        options: ko.observable([]),

        initialize: function () {
            this._super();

            this.loadTemplate();
            this.quiz.subscribe(function (newValue) {
                try {
                    this.options(newValue.questions[0].options);
                } catch (error) {
                    this.options([]);
                }
            }.bind(this));
        },

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

        initializeQuiz() {
            this.quiz(this.quizTemplate.questionGroups()[0]);
        },
    })
});
