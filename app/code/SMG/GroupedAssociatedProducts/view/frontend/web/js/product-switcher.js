/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $('#asdfasdf').click(function() {
        if ($("#asdfasdf").hasClass("active")) {
            //Do nothing
        } else {
            //Add or Remove Class for tablabel
            $('#asdfasdf').addClass('active');
        };
    });
]});
