/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert'
],
    function ($, alert) {
        'use strict';

        const MANUAL_DEFAULT_FORMAT_SIZE = 198;

        $(document).on('click', '#p_method_vantiv_keypadpayment', function () {
            // KeyPad alert
            alert({
                title: $.mage.__('Warning!'),
                content: $.mage.__('Please do not leave this page until you have completed your keypad entry and submitted your order.'),
                actions: {
                    always: function () { }
                }
            });

            // initialize the error to not display
            $('#keypad-error').css('display', 'none');

            // initialize the input fields
            $('#keypadAccountNumber').val('');
            $('#keypadExpMonth').val('');
            $('#keypadExpYear').val('');

            // initialize vantiv values
            $('#payment-vantiv-keypadpayment-ecdata').val('');
            $("#payment-vantiv-keypadpayment-last-four").val('');
            $("#payment-vantiv-keypadpayment-exp-month").val('');
            $("#payment-vantiv-keypadpayment-exp-year").val('');
            $("#payment-vantiv-keypadpayment-serial-number").val('');

            var keyPadInput = [];
            var keyPadOutputType = '';
            var keypadInputValue = '';
            var n;

            // start spinner
            jQuery('#edit_form').trigger('processStart');

            // gather the keypad data from the keypress event
            $(document).keypress(function (event) {
                // get the keycode
                var keyCode = event.keyCode || event.which;

                // get the value
                var keyValue = String.fromCharCode(keyCode);

                // add the value to the string
                keyPadInput.push(keyValue);

                // check to see what type of keypad output we have received
                // if this is the start of original or enhanced format then
                // we should see 02 as the first two values
                // if (keyPressCount == 2)
                if (keyPadInput.length === 2) {
                    // get the first two from the string
                    var firsttwo = keyPadInput.toString().replace(/,/g, '');
                    if (firsttwo === '02') {
                        keyPadOutputType = 1;
                        $('#payment-vantiv-keypadpayment-ecdata-type').val('default');

                    }
                    else if (firsttwo === '<D') {
                        keyPadOutputType = 2;
                        $('#payment-vantiv-keypadpayment-ecdata-type').val('xml');
                    }
                    else {
                        // stop spinner
                        jQuery('#edit_form').trigger('processStop');

                        $('#keypad-error').css('display', 'block');

                        event.preventDefault();
                    }
                }

                // determine if we have made it to the end
                switch (keyPadOutputType) {
                    // Default Output
                    case 1:
                        // determine if we are at the end
                        if (keyPadInput.length === MANUAL_DEFAULT_FORMAT_SIZE) {
                            // get the keypad output as a string with no commas
                            keypadInputValue = keyPadInput.toString().replace(/,/g, '');

                            // parse the data format for displaying purposes
                            parseDefaultFormat(keypadInputValue);

                            // stop spinner
                            jQuery('#edit_form').trigger('processStop');

                            // reset these values for correct processing
                            keyPadInput = [];
                            keyPadOutputType = '';
                            keypadInputValue = '';

                            // this stops the keypress event
                            event.preventDefault();
                        }
                        break;

                    // XML Output
                    case 2:
                        // get the keypad output as a string with no commas
                        keypadInputValue = keyPadInput.toString().replace(/,/g, '');

                        // determine if we are at the end
                        if (keypadInputValue.indexOf('</DvcMsg>') > -1) {
                            // parse the data format for displaying purposes
                            parseXmlFormat(keypadInputValue);

                            // stop spinner
                            jQuery('#edit_form').trigger('processStop');

                            // reset these values for correct processing
                            keyPadInput = [];
                            keyPadOutputType = '';
                            keypadInputValue = '';

                            // this stops the keypress event
                            event.preventDefault();
                        }
                        break;
                }
            });
        });

        // This function is used to parse the Default format
        // from the secure keypad output
        function parseDefaultFormat(data) {
            // get the account number length
            var accountNumberLength = Number.parseInt(data.substring(12, 14), 16);

            // get the card data section of the keypad
            var cardData = data.substring(20, 20 + accountNumberLength);

            // get the account number
            var accountNumber = cardData.substring(1, cardData.indexOf("="));

            // get the last4 digits
            var last4 = accountNumber.substring(accountNumber.length - 4);

            // get the portion of the cardData that is for the expiration date
            var expDataStart = cardData.indexOf("=") + 1;

            // get the expiration year
            var expYear = cardData.substring(expDataStart, expDataStart + 2);

            // get the expiration month
            var expMonth = cardData.substring(expDataStart + 2, expDataStart + 4);

            // get the null hash index
            var nullHashIndex = data.indexOf("0000000000000000000000000000000000000000");

            // get the serial number
            var serialNumber = data.substring(nullHashIndex + 60, nullHashIndex + 80);

            // set the input fields for displaying to the admin user
            $('#keypadAccountNumber').val(accountNumber);
            $('#keypadExpMonth').val(expMonth);
            $('#keypadExpYear').val(expYear);

            // set vantiv values
            $('#payment-vantiv-keypadpayment-ecdata').val(data);
            $("#payment-vantiv-keypadpayment-last-four").val(last4);
            $("#payment-vantiv-keypadpayment-exp-month").val(expMonth);
            $("#payment-vantiv-keypadpayment-exp-year").val(expYear);
            $("#payment-vantiv-keypadpayment-serial-number").val(serialNumber);
        };

        // This function is used to parse the XML format
        // from the secure keypad output
        function parseXmlFormat(data) {
            // find the XML tag DVC to determine if manual or swipe
            var dvc = $(data).find('Dvc');
            var entry = $(dvc).attr('Entry');

            // make sure that the user didn't swipe the card
            if (entry === 'SWIPE') {
                // set the error to display as we shouldn't be doing a swipe of the card
                $('#keypad-error').css('display', 'block');
            }
            else {
                // find the XML tag Card
                var card = $(data).find('Card');

                // get the account number
                var accountNumber = $(card).attr('MskPAN');

                // get the last4 digits
                var last4 = accountNumber.substring(accountNumber.length - 4);

                // get the expiration date
                var expDate = $(card).attr('Exp');

                // get the expiration year
                var expYear = expDate.substring(0, 2);

                // get the expiration month
                var expMonth = expDate.substring(2, 4);

                // get the encrypted card data
                var ecData = $(card).attr('ECData');

                // get the serial number
                var serialNumber = $(card).attr('CDataKSN');

                // initialize the error to not display
                $('#keypad-error').css('display', 'none');

                // set the input fields for displaying to the admin user
                $('#keypadAccountNumber').val(accountNumber);
                $('#keypadExpMonth').val(expMonth);
                $('#keypadExpYear').val(expYear);

                // set vantiv values
                $('#payment-vantiv-keypadpayment-ecdata').val(ecData);
                $("#payment-vantiv-keypadpayment-last-four").val(last4);
                $("#payment-vantiv-keypadpayment-exp-month").val(expMonth);
                $("#payment-vantiv-keypadpayment-exp-year").val(expYear);
                $("#payment-vantiv-keypadpayment-serial-number").val(serialNumber);
            }
        };
    }
);