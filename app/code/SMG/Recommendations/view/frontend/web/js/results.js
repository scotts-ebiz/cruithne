define([
    'uiComponent',
    'ko',
    'jquery',
    'domReady!',
], function (Component, ko, $) {


    return Component.extend({
        initialize: function () {
            this._super();
            this.loadResults();
        },

        /**
         * Load the quiz template data.
         */
        loadResults: function () {
           var self = this;

            $.ajax(
                '/your-plan/template/results',
                {
                    dataType: 'json',
                    method: 'post',
                    success: function (data) {
                         if(data.error_message) {
                            alert( 'Error getting quiz data: ' + data.error_message + '. Please try again.');
                        } else {
                            // Save quiz results in local storage
                            localStorage.setItem('quiz_results', JSON.stringify(data));
                            console.log(localStorage.getItem('quiz_results'));
                        }
                    }.bind(self),
                },
            );
        },
    })
});
