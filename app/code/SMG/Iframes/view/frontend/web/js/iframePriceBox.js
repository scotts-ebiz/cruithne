define([
    'jquery',
    'priceBox'
], function($) {

    $.widget('mage.iframePriceBox', {
        options: {
            id: "",
            jsonConfig: {}
        },

        _create: function () {

            var dataPriceBoxSelector = '[data-role=priceBox]',
                dataProductIdSelector = '[data-product-id=' + id +']',
                priceBoxes = $(dataPriceBoxSelector + dataProductIdSelector);

            priceBoxes = priceBoxes.filter(function(index, elem){
                return !$(elem).find('.price-from').length;
            });

            priceBoxes.priceBox({'priceConfig': this.options.jsonConfig});
        }
    });

    return $.mage.iframePriceBox;
});
