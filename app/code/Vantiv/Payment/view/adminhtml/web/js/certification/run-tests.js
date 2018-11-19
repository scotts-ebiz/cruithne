/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'mage/mage',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, mage, alert, $t) {
    'use strict';
    return function (config) {
        var runButton = $('#' + config.run_button_id);
        var selectedTests = $('#payment_us_vantiv_payment_vantiv_certification_vantiv_certification_tests_ready');

        runButton.on('click', function (event) {
            $.ajax({
                url: config.run_url,
                type: 'POST',
                dataType: 'json',
                data: {'selectedTests': selectedTests.val()},
                showLoader: true
            }).success(function() {
                $($.mage.redirect(config.run_redirect_url, "reload", 0));
            }).fail(function (jqXHR, textStatus) {
                if (window.console) {
                    console.log(textStatus);
                }

                alert({
                    content: $t('Certification Tests has not been completed.')
                });

                return false;
            });
        });
    };
});
