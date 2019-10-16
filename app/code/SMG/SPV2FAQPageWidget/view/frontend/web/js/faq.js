define([
    'uiComponent',
    'ko',
    'jquery',
    'accordion',
], function(Component, ko, $, accordion) {
    return Component.extend({
        category: ko.observable('faq_1'),

        initialize: function () {
            this._super();
        },

        setupAccordion() {
            $('#faq_1').accordion({
                active: false,
                collapsible: true,
                multipleCollapsible: false,
                openedState: 'active',
            });
        },

        setCategory(category) {
            this.category(category);
            $('#' + category).accordion({
                active: false,
                collapsible: true,
                multipleCollapsible: false,
                openedState: 'active',
            });
        }
    });
});
