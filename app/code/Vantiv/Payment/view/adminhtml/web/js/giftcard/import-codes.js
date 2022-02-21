/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/mage',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, mage, alert, $t) {
    'use strict';
    return function (config) {
        var importButton = $('#' + config.import_button_id);
        importButton.on('click', function (event) {
            var formData = new FormData();
            formData.append('form_key', window.FORM_KEY);
            formData.append('import_codes_file', $('#import_codes_file')[0].files[0]);
            $.ajax({
                url: config.import_url,
                type: 'post',
                data: formData,
                processData: false,
                contentType: false,
                showLoader: true,
                success: function () {
                    $($.mage.redirect(config.import_redirect_url, "reload", 0));
                },
                fail: function () {
                    alert({
                        content: $t('Gift Card codes have not been imported. Please verify file format and content.')
                    });
                    return false;
                }
            });
        });
    };
});
