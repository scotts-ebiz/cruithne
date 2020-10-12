define([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'uiRegistry',
    'Magento_Checkout/js/model/quote'
],
    function ($, ui, validate, translate, customerData, uiRegistry, quote) {
        "use strict";
        // Warehouses
        var oxfordPA = {
            street: '311 Reedville Rd',
            city: 'Oxford',
            state: 'Pennsylvania',
            zip: '19363',
            skus: ['8865918010', '8855918010', '8845918010']
        }

        var germantownWI = {
            street: 'W124N9899 Wasaukee Rd',
            city: 'Germantown',
            state: 'Wisconsin',
            zip: '53022',
            skus: ['8865918020', '8845918020', '8855918020']
        }

        var jacksonGA = {
            street: '2057 Highway 42 North',
            city: 'Jackson',
            state: 'Georgia',
            zip: '30233',
            skus: ['8865918030', '8845918030', '8855918030']
        }

        var carrolltonKY = {
            street: '252 Ladder Lane',
            city: 'Carrolltown',
            state: 'Kentucky',
            zip: '41008',
            skus: ['8865918040', '8845918040', '8855918040']
        }

        var orrvilleOH = {
            street: '1220 Schrock Rd',
            city: 'Orrville',
            state: 'Ohio',
            zip: '44667',
            skus: ['8865918050', '8845918050', '8855918050']
        }

        var warehouses = [
            oxfordPA,
            germantownWI,
            jacksonGA,
            carrolltonKY,
            orrvilleOH
        ];

        function getWarehouseAddress() {
            let cart = customerData.get('cart')();
            if (cart && cart.items) {
                let sku = cart.items[0].product_sku;
                warehouses.forEach(function (warehouse) {
                    warehouse.skus.forEach(function (warehouseSku) {
                        if (sku === warehouseSku) {
                            // If the sku in the cart matches a sku for a warehouse we set the shipping values and lock down the inputs
                            // Read only so that the values still get submitted
                            $('#shipping-new-address-form input[name="street[0]"]').val(warehouse.street).prop('readonly', true).css('background', '#eaeaea').change();
                            $('#shipping-new-address-form input[name="street[1]"]').prop('readonly', true);
                            $('#shipping-new-address-form div[name="shippingAddress.street.1"]').prop('hidden', true);
                            $('#shipping-new-address-form input[name="city"]').val(warehouse.city).prop('readonly', true).css('background', '#eaeaea').change();
                            // We disable all state values but the correct choice, again so the value submits properly
                            let stateId = $('option[data-title="' + warehouse.state + '"]').attr('value');
                            $('#shipping-new-address-form select[name="region_id"] option').not('option[data-title="' + warehouse.state + '"]').prop('disabled', true);
                            $('#shipping-new-address-form select[name="region_id"]').val(stateId).css('background', '#eaeaea').change();
                            $('#shipping-new-address-form input[name="postcode"]').val(warehouse.zip).prop('readonly', true).css('background', '#eaeaea').change();

                            // Hide/Change shipping related elements
                            $('.step-title').text('Pickup Address');
                            $('.opc-progress-bar-item._active span').html('Pickup Address');
                        }
                    })
                });
            }
        }

        $(function () {

            var shippingFormLoaded = setInterval(function () {
                if ($('select[name="region_id"] option').length
                    && $('#shipping-new-address-form input[name="street[0]"]').length
                    && $('#shipping-new-address-form input[name="city"]').length
                    && $('#shipping-new-address-form input[name="postcode"]').length
                ) {
                    clearInterval(shippingFormLoaded);
                    getWarehouseAddress();
                }
            }, 100);

            if (window.location.hash === '#payment') {
                quote.billingAddress(null);
            }

            $(window).on('hashchange', function () {
                if (window.location.hash) {
                    var newHash = window.location.hash;
                    if (newHash === '#payment') {
                        quote.billingAddress(null);
                        var billingFormLoaded = setInterval(function () {
                            var billingForm = $('.checkout-validate-address .instructions.noError a.edit-address');
                            if (billingForm.length) {
                                $('.checkout-validate-address .instructions.noError').html('').append(billingForm.attr('href', '#shipping').text('Edit your info.'));
                                clearInterval(billingFormLoaded);
                            }
                        }, 100);
                    }
                }
            });

            setTimeout(function () {
                $("input[name='postcode']").attr('pattern', '[0-9]*');
                $("input[name='postcode']").attr('inputmode', 'numeric');
                $("input[name='telephone']").attr('pattern', '[0-9]*');
                $("input[name='telephone']").attr('inputmode', 'numeric');

                var count = 0;
                $('.form-shipping-address input:visible').keyup(function () {
                    var str = $(this).val();
                    var nval = str.replace(/[&\/\\#,+$~%*?<>{}@!^]/g, '');
                    $(this).val(nval);
                });
                $(".form-shipping-address .street input").keyup(function () {
                    var str = $(this).val();
                    var nval = str.replace(/  +/g, ' ');
                    $(this).val(nval);
                });

                /*------- Shipping Page - Enable "Next" btn if fields are not empty  --------*/
                if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
                    $("select[name='region_id']").click();
                    $('#shipping-method-buttons-container button').prop('disabled', false);
                }

                /*------- Shipping Page - Disable "Next" btn if fields are empty  --------*/
                $(".checkout-shipping-address input:visible, select[name='region_id']").on('keyup change', function (e) {
                    $('#shipping-method-buttons-container button').prop('disabled', true);
                    if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
                        $('#shipping-method-buttons-container button').prop('disabled', false);
                    }
                });

            }, 7000);
        });

        /*------- Sticky Header --------*/
        $(window).scroll(function () {
            var sticky = $('.custom-checkout-btn-wrap');

            if (typeof sticky != "undefined" && sticky) {

                if ($(window).scrollTop() >= 200) {
                    $('.custom-checkout-btn-wrap').addClass('stickyCart');
                    $('.custom-checkout-btn-wrap').removeClass('slide-up');
                }
                else {
                    $('.custom-checkout-btn-wrap').removeClass('stickyCart');
                    $('.custom-checkout-btn-wrap').addClass('slide-up');
                }

            }
        });

        /*------- Discount code - toggle --------*/
        $('#discount-code-title').click(function () {
            $('#block-discount .content').toggleClass('disc_active');
        });
    });
