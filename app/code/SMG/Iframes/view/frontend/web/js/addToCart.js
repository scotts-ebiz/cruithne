(function ($) {
    $(document).ready(function () {

        var magentoAddUrl = $('#cart-add').attr('data-magento-add-url');

        var childId = $('#child-products').find(":selected").val();
        if (childId) {
            $('#cart-add').attr('data-size_id', $('#child-products').find(":selected").attr('data-size'));
            $('#cart-add').attr('data-sku_id', $('#child-products').find(":selected").attr('data-sku'));
            $('#cart-add').attr('data-drupal-sku', $('#child-products').find(":selected").attr('data-drupal-sku'));
        }

        $(document).on('change', "#child-products", function () {
            var price = $('#child-products').attr('data-price');
            if (!price) {
                price = $('#child-products').find(":selected").attr('data-price');
            }

            $('#list-price').text(price);
            $('#cart-add').attr('data-size_id', $('#child-products').find(":selected").attr('data-size'));
            $('#cart-add').attr('data-sku_id', $('#child-products').find(":selected").attr('data-sku'));
            $('#cart-add').attr('data-drupal-sku', $('#child-products').find(":selected").attr('data-drupal-sku'));

            var skuSize = $('#cart-add').attr('data-size_id');
            var magentoSku = $('#cart-add').attr('data-sku_id');
            var drupalSku =$('#cart-add').attr('data-drupal-sku');

            // Notify parent page that we have changed the product size.
            window.parent.postMessage(
                {
                    event: 'sizeChange',
                    data: {
                        size: skuSize,
                        magentoSku: magentoSku,
                        drupalSku: drupalSku
                    }
                }, "*");
        });

        $('#child-products').trigger('change');
        $('#cart-add').on('click', function () {
            var productId = $('#child-products').find(":selected").val();
            if (!productId) {
                productId = $('#base-product-id').val();

            }
            var drupalId = $('#cart-add').attr('data-drupal-sku');
            var magentoSku = $('#cart-add').attr('data-sku_id');
            var quantity = $('#qty').val();
            var cartUrl = magentoAddUrl + "?product_id=" + productId + "&quantity=" + quantity;
            $.get(cartUrl, function (data) {

                //Do not track Magento products that do not have a drupalId.
                if (!drupalId) {
                    return;
                }

                // BazaarVoice track add to cart.
                window.bvCallback = function (BV) {
                    BV.pixel.trackConversion({
                        "type": "AddToCart",
                        "label": "AddToCart_SKU",
                        "value": drupalId,
                        "items" : [
                            { "sku": drupalId, "quantity": quantity }
                        ]
                    });
                };

            }).done(function (e) {
                window.parent.postMessage('productAdded', '*');
            });
        });

        // Grab the default selected sku and update the radio option state for that sku.
        $('#size-options > label > input[value=' + childId + ']').attr('checked', true);

        // Change the radio option state when clicking on the size label.
        $("#size-options label").on('click', function () {
            $('#size-options input').removeAttr('checked');
            $(this).find('input').attr('checked', true).trigger('change');
        });

        // Update the original <select> dropdown whenever the radio option changes.
        $("#size-options > label > input").change(function () {
            var productId = $(this).val();
            $("#child-products").val(productId);
            $("#child-products").trigger('change');
        });

        $("#qty-wrap button").click(function (t) {
            $(this).hasClass("minus") ?
                $("#qty").val() > 1 && $("#qty").val(parseInt($("#qty").val()) - 1) :
                $(this).hasClass("plus") && $("#qty").val() < 99 && $("#qty").val(parseInt($("#qty").val()) + 1)
        });

        $('#qty').on('input', function () {
            var quantity = this.value;

            if (quantity < 1) {
                $("#qty").val(1);
            }
            else if (quantity > 99) {
                $("#qty").val(99);
            }
        })

    });
})(jQuery);