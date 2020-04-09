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
        self.mapInstructionsDisabled = ko.observable(true);

        self.addListeners = function () {
            google.maps.event.addListener(self.drawingManager, 'polygoncomplete', self.handlePolygonComplete);
            self.map.addListener('click', function (a) {
                if (!self.drawingManager.getDrawingMode()) {
                    self.drawingManager.setDrawingMode('polygon');
                }
            });
        };

        self.initialize = function () {
            const autocompleteElement = document.querySelector('#address-autocomplete');
            const footerBar = document.querySelector('.sp-quiz__footer');
            self.quiz.invalidZipCode(false);
            let isAndroid = navigator.userAgent.toLowerCase().indexOf('android') !== -1;

            autocompleteElement.onfocus = () => {
                if (isAndroid) {
                    footerBar.style.display = 'none';
                }
            };

            autocompleteElement.onblur = () => {
                if (isAndroid) {
                    footerBar.style.display = 'block';
                }
            };

            self.autocomplete = new google.maps.places.Autocomplete(
                autocompleteElement, { types: ['geocode'] }
            );
            google.maps.event.addDomListener(autocompleteElement, 'blur', function() {
                if ($('.pac-item:hover').length === 0 ) {
                    google.maps.event.trigger(this, 'focus', {});
                    google.maps.event.trigger(this, 'keydown', {
                        keyCode: 13
                    });
                }
            });
            self.autocomplete.setComponentRestrictions({ 'country': 'us' });
            self.autocomplete.setFields(['geometry']);
            self.autocomplete.setTypes(['address']);
            self.autocomplete.addListener('place_changed', function () {
                self.quiz.invalidZipCode(false);
                var place = self.autocomplete.getPlace();

                if (!place || !place.geometry) {
                    self.quiz.invalidZipCode(true);
                    return;
                }

                // We have a place, enable the drawing manager.
                self.drawingManager.setMap(self.map);
                self.address($('#address-autocomplete').val().replace(', USA', ''));
                $('#address-autocomplete').val(self.address());
                self.showInstructions(true);

                setTimeout(() => {
                    self.mapInstructionsDisabled(false);
                }, 5000);

                const zoom = window.outerWidth >= 1024 ? 22 : 20;

                if (place.geometry.viewport) {
                    self.map.fitBounds(place.geometry.viewport);
                    self.map.setZoom(zoom);
                } else {
                    self.map.setCenter(place.geometry.location);
                    self.map.setZoom(zoom);
                }

                self.getLocation(place.geometry.location);
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
                tilt: 0,
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

            // Not a polygon since there are less then 3 points, so remove it.
            if (path.getLength() < 3) {
                polygon.setMap(null);

                return;
            }

            self.activePolygon = polygon;
            self.drawingManager.setDrawingMode(null);

            function adjustPolygon(index) {
                var point = path.getAt(index);
                self.calculateLawnSize();
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

            for (polygon of self.polygons()) {
                var squareMeters = google.maps.geometry.spherical.computeArea(polygon.getPath());
                var squareFeet = Math.round(squareMeters * 10.76391);

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
            self.drawingManager.setDrawingMode(null);
            self.polygons().forEach(polygon => polygon.setMap(null));
            self.polygons([]);
            self.activePolygon = null;

            self.calculateLawnSize();
        };

        self.toggleInfo = function () {
            self.showInstructions(!self.showInstructions());
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
        self.optionalValue = optionalValue ? String(optionalValue) : null;
    }

    /**
     * Quiz View Model
     *
     * @param data
     * @constructor
     */
    function Quiz(data) {
        var self = this;
        const NUMBERPATTERN = /^[0-9]+$/;

        // Grab question content block for use in finding callback event
        self.questionContentBlock = document.querySelector('.sp-quiz__question-wrapper');

        self.debounce = function (func, wait, immediate = false) {
            let timeout;
            return function() {
                let context = this, args = arguments;
                let later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                }
                let callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            }

        }

        self.handleResize = function() {
            const wrapper = document.querySelector('.sp-quiz__wrapper');
            const content = document.querySelector('.sp-quiz__content');

            if (window.innerWidth < 1024) {
                const height = document.querySelector('.sp-quiz__footer').offsetHeight;
                wrapper.style.marginBottom = height + 'px';
                content.style.paddingBottom = height + 'px';
                return;
            }

            wrapper.style.marginBottom = '0px';
            content.style.marginBottom = '0px';
        };

        window.addEventListener('resize', self.debounce(self.handleResize, 250));

        let observer = new MutationObserver(function(mutations) {
            if (document.contains(document.querySelector('.sp-quiz__footer'))) {
                if (document.querySelector('.sp-quiz__footer').offsetHeight > 0) {
                    self.handleResize();
                    observer.disconnect();
                }
            }
        })

        observer.observe(document, {attributes: false, childList: true, characterData: false, subtree: true})

        self.progressBarCategories = ko.observableArray([
            {label: "Goals"},
            {label: "Routine"},
            {label: "Tools"},
            {label: "Condition"},
            {label: "Lawn Details"}
        ]);

        self.answers = ko.observableArray([]);
        self.currentGroup = ko.observable(null);
        self.map = ko.observable(null);
        self.template = null;
        self.previousGroups = ko.observableArray([]);
        self.sliderImages = ko.observable({});
        self.animation = ko.observable({});
        self.usingGoogleMaps = ko.observable(false);
        self.invalidZipCode = ko.observable(false);
        self.invalidArea = ko.observable(false);
        self.isAnimating = ko.observable(false);
        self.zipCode = '';

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
            self.isAnimating(true);
            $('.sp-quiz-option').removeClass('sp-quiz-option-animation');
            $('.sp-quiz__question-wrapper').removeClass('sp-quiz__question-up');
            $('.sp-quiz__question-wrapper').addClass('sp-quiz__question-down');
            setTimeout(() => {
                $('.sp-quiz__question-wrapper').addClass('sp-quiz__displaynone');
            }, 250);
        }

        /**
         * Handle moving the transition screen up
         */
        self.transitionUp = function () {
            $('.sp-quiz-option').removeClass('sp-quiz-option-fullopacity');
            $('.sp-quiz__question-wrapper').addClass('sp-quiz__displaynone');
            $('.sp-quiz__transition-inner').removeClass('sp-quiz__displaynone');
            $('.sp-quiz__transition-wrapper').addClass('sp-quiz__displayblock');
            $('.sp-quiz__transition-inner').addClass('sp-quiz__transition-slideup');
        },

        /**
         * Handle moving the transition screen down
         */
        self.transitionDown = function () {
                $('.sp-quiz__transition-inner').addClass('sp-quiz__transition-slidedown');
            setTimeout(() => {
                $('.sp-quiz__transition-inner').addClass('sp-quiz__displaynone');
            }, 250);
        },

        /**
         * Handle moving the content screen up
         */
        self.contentUp = function () {

            $('.sp-quiz-option').addClass('sp-quiz-option-animation');
            $('.sp-quiz__transition-inner').removeClass('sp-quiz__transition-slidedown');
            $('.sp-quiz__transition-inner').removeClass('sp-quiz__displaynone');
            $('.sp-quiz__question-wrapper').removeClass('sp-quiz__displaynone');
            $('.sp-quiz__question-wrapper').addClass('sp-quiz__question-up');
            $('.sp-quiz__transition-wrapper').removeClass('sp-quiz__displayblock');

            setTimeout(() => {
                $('.sp-quiz-option').addClass('sp-quiz-option-fullopacity');
                self.isAnimating(false);
            }, 640);
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
        };

        /**
         * Step function to run through the animation states
         */
        self.step = function (start, currentAnimationState) {
            return function () {
                if (currentAnimationState <= 5) {
                    setTimeout(() => {
                        let animationFrame = window.requestAnimationFrame(() => {
                            try {
                                self.animationStates[currentAnimationState - 1]();
                            } catch (err) {
                                window.cancelAnimationFrame(animationFrame);
                            }
                        });
                    }, 500);
                }
            }
        };

        self.loadNextGroup = async group => {
            // No group specified so load the first group.
            if (!group) {
                self.setGroup(self.template.questionGroups[0]);

                return;
            }

            self.currentAnimationState = 0;

            $('.sp-quiz-option').addClass('sp-quiz-option-fullopacity');

            // Get the transition.
            const animations = self.currentGroup().animationScreens;
            self.animation({});

                // Find the animation based on the answer.
            for (const animation of animations) {
                // No required conditions for this animation, so just use it.
                if (!animation.conditions.length || self.testConditions(animation.conditions)) {
                    self.animation(animation);
                    break;
                }
            }

            // If there isn't a transition screen, don't animate it.
            if (!self.animation().title) {
                self.animationStates = [
                    () => self.contentDown(),
                    () => self.contentUp(),
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

            self.isAnimating(true);

            self.currentAnimationState++;
            window.requestAnimationFrame(self.step(null, self.currentAnimationState));
            /**
             * Set an interval for the animation states.
             */
            let animInterval = setInterval(() => {
                self.currentAnimationState++;

                let start = null;

                if (self.currentAnimationState >= 5) {
                    self.currentAnimationState = 0;
                    clearInterval(animInterval);
                } else if (self.currentAnimationState === 4) {
                    self.previousGroups.push(self.currentGroup());
                    self.setGroup(group);

                    window.requestAnimationFrame(self.step(start, self.currentAnimationState));

                    clearInterval(animInterval);
                } else if (self.currentAnimationState == 2 && !self.animation().title) {
                    self.previousGroups.push(self.currentGroup());
                    self.setGroup(group);

                    window.requestAnimationFrame(self.step(start, self.currentAnimationState));
                    clearInterval(animInterval);
                } else {
                    window.requestAnimationFrame(self.step(start, self.currentAnimationState));
                }
            }, 1000);
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

            if(group.label === 'CONDITION') setupRangeListener(this);

            var results = {};
            var initializedMap = false;
            var sliderQuestion = false;

            window.location.hash = group.label
                ? String(group.label).replace(' ', '-').toLowerCase()
                : '';

            for (question of group.questions) {
                sliderQuestion = true;
                // Check if the questions are sliders and set a base response.
                if (+question.questionType === 3) {
                    self.addAnswerIfEmpty(question.id, question.options[2], 3);
                }

                // Check if the questions are for the google maps entry and initialize the map.
                if (!initializedMap && self.usingGoogleMaps() && question.questionType === 5) {
                    self.initializeMap();
                    initializedMap = true;
                }
            }

            // If this is the slider question, set the images.
            if (sliderQuestion) {
                for (question of group.questions) {
                    self.sliderImages(Object.assign({}, self.sliderImages(), {
                        [question.id]: self.getOptionImage(question.id),
                    }));
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
                        self.sliderImages(Object.assign({}, self.sliderImages(), {
                            [event.target.name]: self.getOptionImage(event.target.name),
                        }));
                        break;
                    }
                }
            } else if (['checkbox', 'radio'].indexOf(event.target.type) === -1 || event.target.checked) {
                self.answers.push(new QuestionResult(event.target.name, event.target.value));

                // This is the grass type question, so store the grass type.
                if (self.currentGroup().label === 'LAWN DETAILS' && event.target.dataset.label) {
                    if (!event.target.dataset.label.includes('not sure')) {
                        window.sessionStorage.setItem('lawn-type', event.target.dataset.label);
                    } else {
                        window.sessionStorage.removeItem('lawn-type');
                    }
                }
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
         * @param id
         * @returns {boolean|*}
         */
        self.getQuestionAnswer = function (questionID, id) {
            const answers = [];
            for (let answer of self.answers()) {
                if (answer.questionId === questionID && answer.optionId) {
                    if (id) {
                        answers.push(answer.optionId);
                        continue;
                    }

                    answers.push(answer.optionalValue || answer.optionId);
                }
            }

            switch (answers.length) {
                case 0:
                    return false;
                case 1:
                    return answers[0];
                default:
                    return answers;
            }
        };

        self.getOptionImage = function (questionID) {
            const question = self.currentGroup().questions.find((question) => {
                return question.id === questionID;
            });

            if (!question) {
                return '';
            }

            for (let answer of self.answers()) {
                if (answer.questionId === questionID) {
                    const option = question.options.find((option) => {
                        return option.id === answer.optionId;
                    });

                    if (option) {
                        return option.imageUrl;
                    }
                }
            }

            return '';
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
                }
            }
        };

        self.formatNumber = function(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        };

        /**
         * Toggle Google Maps and manual entry.
         */
        self.toggleGoogleMaps = function () {
            self.invalidZipCode(false);
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
            let questionValid = false;
            switch (+question.questionType) {
                case 1:
                case 2:
                case 3:
                case 5:
                case 7:
                case 8:
                    // Validate checkbox, radio, and slider questions.
                    questionValid = false;
                    for (answer of self.answers()) {
                        if (answer.questionId === question.id && answer.optionId) {
                            questionValid = true;
                        }
                    }

                    return questionValid;
                case 6:
                    // Validate that the area answer contains an optional value (the area).
                    questionValid = false;
                    for (answer of self.answers()) {
                        if (answer.questionId === question.id && answer.optionalValue > 0) {
                            questionValid = true;
                        }
                    }

                    return questionValid;
                case 9:
                    // Validate the zip code question contains an optional value (zip code).
                    questionValid = false;
                    for (answer of self.answers()) {
                        if (answer.questionId === question.id && String(answer.optionalValue).length === 5) {
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
            for (const transition of transitions) {
                // No required conditions for this transition, so just use it.
                if (!transition.conditions.length || self.testConditions(transition.conditions)) {
                    self.loadNextGroup(self.findQuestionGroup(transition.destinationQuestionGroupId));
                    return;
                }
            }

            // No transitions remaining so the quiz is complete.
            self.completeQuiz();
        };

        /**
         * Test a group of transition/animation conditions.
         *
         * @param conditions
         * @returns {boolean}
         */
        self.testConditions = function (conditions) {
            for (var condition of conditions) {
                // Condition failed, return false.
                if (! self.testCondition(condition)) {
                    return false;
                }
            }

            return true;
        };

        /**
         * Test a single condition.
         *
         * @param condition
         * @returns {boolean}
         */
        self.testCondition = function (condition) {
            let values = self.getQuestionAnswer(condition.questionId, true) || [];

            if (values && !Array.isArray(values)) {
                values = [values];
            }

            switch (condition.conditionType) {
                case 1:
                    // Make sure at least one selected option is in
                    // the condition values.
                    let passed = false;
                    for (value of values) {
                        if (condition.values.includes(value)) {
                            passed = true;
                            break;
                        }
                    }

                    // No value existed in conditions so it did not pass.
                    if (!passed) {
                        return false;
                    }

                    break;
                case 2:
                    // Make sure all condition values are selected by the user.
                    for (let conditionValue of condition.values) {
                        if (!values.includes(value)) {
                            return false;
                        }
                    }

                    break;
                case 3:
                    // Make sure the condition value include no
                    // user selected values.
                    for (value of values) {
                        if (condition.values.includes(value)) {
                            // User selected a restricted value so condition
                            // fails.
                            return false;
                        }
                    }
            }

            return true;
        };

        /**
         * Complete the quiz.
         */
        self.completeQuiz = function () {
            var quiz = {
                id: self.template.id,
                answers: self.answers(),
                zip: self.zipCode,
            };

            window.sessionStorage.setItem('quiz', JSON.stringify(quiz));

            // Redirect to plan page.
            window.location.href = '/your-results';
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
            let zip = '';
            if (!event) {
                zip = data;
            } else {
                zip = event.target.value;
                self.invalidZipCode(false);
                if (zip.length > 0 && !zip.toString().match(NUMBERPATTERN)) {
                    self.invalidZipCode(true);
                    return;
                }
            }

            const zoneQuestion = self.questions().find((question) => +question.questionType === 5);
            const zipQuestion = self.questions().find((question) => +question.questionType === 9);

            if (!zoneQuestion || !zipQuestion) {
                return;
            }

            self.removeAnswer(zoneQuestion.id);
            self.removeAnswer(zipQuestion.id);

            if (!zip || zip.length < 5) {
                return;
            }

            if (zip.length === 5 && zip.toString().match(NUMBERPATTERN)) {
                const zoneOption = self.getZone(zip);

                if (zoneOption) {
                    self.addOrReplaceAnswer(zoneQuestion.id, zoneOption);
                    self.addOrReplaceAnswer(zipQuestion.id, zipQuestion.options[0].id, zip);
                    self.zipCode = zip;
                    window.sessionStorage.setItem('lawn-zip', String(zip));
                    self.invalidZipCode(false);
                    return;
                }
            }

            self.invalidZipCode(true);
        };

        /**
         * Set the lawn area from the manual entry input.
         *
         * @param data
         * @param event
         */
        self.setArea = function (data, event) {
            let area = 0;
            self.invalidArea(false);
            if (typeof data === 'number') {
                area = data;
            } else {
                let areaInput = event.target.value;
                let areaAsString = areaInput.toString();
                if (!areaAsString.match(NUMBERPATTERN)) {
                    self.invalidArea(true);
                } else {
                    area = parseInt(areaInput);
                }
            }

            self.addOrReplaceAnswer(self.questions()[1].id, self.questions()[1].options[0].id, area);
            window.sessionStorage.setItem('lawn-area', String(area));
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

       // Remove the Alaska and Hawaii zones.
       self.zipCodesOptionMappings = data.zipCodesOptionMappings;
    }

    function setSliderTrack(el) {
        var value = el.value;
        var percentage = (value - 1) * 25;
        var parent = el.parentNode;
        var progress = parent.querySelector('.sp-slider-progress');
        progress.style.background = 'linear-gradient(to right, #1d5632 0%, #1d5632 ' + percentage + '%, transparent ' + percentage + '%, transparent 100%)';
    }

    function setupRangeListener(quiz) {
        let interval = setInterval(() => {
            if (!navigator.platform.match(/iPhone|iPod|iPad/)) {
                clearInterval(interval);
            }

            if (document.querySelectorAll('.sp-slider-container input.sp-slider').length > 0) {
                const sliders = document.querySelectorAll('.sp-slider-container input.sp-slider');

                sliders.forEach(slider => {
                    slider.addEventListener(
                        "touchend",
                        (e) => iosPolyfill(e, quiz), 
                        { passive: true }
                    );
                });

                clearInterval(interval);
            }
        }, 100);
    }

    function iosPolyfill(e, quiz) {
        let slider = e.target;
        let val =
            (e.changedTouches[0].pageX - slider.getBoundingClientRect().left) /
            (slider.getBoundingClientRect().right -
            slider.getBoundingClientRect().left),
        max = slider.getAttribute("max"),
            segment = 1 / (max - 1),
            segmentArr = [];

        max++;

        for (let i = 0; i < max; i++) {
            segmentArr.push(segment * i);
        }

        let segCopy = JSON.parse(JSON.stringify(segmentArr)),
            index = segmentArr.sort((a, b) => Math.abs(val - a) - Math.abs(val - b))[0];

        let newValue = segCopy.indexOf(index) + 1;
        slider.value = newValue;
        setSliderTrack(slider);
        quiz.setAnswer(newValue + 1, e);
    }

    return Component.extend({
        questionGroup: ko.observable(null),
        questions: ko.observable({}),
        answers: ko.observable({}),
        previousGroups: ko.observableArray([]),

        initialize: function () {
            this._super();

            // Clear out any session cache for the new quiz.
            window.sessionStorage.clear();

            this.quiz = new Quiz();
            this.loadTemplate();
        },

        /**
         * Load the quiz template data.
         */
        loadTemplate: function () {
            var self = this;
            var formKey = document.querySelector('input[name=form_key]').value;

            $.ajax(
                '/rest/V1/recommendations/quiz/new',
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ key: formKey }),
                    dataType: 'json',
                    method: 'post',
                    success: function (data) {
                        if(data.error_message) {
                            alert( 'Error getting quiz data: ' + data.error_message + '. Please try again.');
                        } else {
                            if (Array.isArray(data)) {
                                data = data[0];
                            }

                            // Initialize the quiz with the template data.
                            self.quiz.initialize(data);
                        }
                    }.bind(self),
                },
            );
        },

        initializeSlider(el) {
            setSliderTrack(el);
        },

        updateSlider(data, event) {
            setSliderTrack(event.target);
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
