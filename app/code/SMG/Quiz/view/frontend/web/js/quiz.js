define([
    'uiComponent',
    'ko',
    'jquery',
], function (Component, ko, $) {
    /**
     * Question Group View Model
     *
     * @param questionGroup
     * @constructor
     */
    function QuestionGroup(questionGroup) {
        var self = this;

        self.id = questionGroup.id;

        self.questions = questionGroup.questions;
        self.answers = ko.observable({});
    }

    /**
     * Question Result View Model
     *
     * @param questionId
     * @param optionId
     * @param optionalValue
     * @constructor
     */
    function QuestionResult(questionId, optionId, optionalValue) {
        var self = this;

        self.questionId = questionId;
        self.optionId = optionId;
        self.optionalValue = optionalValue;
    }

    /**
     * Quiz View Model
     *
     * @param data
     * @constructor
     */
    function Quiz(data) {
        var self = this;
        self.progressBarCategories = ko.observableArray([
            {label: "Goals"}, 
            {label: "Routine"},
            {label: "Tools"},
            {label: "Condition"},
            {label: "Lawn Details"}
        ]);
        self.template = null;
        self.previousGroups = ko.observableArray([]);
        self.currentGroup = ko.observable(null);                            
        // self.progressBar = ko.observable(null);

        self.initialize = function (data) {
            self.template = new QuizTemplate(data);
            self.loadNextGroup();
        };

        self.loadNextGroup = function (group) {
            // No group specified so load the first group.
            if (!group) {
                self.currentGroup(self.template.questionGroups[0]);
                console.log(self.template.questionGroups[0]);

                return;
            }

            console.log(group);

            self.previousGroups.push(self.currentGroup());

            self.setGroup(group);
        };

        self.loadPreviousGroup = function () {
            if (!self.previousGroups().length) {
                return;
            }

            self.setGroup(self.previousGroups.pop());
        };

        self.setGroup = function (group) {
            self.currentGroup(group);

            var results = {};

            for (question of group.questions) {
                for (option of question.options) {
                    results[option.id] = '';
                }
            }
        };

        // self.updateProgressBar = function (group) {
        //     var labelValue = $(".sp-quiz__progress-list li").get("value");
        //     function checkLabel() {
        //         for (var i = 0; i < self.progressBarCategories.length; i += 1) {
        //             if (self.labelValue === self.progressBarCategories[i]) {
        //             $(".sp-quiz__progress-list li").css("background-color", "hotpink");
        //             $(".sp-quiz__progress-list li").append(".sp-quiz__progress-fill-active");
        //         }
        //     }
        //         if (quiz.currentGroup().label.toLowerCase === progressBarCategories[i].toLowerCase) {
        //             return 
        //                 self.progressBarCategories[i].css("background-color", "hotpink");
        //             if (self.labelValue === self.quiz.currentGroup().label) {
        //             } 
        //         } else {
        //             return self.labelValue.css("background-color", "hotpink");
        //         }
        //     } 

        //     // No group specified so load the first group
        //     if (!group) {
        //         self.currentGroup(self.template.questionGroups[0]);
        //         // $(".sp-quiz__progress-label li:first").css("background-color", "hotpink");
        //         return;
        //     // } else {
        //         // checkLabel();
        //     };

        //     self.previousGroups.push(self.currentGroup());

        //     self.setGroup(group);
        
        // };

        self.validateGroup = function () {
            // @todo: Validate responses
            var valid = true;

            // Store answers to local storage on validation
            if (!valid) {
                return;
            // } else {
            //     return;
            }

            // Move the progress bar to next question
            // self.updateProgressBar();

            // Get the transitions for the current group.
            var transitions = self.currentGroup().transitions;

            if (transitions.length === 1) {
                // There is only one transition, so pull that questionGroup.
                var id = transitions[0].destinationQuestionGroupId;
                
                // Move progress bar to next category when the next question appears on 'Next' button click
                

                // Possibly unneeded: Calculate how much to fill in the progress bar
                // var num = ko.pureComputed(function() {
                //    return Math.round(
                //     Math.min(
                //         ko.unwrap(params.value), 1
                //     ) * 100) + '%';
                // });
                self.loadNextGroup(self.findQuestionGroup(id));
                // self.progressBar({
                //     num: 0,
                //     // transform: "translateX:" + " (calc(questionGroups.length / 5) * 100}" + "%);"
                //     transform: "translateX: {num}"
                // });
                // console.log('progressBar = potato')
            }
        };

        /**
         * Find the question group with the given ID.
         *
         * @param id
         */
        self.findQuestionGroup = function (id) {
            if (!id) {
                return false;
            }

            for (var i = 0; i < self.template.questionGroups.length; i += 1) {
                if (self.template.questionGroups[i].id === id) {
                    return self.template.questionGroups[i];
                }
            }

            return false;
        };
    }

    /**
     * Quiz Template View Model
     *
     * @param data
     * @constructor
     */
    function QuizTemplate(data) {
       var self = this;

       self.id = data.id;
       self.questionGroups = data.questionGroups;
       self.zipCodesOptionMappings = data.zipCodesOptionMappings;
    }

    return Component.extend({
        questionGroup: ko.observable(null),
        questions: ko.observable({}),
        answers: ko.observable({}),
        previousGroups: ko.observableArray([]),
        // quizProgress: ko.observable({}),

        initialize: function () {
            this._super();
            this.quiz = new Quiz();
            this.loadTemplate();
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
                        // Initialize the quiz with the template data.
                        self.quiz.initialize(data);
                    }.bind(self),
                },
            );
        },

        /**
         * Go back to the previous question.
         */
        previousQuestionGroup() {
            console.log('previousQuestionGroup');
            this.quiz.loadPreviousGroup();
        },

        /**
         * Validate the responses and move to the appropriate question.
         */
        validateResponse: function () {
            console.log('validateResponse');
            this.quiz.validateGroup();
        },

        setAnswer: function (data, event) {
            console.log('setAnswer');
        }
    })
});
