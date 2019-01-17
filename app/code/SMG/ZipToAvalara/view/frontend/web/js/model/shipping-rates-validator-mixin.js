/**
 * Copyright © 2019 SMG, LLC. All rights reserved.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Checkout/js/model/postcode-validator',
    'uiRegistry',
    'mage/cookies'
], function ($, $t, postcodeValidator, uiRegistry) {
    'use strict';

    return function (target) {
        target.postcodeValidation = function () {
            var countryId = $('select[name="country_id"]').val(),
                postcodeElement = uiRegistry.get('block-summary.block-shipping.address-fieldsets.postcode'),
                validationResult,
                warnMessage;

            if (postcodeElement === undefined) {
                postcodeElement = uiRegistry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.postcode');
            }

            if (postcodeElement == null || postcodeElement.value() == null) {
                return true;
            }

            postcodeElement.warn(null);
            validationResult = postcodeValidator.validate(postcodeElement.value(), countryId);

            if (!validationResult) {
                warnMessage = $t('Provided Zip/Postal Code seems to be invalid.');

                if (postcodeValidator.validatedPostCodeExample.length) {
                    warnMessage += $t(' Example: ') + postcodeValidator.validatedPostCodeExample.join('; ') + '. ';
                }
                warnMessage += $t('If you believe it is the right one you can ignore this notice.');
                postcodeElement.warn(warnMessage);
            }

            $.mage.cookies.set('estimated-tax', false);

            return validationResult;
        };

        return target;
    };
});
