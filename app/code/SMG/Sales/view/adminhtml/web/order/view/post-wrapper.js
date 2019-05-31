/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, confirm) {
    'use strict';

    /**
     * @param {String} url
     * @returns {Object}
     */
    function getForm(url) {
        return $('<form>', {
            'action': url,
            'method': 'POST'
        }).append($('<input>', {
            'name': 'form_key',
            'value': window.FORM_KEY,
            'type': 'hidden'
        }));
    }

    $('#order-view-cancel-button').click(function () {
        // get the value of the order sent
        var orderSent = $('#order-sent').html();

        // if order has been sent to SAP then inform the user
        // otherwise just make sure they want to cancel
        if (orderSent === 'Yes')
        {
            var msg = $.mage.__('The order has already been sent to SAP for fulfillment, please contact SAP Order ' +
                'team miraclegrostore@scotts.com.<br /><br />Upon cancellation of the SAP order, come back to ' +
                'Magento to complete any refunds and returns needed for the order.<br /><br />Are you sure you ' +
                'want to cancel this order?'),
                url = $('#order-view-cancel-button').data('url');
        }
        else
        {
            var msg = $.mage.__('Are you sure you want to cancel this order?'),
                url = $('#order-view-cancel-button').data('url');
        }

        confirm({
            'content': msg,
            'actions': {

                /**
                 * 'Confirm' action handler.
                 */
                confirm: function () {
                    getForm(url).appendTo('body').submit();
                }
            }
        });

        return false;
    });

    $('#order-view-hold-button').click(function () {
        var url = $('#order-view-hold-button').data('url');

        getForm(url).appendTo('body').submit();
    });

    $('#order-view-unhold-button').click(function () {
        var url = $('#order-view-unhold-button').data('url');

        getForm(url).appendTo('body').submit();
    });
});
