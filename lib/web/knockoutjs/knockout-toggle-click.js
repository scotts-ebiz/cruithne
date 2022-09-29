define(['jquery', 'knockout'], ($, ko) => {
    ko.bindingHandlers.toggleClick = {
        init:  (element, valueAccessor) => {
            var value = valueAccessor();
            ko.utils.registerEventHandler(
                element, "click", e => {
                    value(!value());
                }
            );
        }
    };
});
