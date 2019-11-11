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
        self = Object.assign(self, questionGroup);
        self.questions = ko.observableArray(questionGroup.questions);
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

        self.answers = ko.observableArray([]);
        self.complete = ko.observable(false);
        self.currentGroup = ko.observable(null);
        self.template = null;
        self.previousGroups = ko.observableArray([]);
        self.usingGoogleMaps = ko.observable(true);

        self.questions = ko.computed(function () {
            return self.currentGroup() ? self.currentGroup().questions : [];
        });

        /**
         * This looks at the current answers to determine if the current
         * question group is valid.
         *
         * @type {computedObservable|*}
         */
        self.isCurrentGroupValid = ko.computed(function () {
            if (!self.currentGroup()) {
                return false;
            }

            // Loop through the questions of the group to see if each one is
            // valid.
            for (question of self.questions()) {
                if (!self.validateQuestion(question)) {
                    return false;
                }
            }

            return true;
        });

        self.initialize = function (data) {
            self.template = new QuizTemplate(data);
            self.loadNextGroup();
        };

        self.loadNextGroup = function (group) {
            // No group specified so load the first group.
            if (!group) {
                self.setGroup(self.template.questionGroups[0]);

                return;
            }

            self.previousGroups.push(self.currentGroup());

            self.setGroup(group);
        };

        self.loadPreviousGroup = function () {
            // When hitting previous from manual entry, pull google maps back up.
            if (self.questions()[0] && self.questions()[0].questionType === 5 && !self.usingGoogleMaps()) {
               self.toggleGoogleMaps();
               return;
            }

            if (!self.previousGroups().length) {
                return;
            }

            // Remove the current group answers.
            for (question of self.questions()) {
                self.removeAnswer(question.id);
            }

            self.setGroup(self.previousGroups.pop());
        };

        self.setGroup = function (group) {
            self.currentGroup(group);

            var results = {};

            for (question of group.questions) {
                // Check if the questions are sliders and set a base response.
                if (+question.questionType === 3) {
                    self.addAnswerIfEmpty(question.id, question.options[0], 1);
                }
            }

            self.routeLogic(group);

            console.log(self);
        };

        self.setAnswer = function (data, event) {
            // Remove any existing answers.
            self.answers.remove(function (item) {
                // If it is a checkbox, only remove the specific option.
                if (event.target.type === 'checkbox') {
                    return item.questionId === event.target.name && item.optionId === event.target.value;
                }

                return item.questionId === event.target.name;
            });

            if (event.target.type === 'range') {
                // Get the current options.
                var options = [];

                for (question of self.questions()) {
                    if (question.id === event.target.name) {
                        options = question.options;
                        break;
                    }
                }

                // Get the option.
                for (item of options) {
                    if (+item.value === +event.target.value) {
                        self.answers.push(new QuestionResult(event.target.name, item.id, event.target.value));
                        break;
                    }
                }
            } else if (['checkbox', 'radio'].indexOf(event.target.type) === -1 || event.target.checked) {
                self.answers.push(new QuestionResult(event.target.name, event.target.value));
            }
        };

        /**
         * Add an answer if it does not already exist.
         *
         * @param questionID
         * @param option
         * @param optionalValue
         */
        self.addAnswerIfEmpty = function (questionID, option, optionalValue) {
            // If the answer exists, just return.
            for (var answer of self.answers()) {
                if (answer.questionId === questionID) {
                    return;
                }
            }

            self.answers.push(new QuestionResult(questionID, option.id, optionalValue));
        };

        /**
         * Add or replace an answer with the given question ID.
         *
         * @param questionID
         * @param optionID
         * @param optionalValue
         */
        self.addOrReplaceAnswer = function (questionID, optionID, optionalValue) {
            self.removeAnswer(questionID);

            self.answers.push(new QuestionResult(questionID, optionID, optionalValue));
        };

        /**
         * Remove an answer with the given question ID.
         *
         * @param questionID
         */
        self.removeAnswer = function (questionID) {
            self.answers.remove(function (item) {
                return item.questionId === questionID;
            });
        };

        /**
         * Get the optional value or option ID for the given question ID.
         *
         * @param questionID
         * @returns {boolean|*}
         */
        self.getQuestionAnswer = function (questionID, id) {
            for (var answer of self.answers()) {
                if (answer.questionId === questionID) {
                    if (id) {
                        return answer.optionId;
                    }

                    return answer.optionalValue || answer.optionId;
                }
            }

            return false;
        };

        /**
         * Check if the given option for the question is selected.
         *
         * @param questionID
         * @param optionID
         * @returns {boolean}
         */
        self.isOptionSelected = function (questionID, optionID) {
            for (var answer of self.answers()) {
                if (answer.questionId === questionID && answer.optionId === optionID) {
                    return true;
                }
            }

            return false;
        };

        self.routeLogic = function (group) {
            // Sliders Logic
            if (group.questions[0].questionType === 3) {
                let sliderContainers = Array.prototype.slice.call(document.querySelectorAll(".sliderContainer"));

                for (var p = 0; p < sliderContainers.length; p++) {
                    let scont = sliderContainers[p];
                    let scontId = "#" + scont.id;

                    let sliderValues = {};

                    let labels = Array.prototype.slice.call(document.querySelectorAll( scontId + " span"));

                    for (var i = 0; i < labels.length; i++) {
                        sliderValues[labels[i].dataset.optid] = labels[i].innerText;
                    }

                    let slider = document.querySelector(scontId + " input[type=range]");

                    slider.addEventListener("change", function() {
                        let closest = sliderValues[slider.value];
                        let key = parseInt(slider.value);

                        // @todo this will need updated once we are getting real images back from the payload.
                        let backgroundSrc = 'https://picsum.photos/id/' + (9 + ( 5 * slider.dataset.sliderid + key )) + '/570/280';
                        document.querySelector('#sliderImage img').setAttribute('src', backgroundSrc);

                        let labels = Array.prototype.slice.call(document.querySelectorAll(scontId + " span"));
                        for (var i = 0; i < labels.length; i++) {
                            labels[i].classList.add('sp-hide');
                        }
                        labels[key-1].classList.remove('sp-hide');
                    });
                }
            }
        };

        /**
         * Toggle Google Maps and manual entry.
         */
        self.toggleGoogleMaps = function () {
            self.usingGoogleMaps(!self.usingGoogleMaps());
        };

        /**
         * Validate the given question.
         *
         * @param question
         * @returns {boolean}
         */
        self.validateQuestion = function (question) {
            switch (question.questionType) {
                case 1:
                case 2:
                case 3:
                case 5:
                case 7:
                case 8:
                    // Validate checkbox, radio, and slider questions.
                    var questionValid = false;
                    for (answer of self.answers()) {
                        if (answer.questionId === question.id && answer.optionId) {
                            questionValid = true;
                        }
                    }

                    return questionValid;
                case 6:
                    // Validate that the area answer contains an optional value (the area).
                    var questionValid = false;
                    for (answer of self.answers()) {
                        if (answer.questionId === question.id && answer.optionalValue > 0) {
                            questionValid = true;
                        }
                    }

                    return questionValid;

            }

            return false;
        };

        self.validateGroup = function () {
            // Get the transitions for the current group.
            var transitions = self.currentGroup().transitions;

            if (transitions.length === 1) {
                // There is only one transition, so pull that questionGroup.
                var id = transitions[0].destinationQuestionGroupId;
                
                // Move progress bar to next category when the next question appears on 'Next' button click
                self.loadNextGroup(self.findQuestionGroup(id));
                return;
            }

            // Loop through the transitions and compare the values to see where we should redirect.
            for (var transition of transitions) {
                var isCorrectTransition = true;

                for (var condition of transition.conditions) {
                    if (condition.values[0] != self.getQuestionAnswer(condition.questionId, true)) {
                        isCorrectTransition = false;
                    }
                }

                if (isCorrectTransition) {
                    console.log("question answer: " + self.getQuestionAnswer(condition.questionId, true));
                    console.log("transition dest group id " + transition.destinationQuestionGroupId);
                    self.loadNextGroup(self.findQuestionGroup(transition.destinationQuestionGroupId));
                    return;
                }
            }

            // No transitions remaining so the quiz is complete.
            self.complete(true);
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

        /**
         * Get Zone
         *
         * Takes a zip code and returns an answer id for Zone.
         * @param zip
         */
        self.getZone = function (zip) {
            var zones = self.template.zipCodesOptionMappings;
            for (var i = 0; i < zones.length; i++) {
                for (prefix of zones[i].zipCodePrefixes) {
                    if ( prefix === zip.substr(0, 3) ) {
                        console.log(zones[i].optionId);
                        return zones[i].optionId;
                    }
                }
            }
        };

        /**
         * Set the zip code from the manual entry input.
         *
         * @param data
         * @param event
         */
        self.setZipCode = function (data, event) {
            var zip = event.target.value;

            self.removeAnswer(self.questions()[0].id);

            if (zip.length === 5) {
                var zoneOption = self.getZone(zip);

                if (zoneOption) {
                    self.addOrReplaceAnswer(self.questions()[0].id, zoneOption);
                }
            }
        };

        /**
         * Set the lawn area from the manual entry input.
         *
         * @param data
         * @param event
         */
        self.setArea = function (data, event) {
            var area = parseInt(event.target.value);

            if (area > 0) {
               self.addOrReplaceAnswer(self.questions()[1].id, self.questions()[1].options[0].id, area);
            }
        }
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
            this.quiz.loadPreviousGroup();
        },

        /**
         * Validate the responses and move to the appropriate question.
         */
        validateResponse: function () {
            this.quiz.validateGroup();
        },
    })
});
