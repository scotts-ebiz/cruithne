define([
    'catalogAddToCart',
    'jquery'
], function(catalogAddToCart, $) {

    $(document).ready(function () {

        // Sets option id or product base id
        var currentDrupalProductId = '';
        var currentSize = '';
        var currentSku = '';

        var $qty = $('#qty');
        var $product_addtocart_form = $('#product_addtocart_form');

        var setOptionValues = function() {
            var childId = $('input[name="selected_configurable_option"]').val();
            if (!childId) {
                childId = $('input[name="product"]').val();
                currentSku = JSON.parse($product_addtocart_form.attr('data-skus-by-id'))[childId];
                currentDrupalProductId = JSON.parse($product_addtocart_form.attr('data-drupal-ids-by-id'))[childId];
            } else {
                currentSku = JSON.parse($product_addtocart_form.attr('data-skus-by-id'))[childId];
                currentDrupalProductId = JSON.parse($product_addtocart_form.attr('data-drupal-ids-by-id'))[childId];
                // Grab selected option text then lowercase and strip space to match css class created from tag field on Drupal.
                currentSize = $('select.super-attribute-select option:selected').text().replace(' ', '').toLowerCase();
            }
        };

        // Initial
        setOptionValues();

        // On change of dropdown reset values, send post message
        $(document).on('change', 'select.super-attribute-select', function () {
            setOptionValues();

            // Notify parent page that we have changed the product size.
            window.parent.postMessage(
                {
                    event: 'sizeChange',
                    data: {
                        size: currentSize,
                        magentoSku: currentSku,
                        drupalSku: currentDrupalProductId
                    }
                }, "*");
        });
        // Check for onSuccess of add to cart and set pixel
        $(document).on('dataLayerUpdate', function (event, gtmDataLayer, actions, add) {
            if (actions && add && ((actions.add && actions.add.length) || (actions.update && actions.update.length))) {
                window.parent.postMessage({event: 'productAdded', dataLayer: JSON.stringify(add)}, '*');

                actions.add.forEach(function(item) {
                    var quantity = item.quantity;
                    var currentDrupalProductId = item.drupalproductid;
                    //Do not track Magento products that do not have a drupalId.
                    if (!currentDrupalProductId) {
                        return;
                    }

                    // BazaarVoice track add to cart.
                    window.bvCallback = function (BV) {
                        BV.pixel.trackConversion({
                            "type": "AddToCart",
                            "label": "AddToCart_SKU",
                            "value": currentDrupalProductId,
                            "items": [
                                {"sku": currentDrupalProductId, "quantity": quantity}
                            ]
                        });
                    };
                });
            } else {
                window.parent.postMessage({event: 'cartUpdated', dataLayer: false}, '*');
            }
        });

        // Quantity buttons
        $('#qty-wrap button').click(function (t) {
            $(this).hasClass("minus") ?
                $qty.val() > 1 && $qty.val(parseInt($qty.val()) - 1) :
                $(this).hasClass("plus") && $qty.val() < 99 && $qty.val(parseInt($qty.val()) + 1)
        });

        $qty.on('input', function () {
            var quantity = this.value;

            if (quantity < 1) {
                $qty.val(1);
            }
            else if (quantity > 99) {
                $qty.val(99);
            }
        });

        $.mage.catalogAddToCart();
    });

});