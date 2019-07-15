define([
    'jquery'
], function($) {
	'use strict';

    return function (config, element) {
        $(element).on("click", function (e) {
            var name = $(element).attr("id");
            if (name === 'qtyminus') {
                var qty_el = document.getElementById('super_group[' + config.id + ']');
                var qty = qty_el.value;

                if (qty == 2) {
                    jQuery('.box_down' + config.id).css({
                        'visibility': 'hidden'
                    });
                }

                if (!isNaN(qty) && qty > 0) {
                    qty_el.value--;
                }
            }
            else if (name === 'qtyplus') {
                var qty_el = document.getElementById('super_group[' + config.id + ']');
                var qty = qty_el.value;

                if (!isNaN(qty)) {
                    qty_el.value++;
                }

                jQuery('.box_down' + config.id).css({
                    'visibility': 'visible'
                });
            }
        });
    };
});