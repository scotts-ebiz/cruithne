/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*jshint browser:true jquery:true expr:true*/
define([
    "jquery"
], function ($) {
    "use strict";

    $.widget('mage.vantivPriceSubscription', {
        options: {
            priceHolderSelector: '.price-box',
            paypalShortcutSelector: '.box-tocart .actions .paypal.checkout',
            qtySelector: '.box-tocart .field.qty'
        },

        _create: function () {
            this.element.find(this.options.planElement).on('change', $.proxy(function () {
                this._reloadPrice();
            }, this));

            $(this.options.addPlanElement).on('change', $.proxy(function () {
                if ($(this.options.addPlanElement).prop('checked')) {
                    this.element.show();
                } else {
                    this.element.hide();
                    $(this.options.startDateContainerElement).hide();
                    $(this.options.paypalShortcutSelector).show();
                    $(this.options.qtySelector).show();

                    var selectedPlan = this.element.find(this.options.planElement + ':checked').first();
                    if(selectedPlan.length) {
                        selectedPlan.prop('checked', false);
                    }
                }
                this._reloadPrice();
            }, this));

            if(this.element.find(this.options.planElement + ':checked').first().length) {
                $(this.options.addPlanElement).prop('checked', true);
                this.element.show();
            }

            this._reloadPrice();
        },

        /**
         * Reload product price with selected subscription plan price
         * @private
         */
        _reloadPrice: function () {
            var basePrice, finalPrice;

            var selectedPlan = this.element.find(this.options.planElement + ':checked').first();
            if (!selectedPlan.length) {
                finalPrice = this.options.config.defaults.finalPrice;
                basePrice = this.options.config.defaults.basePrice;
            } else {
                basePrice = this.options.config.plans[selectedPlan.val()].basePrice;
                finalPrice = this.options.config.plans[selectedPlan.val()].finalPrice;

                $(this.options.startDateContainerElement).show();
                $(this.options.paypalShortcutSelector).hide();
                $(this.options.qtySelector).hide();
            }

            $(this.options.priceHolderSelector).trigger('replacePrice', {
                'prices': {
                    'finalPrice': {'amount': finalPrice},
                    'basePrice': {'amount': basePrice}
                }
            });
        }
    });

    return $.mage.vantivPriceSubscription;
});
