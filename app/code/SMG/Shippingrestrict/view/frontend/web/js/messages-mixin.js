define([
    'jquery'
], function($) {
    'use strict';
    return function(targetModule) {
        return targetModule.extend({
            onHiddenChange: function (isHidden) {
            var self = this;

            // Hide message block if needed
//            if (isHidden) {
//                setTimeout(function () { 
//                    $(self.selector).hide('blind', {}, 500);
//                }, 30000);                
//            }
            }
        });
    };

});