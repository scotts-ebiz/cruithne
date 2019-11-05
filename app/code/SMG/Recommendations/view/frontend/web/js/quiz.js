define([
    'uiComponent',
    'ko',
    'jquery'
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

            self.routeLogic(group);
        };

        self.routeLogic = function (group) {

            // Sliders Logic
            if (group.questions[0].questionType === 3) {

                let sliderContainers = Array.prototype.slice.call(document.querySelectorAll(".sliderContainer"));

                for (var p=0; p < sliderContainers.length; p++) {
                    let scont = sliderContainers[p];
                    let scontId = "#" + scont.id;

                    console.log(scontId);

                    let sliderValues = {};

                    let labels = Array.prototype.slice.call(document.querySelectorAll( scontId + " span"));

                    for (var i = 0; i < labels.length; i++) {
                        sliderValues[labels[i].dataset.optid] = labels[i].innerText;
                    }

                    let slider = document.querySelector(scontId + " .slider");

                    slider.addEventListener("change", function() {
                        let closest = sliderValues[slider.value];
                        let key = parseInt(slider.value);

                        // @todo this will need updated once we are getting real images back from the payload.
                        let backgroundSrc = 'https://picsum.photos/id/' + (9 + ( 5 * slider.dataset.sliderid + key )) + '/570/280'
                        document.querySelector('#sliderImage img').setAttribute('src', backgroundSrc);

                        let labels = Array.prototype.slice.call(document.querySelectorAll(scontId + " span"));
                        for (var i = 0; i < labels.length; i++) {
                            labels[i].classList.add('hide');
                        }
                        labels[key-1].classList.remove('hide');
                    });
                }
            }

            // if (group.questions[0].questionType === 5) {
            //     console.log(group.questions[0]);
            // }
        };

        self.validateGroup = function () {
            // @todo: Validate responses
            var valid = true;

            // Store answers to local storage on validation
            if (!valid) {
                return;
            }

            // Get the transitions for the current group.
            var transitions = self.currentGroup().transitions;

            if (transitions.length === 1) {
                // There is only one transition, so pull that questionGroup.
                var id = transitions[0].destinationQuestionGroupId;
                
                // Move progress bar to next category when the next question appears on 'Next' button click
                self.loadNextGroup(self.findQuestionGroup(id));
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
                        if(data.error_message) {
                            alert( 'Error getting quiz data: ' + data.error_message + '. Please try again.');
                        } else {
                            // Initialize the quiz with the template data.
                            self.quiz.initialize(data);
                        }
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
