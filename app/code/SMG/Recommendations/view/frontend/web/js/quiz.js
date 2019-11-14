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

    function LawnCalculator(quiz) {
        var self = this;

        self.autocomplete = null;
        self.drawingManager = null;
        self.element = null;
        self.address = ko.observable('');
        self.showInstructions = ko.observable(false);
        self.geocoder = null;
        self.lawnSize = ko.observable(0);
        self.map = null;
        self.quiz = quiz;
        self.polygons = ko.observableArray([]);
        self.activePolygon = null;

        self.addListeners = function () {
            google.maps.event.addListener(self.drawingManager, 'polygoncomplete', self.handlePolygonComplete);
            self.map.addListener('click', function (a) {
                if (!self.drawingManager.getDrawingMode()) {
                    self.drawingManager.setDrawingMode('polygon');
                }
            });
        };

        self.initialize = function () {
            self.autocomplete = new google.maps.places.Autocomplete(
                document.getElementById('address-autocomplete'), { types: ['geocode'] }
            );
            self.autocomplete.setFields(['geometry']);
            self.autocomplete.addListener('place_changed', function () {
                var place = self.autocomplete.getPlace();

                if (!place.geometry) {
                    return;
                }

                // We have a place, enable the drawing manager.
                self.drawingManager.setMap(self.map);
                self.address($('#address-autocomplete').val().replace(', USA', ''));
                $('#address-autocomplete').val(self.address());
                self.showInstructions(true);

                if (place.geometry.viewport) {
                    self.map.fitBounds(place.geometry.viewport);
                    self.map.setZoom(22);
                } else {
                    self.map.setCenter(place.geometry.location);
                    self.map.setZoom(22);
                }
            });

            // If an address has already been set, repopulate it.
            if (self.address()) {
                $('#address-autocomplete').val(self.address());
            }

            // If a map instance already exists, add it back to the DOM.
            if (self.element) {
                $('#map').replaceWith(self.element);
                return;
            }

            self.element = document.getElementById('map');

            self.map = new google.maps.Map(document.getElementById('map'), {
                backgroundColor: '#efefefe',
                center: { lat: 37.76360215998705, lng: -94.90050332499999 },
                disableDefaultUI: true,
                mapTypeId: google.maps.MapTypeId.HYBRID,
                minZoom: 1,
                zoom: 4,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_BOTTOM
                },
            });

            self.geocoder = new google.maps.Geocoder;

            self.drawingManager = new google.maps.drawing.DrawingManager({
                // Change this to false to hide the controls, however, then the
                // ability to edit points will be lost.
                drawingControl: false,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [google.maps.drawing.OverlayType.POLYGON],
                },
                polygonOptions: {
                    fillColor: '#008B43',
                    fillOpacity: 0.7,
                    strokeWeight: 2,
                    strokeColor: '#FFF',
                    clickable: true,
                    editable: true,
                    zIndex: 1,
                },
                drawingMode: google.maps.drawing.OverlayType.POLYGON,
            });

            self.addListeners();
        };

        self.handlePolygonComplete = function (polygon) {
            var path = polygon.getPath();
            var point = path.getAt(0);

            if (path.getLength() < 3) {
                return;
            }

            self.activePolygon = polygon;
            self.drawingManager.setDrawingMode(null);

            function adjustPolygon(index) {
                var point = path.getAt(index);
                if (point) {
                    self.getLocation({ lat: point.lat(), lng: point.lng() });
                }
                self.calculateLawnSize();
            }

            // Attempt to get the zip code from a point on the polygon.
            if (point) {
                self.getLocation({ lat: point.lat(), lng: point.lng() });
            }

            // Can use the following events if we allow the user to edit.
            path.addListener('set_at', adjustPolygon);
            path.addListener('insert_at', adjustPolygon);

            // When clicking the polygon, switch to edit mode and set the
            // polygon to active.
            polygon.addListener('click', function () {
                self.activePolygon = polygon;
                self.drawingManager.setDrawingMode(null);
            });

            self.polygons.push(polygon);
            self.calculateLawnSize();
        };

        self.calculateLawnSize = function () {
            self.lawnSize(0);

            if (!self.polygons().length) {
                return;
            }

            for (polygon of self.polygons()) {
                var squareMeters = google.maps.geometry.spherical.computeArea(polygon.getPath());
                var squareFeet = Math.round(squareMeters * 3.28084);

                self.lawnSize(self.lawnSize() + squareFeet);
            }

            self.quiz.setArea(self.lawnSize());
        };

        self.getLocation = function (position) {
            if (!position) {
                position = self.map.getCenter();
            }

            self.geocoder.geocode({ 'location': position }, function (results) {
                if (results && results.length) {
                    var location = results[0];
                    for (component of location.address_components) {
                        for (types of component.types) {
                            if (types === 'postal_code') {
                                self.quiz.setZipCode(component.short_name);
                                return;
                            }
                        }
                    }
                }
            });
        };

        /**
         * Clear the selected address in the autocomplete.
         */
        self.clearAddress = function () {
            self.resetMap();
        };

        /**
         * Undo the last point placed on the map.
         */
        self.undo = function () {
            if (!self.activePolygon && !self.polygons().length) {
                return;
            }

            var shape = null;

            if (self.activePolygon) {
                shape = self.activePolygon;
                self.polygons.remove(shape);
                shape.setMap(null);
                self.activePolygon = null;
            } else {
                shape = self.polygons.pop();
                shape.setMap(null);
            }

            self.calculateLawnSize();
        };

        /**
         * Hide the helper instructions.
         */
        self.hideInstructions = function () {
            self.showInstructions(false);
        };

        self.resetMap = function () {
            // Reset the address.
            self.address('');
            $('#address-autocomplete').val('');
            self.drawingManager.setMap(null);

            // Remove the active polygons.
            self.activePolygon = null;

            // Remove any polygons from the map.
            for (polygon of self.polygons()) {
                polygon.setMap(null);
            }

            // Remove the polygons from the array.
            self.polygons.removeAll();
            self.calculateLawnSize();
        };

        self.initialize();

        // Try HTML5 geolocation.
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                self.map.setCenter(pos);
                self.map.setZoom(18);
                self.getLocation(pos);
            }, function() {
                self.getLocation();
            });
        } else {
            self.getLocation();
        }
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

        // Grab question content block for use in finding callback event
        self.questionContentBlock = document.querySelector('.sp-quiz__content');

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
        self.map = ko.observable(null);
        self.template = null;
        self.previousGroups = ko.observableArray([]);
        self.animation = ko.observable({});
        self.usingGoogleMaps = ko.observable(true);

        // Animation States for self.transitionToNextState() to iterate over
        self.animationStates = [
            () => self.contentDown(),
            () => self.transitionUp(),
            () => self.transitionDown(),
            () => self.contentUp()
        ];

        // Track current animation state
        self.currentAnimationState = 0;

        // Store id's of all running animations to cancel later on
        self.runningAnimationStates = [];

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

        /**
         * Handle moving the content screen down
         */
        self.contentDown = function () {
            $('.sp-quiz__content').removeClass('sp-quiz__content-up');
            $('.sp-quiz__content').addClass('sp-quiz__content-down');
            setTimeout(() => {
                $('.sp-quiz__content').addClass('sp-quiz__displaynone');
            }, 700);
        }

        /**
         * Handle moving the transition screen up
         */
        self.transitionUp = function () {
            $('.sp-quiz__content').addClass('sp-quiz__displaynone');
            $('.sp-quiz__transition-inner').removeClass('sp-quiz__displaynone');
            $('.sp-quiz__transition-wrapper').addClass('sp-quiz__displayblock');
            $('.sp-quiz__transition-inner').addClass('sp-quiz__transition-slideup');
        }

        /**
         * Handle moving the transition screen down
         */
        self.transitionDown = function () {
                $('.sp-quiz__transition-inner').addClass('sp-quiz__transition-slidedown');
            setTimeout(() => {
                $('.sp-quiz__transition-inner').addClass('sp-quiz__displaynone');
            }, 400);
        }

        /**
         * Handle moving the content screen up
         */
        self.contentUp = function () {
            $('.sp-quiz__transition-inner').removeClass('sp-quiz__transition-slidedown');
            $('.sp-quiz__transition-inner').removeClass('sp-quiz__displaynone');
            $('.sp-quiz__content').removeClass('sp-quiz__displaynone');
            $('.sp-quiz__content').addClass('sp-quiz__content-up');
            $('.sp-quiz__transition-wrapper').removeClass('sp-quiz__displayblock');
        };

        self.transitionToNextState = function() {
            self.runningAnimationStates.push(window.requestAnimationFrame(() => {
                self.animationStates[self.currentAnimationState]();
            }));
        };

        /**
         * Clear animationFrame's stack of previously run animationStates
         */
        self.resetAnimationState = function () {
            self.currentAnimationState = 0;

            self.runningAnimationStates.forEach(animationEventID => {
                window.cancelAnimationFrame(+animationEventID);
            });
        }

        /**
         * Step function to run through the animation states
         */
        self.step = function (start, currentAnimationState) {
            return function (timestamp) {
                if (!start) start = timestamp;
                let progress = timestamp - start;

                if (self.currentAnimationState < 5) {
                    setTimeout(() => {
                        window.requestAnimationFrame(() => {
                            self.animationStates[currentAnimationState]();
                        });
                    }, 1000);
                }
            }
        }


        self.loadNextGroup = async group => {
            // No group specified so load the first group.
            if (!group) {
                self.setGroup(self.template.questionGroups[0]);

                return;
            }

            // Get the transition.
            const animations = self.currentGroup().animationScreens;
            self.animation({});
            if (animations.length === 1) {
                self.animation(animations[0]);
            } else {
                // Find the animation based on the answer.
                for (const animation of animations) {
                    for (const condition of animation.conditions) {
                        const value = self.getQuestionAnswer(condition.questionId, true);

                        if (condition.values.includes(value)) {
                            self.animation(animation);
                            break;
                        }
                    }
                }
            }

            // If there isn't a transition screen, don't animate it.
            if (!self.animation().title) {
                self.animationStates = [
                    () => self.contentDown(),
                    () => self.contentUp()
                ];
            } else {
                // Reset animation states to default values
                self.animationStates = [
                    () => self.contentDown(),
                    () => self.transitionUp(),
                    () => self.transitionDown(),
                    () => self.contentUp()
                ];
            }

            /**
             * Set an interval for the animation states.
             */
            let animInterval = setInterval(() => {
                self.currentAnimationState++;
                let start = null;

                if (self.currentAnimationState === 5) {
                    self.currentAnimationState = 0;
                    clearInterval(animInterval);
                } else if (self.currentAnimationState === 3) {
                    self.previousGroups.push(self.currentGroup());
                    self.setGroup(group);

                    window.requestAnimationFrame(self.step(start, self.currentAnimationState));
                } else {
                    window.requestAnimationFrame(self.step(start, self.currentAnimationState));
                }
            }, 2000);

            self.transitionToNextState();
        };



        self.loadPreviousGroup = function () {
            if (!self.previousGroups().length) {
                return;
            }

            // Remove the grass type selection if set.
            if (self.questions().length && self.questions()[0].questionType === 7 && self.currentGroup().label === 'LAWN DETAILS') {
                for (question of self.questions()) {
                    self.removeAnswer(question.id);
                }
            }

            self.setGroup(self.previousGroups.pop());
        };

        self.setGroup = function (group) {
            self.currentGroup(group);

            var results = {};
            var initializedMap = false;

            for (question of group.questions) {
                // Check if the questions are sliders and set a base response.
                if (+question.questionType === 3) {
                    self.addAnswerIfEmpty(question.id, question.options[0], 1);
                }

                // Check if the questions are for the google maps entry and initialize the map.
                if (!initializedMap && self.usingGoogleMaps() && question.questionType === 5) {
                    self.initializeMap();
                    initializedMap = true;
                }
            }

            self.routeLogic(group);
        };

        self.initializeMap = function () {
            if (self.map()) {
                self.map().initialize();
            } else {
                self.map(new LawnCalculator(self));
            }
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
            self.setArea(0);
            self.setZipCode('');

            if (self.usingGoogleMaps()) {
                self.initializeMap();
                self.map().resetMap();
            }
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
            var zip = '';
            if (!event) {
                zip = data;
            } else {
                zip = event.target.value;
            }

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
            var area = 0;
            if (typeof data === 'number') {
                area = data;
            } else {
                area = parseInt(event.target.value);
            }

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
