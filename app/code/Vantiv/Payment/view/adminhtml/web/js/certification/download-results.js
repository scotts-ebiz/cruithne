/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'mage/mage',
    'prototype'
], function ($) {
    'use strict';
    return function (config) {
        var downloadButton = $('#' + config.download_button_id);

        downloadButton.on('click', function (event) {
            var url = config.download_url;
            if (Prototype.Browser.IE) {
                var generateLink = new Element('a', {href: url});
                $$('body')[0].insert(generateLink);
                generateLink.click();
            } else {
                $($.mage.redirect(url, "replace", 0));
            }
        });
    };
});
