define([
    'uiComponent',
    'ko',
    'jquery',
    'accordion',
], function(Component, ko, $, accordion) {
    return Component.extend({
        category: ko.observable('faq_1'),

        initialize: function (config) {
            this._super();

            this.category1Name = config.category1Name;
            this.category2Name = config.category2Name;
        },

        setupAccordion(el) {

            el.classList.remove('sp-hide');

            $('#faq_1').accordion({
                active: false,
                collapsible: true,
                multipleCollapsible: false,
                openedState: 'active',
            });

            $('#faq_2').accordion({
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
