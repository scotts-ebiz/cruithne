/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/model/totals',
    'uiComponent',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote'
], function (ko, totals, Component, stepNavigator, quote) {
    'use strict';

    var quoteItemData = window.checkoutConfig.quoteItemData;

    return Component.extend({
        defaults: {
            template: 'SMG_SubscriptionCheckout/summary/cart-items'
        },
        totals: totals.totals(),
        items: ko.observable([]),
        maxCartItemsToDisplay: window.checkoutConfig.maxCartItemsToDisplay,
        cartUrl: window.checkoutConfig.cartUrl,
        quoteItemData: quoteItemData,

        /**
         * @deprecated Please use observable property (this.items())
         */
        getItems: totals.getItems(),

        /**
         * Returns cart items qty
         *
         * @returns {Number}
         */
        getItemsQty: function () {
            return parseFloat(this.totals['items_qty']);
        },

        /**
         * Returns count of cart line items
         *
         * @returns {Number}
         */
        getCartLineItemsCount: function () {
            return parseInt(totals.getItems()().length, 10);
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            // Set initial items to observable field
            this.setItems(totals.getItems()());
            // Subscribe for items data changes and refresh items in view
            totals.getItems().subscribe(function (items) {
                this.setItems(items);
            }.bind(this));
        },

        /**
         * Return product SKU
         *
         * @param {Object} quoteItem
         * @returns {String}
         */
        getProductSku: function(quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.product.sku
        },

        /**
         * Return product short description
         *
         * @param {Object} quoteItem
         * @returns {String}
         */
        getProductShortDescription: function(quoteItem) {
            var item = this.getItem(quoteItem.item_id);
            return item.product.short_description
        },

        /**
         * Get product data from cart by product id
         *
         * @param {Number} item_id
         * @returns {Object}
         */
        getItem: function(item_id) {
            var itemElement = null;
            _.each(this.quoteItemData, function(element, index) {
                if (element.item_id == item_id) {
                    itemElement = element;
                }
            });
            return itemElement;
        },

        /**
         * Set items to observable field
         *
         * @param {Object} items
         */
        setItems: function (items) {
            if (items && items.length > 0) {
                items = items.slice(parseInt(-this.maxCartItemsToDisplay, 10));
            }
            this.items(items);
        },

        /**
         * Returns bool value for items block state (expanded or not)
         *
         * @returns {*|Boolean}
         */
        isItemsBlockExpanded: function () {
            return true;//quote.isVirtual() || stepNavigator.isProcessed('shipping');
        }
    });
});
