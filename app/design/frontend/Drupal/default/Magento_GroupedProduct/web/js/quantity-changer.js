define([
    'jquery',
    'Magento_Catalog/js/price-utils'
], function($, priceUtils) {
'use strict';

    return function (config, element) {
        $(element).on("keyup", function (e) {
            var sum = 0;
            var priceFormat = {
                decimalSymbol: '.',
                groupLength: 3,
                groupSymbol: ",",
                integerRequired: false,
                pattern: "$%s",
                precision: 2,
                requiredPrecision: 2
            };
            $(".price-wrapper").each(function() {
                if(!isNaN($(this).data( "price-amount" )) && $(this).data( "price-amount" ).length!=0) {
                    var suffix = $(this).attr('id');
                    var pid = suffix.replace(/[^0-9]/g,'');
                    var qty = $("#super_group_"+pid).val(); 
                    sum = sum + (qty * parseFloat($(this).data( "price-amount" )));
                }
            });
            $(".totalgroupedprice").html(priceUtils.formatPrice(sum, priceFormat));
        });
    };
});
