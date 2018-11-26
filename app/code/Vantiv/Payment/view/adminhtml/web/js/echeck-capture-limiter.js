/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm'
], function ($, $t, confirm) {
    'use strict';
    return function (config, element) {
        var msg,
            original_submit = element.submit.bind(element),
            redirect = function () {
                $.mage.redirect(config.cancel_url);
            };
        element.submit = function () {
            if (config.sale_attempts_count > config.max_attempts) {
                msg = $t('Maximum attempts count is reached. You can not create invoice anymore!');
            } else {
                msg = $t('Attempt number ') + config.sale_attempts_count + $t(' of ') + config.max_attempts;
            }
            if (config.sale_attempts_count >= 1) {
                confirm(
                    {
                        title: $t('eCheck payment capture.'),
                        content: msg,
                        actions: {
                            confirm: function () {
                                if (config.sale_attempts_count > config.max_attempts) {
                                    redirect();
                                } else {
                                    original_submit();
                                }
                            },
                            cancel: function () {
                                redirect();
                            }
                        }
                    }
                );
            } else {
                original_submit();
            }
        };
    };
});
