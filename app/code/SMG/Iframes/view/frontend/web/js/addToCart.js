requirejs(['catalogAddToCart'], function(catalogAddToCart) {
    (function ($) {
        $(document).ready(function () {
            $.mage.catalogAddToCart();
        });
    })(jQuery);
});