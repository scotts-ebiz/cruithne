define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    'uiRegistry',
    'mage/translate',
], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressList,
    addressConverter,
    quote,
    createShippingAddress,
    selectShippingAddress,
    shippingRatesValidator,
    formPopUpState,
    shippingService,
    selectShippingMethodAction,
    rateRegistry,
    setShippingInformationAction,
    stepNavigator,
    modal,
    checkoutDataResolver,
    checkoutData,
    registry,
    $t
) {
    'use strict';

    var popUp = null;

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/shipping',
            shippingFormTemplate: 'Magento_Checkout/shipping-address/form',
            shippingMethodListTemplate: 'Magento_Checkout/shipping-address/shipping-method-list',
            shippingMethodItemTemplate: 'Magento_Checkout/shipping-address/shipping-method-item'
        },
        visible: ko.observable(!quote.isVirtual()),
        errorValidationMessage: ko.observable(false),
        isCustomerLoggedIn: customer.isLoggedIn,
        isFormPopUpVisible: formPopUpState.isVisible,
        isFormInline: addressList().length === 0,
        isNewAddressAdded: ko.observable(false),
        saveInAddressBook: 1,
        quoteIsVirtual: quote.isVirtual(),

        /**
         * @return {exports}
         */
        initialize: function () {
            var self = this,
                hasNewAddress,
                fieldsetName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset';

            this._super();
            this.loading = ko.observable(false);
            this.hasSubscription = ko.observable(false);
            this.cancellingSubscription = ko.observable(false);

            // Check if customer has active subscription
            self.checkRecurlySubscriptions();

            $(document).on( 'click', 'button#cancelSubscription', function() {
                self.cancelRecurlySubscription();
            });

            let telephoneInterval = setInterval(() => {
                if ($('input[name="telephone"]').length) {
                    const telephoneInput = $('input[name="telephone"]');
                    telephoneInput.on('input propertychange', e => {
                        telephoneInput.blur();
                        telephoneInput.focus();
                    });
                    clearInterval(telephoneInterval);
                }
            }, 250);

            if (!quote.isVirtual()) {
                stepNavigator.registerStep(
                    'shipping',
                    '',
                    $t('Shipping'),
                    this.visible, _.bind(this.navigate, this),
                    10
                );
            }
            checkoutDataResolver.resolveShippingAddress();

            hasNewAddress = addressList.some(function (address) {
                return address.getType() == 'new-customer-address'; //eslint-disable-line eqeqeq
            });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                }
            });

            quote.shippingMethod.subscribe(function () {
                self.errorValidationMessage(false);
            });

            registry.async('checkoutProvider')(function (checkoutProvider) {
                var shippingAddressData = checkoutData.getShippingAddressFromData();

                if (shippingAddressData) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                }
                checkoutProvider.on('shippingAddress', function (shippingAddrsData) {
                    checkoutData.setShippingAddressFromData(shippingAddrsData);
                });
                shippingRatesValidator.initFields(fieldsetName);
            });

            return this;
        },

        getAlreadySubscribedModalContent() {
            return `<h5 class="sp-h5 sp-text-black sp-text-center">You're Already Subscribed</h5>
                    <h6 class="sp-h6 sp-text-black sp-text-center">Looks like you already have a Scotts Program Subscription.</h6>
                    <p><strong>Start a new subscription</strong><br />
                        Did you move or have your lawn conditions changed? We can cancel your current subscription and start a new one.
                    </p>
                    <p><strong>Keep my current subscription</strong><br />
                        Don’t want to cancel? You can keep your current plan and subscription option.
                    </p>
                     <p class="sp-text-center"><strong>For questions, email us at scotts-orders@scotts.com or call us at <a href="tel:18772203091">1-877-220-3091</a>.</strong></p>
                    <div class="modal-popup-cta sp-text-center">
                        <button id="startNewSubscription" class="sp-button sp-button--primary">Start a new subscription</button>
                    </div>
                    <div class="sp-text-center sp-mb-8">
                        <a href="/your-plan">Keep my current subscription</a>
                    </div>`;
        },

        getCancellationModalContent() {
            return `
                <h5 class="sp-h5 sp-text-black sp-text-center">Confirm Your Cancellation</h5>
                <h6 class="sp-h6 sp-text-black sp-text-center">By cancelling your subscription, the following will happen:</h6>
                <div class="content">
                    <ul>
                        <li>Please see your email for your refund amount. This should appear in your account within 7 days.</li>
                        <li>You will no longer be billed</li>
                        <li>You will no longer receive product shipments</li>
                        <li>You will still have an online account with Scotts</li>
                    </ul>
                    <p class="sp-text-center"><strong>For questions, email us at scotts-orders@scotts.com or call us at <a href="tel:18772203091">1-877-220-3091</a>.</strong></p>
                    <div class="modal-popup-cta sp-text-center">
                        <button type="button" id="cancelSubscription" class="sp-button sp-button--primary">Cancel My Subscription</button>
                    </div>
                    <div class="sp-text-center sp-mb-8">
                        <a href="/your-plan">Never mind, take me back</a>
                    </div>
                </div>`;
        },

        /**
         * Check if the customer has an active Recurly Subscription
         *
         */
        checkRecurlySubscriptions: function() {
            var self = this;

            self.loading(true);

            $.ajax({
                type: 'POST',
                url: window.location.origin + '/rest/V1/subscription/check',
                dataType: 'json',
                contentType: 'application/json',
                processData: false,
                showLoader: true,
                success: function(response) {
                    var response = JSON.parse( response );

                    if( response.success === true && response.has_subscription === true ) {
                        self.hasSubscription(true);

                        // Show modal
                        $('body').append(`<section id="popup-modal" class="modal-popup--subscriptions">
                            ${self.getAlreadySubscribedModalContent()}
                            </section>`
                        );

                        var options = {
                            type: 'popup',
                            innerScroll: true,
                            focus: 'none',
                            buttons: [],
                            opened: function($Event) {
                                $('.modal-header').remove();
                            },
                            closed() {
                                if (self.hasSubscription() && !self.cancellingSubscription()) {
                                    window.location.href = '/your-plan';
                                }
                            },
                        };

                        document.getElementById('startNewSubscription').addEventListener('click', (event) => {
                            event.preventDefault();
                            $('#popup-modal').html(self.getCancellationModalContent());
                        });

                        var popup = modal(options, $('#popup-modal'));
                        $('#popup-modal').modal('openModal');



                    }
                },
                complete() {
                    self.loading(false);
                },
            });
        },

        /**
         * Cancel Recurly subscription
         *
         */
        cancelRecurlySubscription() {
            const self = this;
            self.loading(true);
            self.cancellingSubscription(true);

            $('button#cancelSubscription')
                .addClass('sp-button--loading')
                .addClass('sp-button--inactive');

            $.ajax({
                type: 'POST',
                url: window.location.origin + '/rest/V1/subscription/cancel',
                dataType: 'json',
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    response = JSON.parse(response);
                    if( response.success === true ) {
                        self.hasSubscription(false);
                        $('#popup-modal').modal('closeModal');
                    } else {
                        alert( response.message );
                        $('#popup-modal').modal('closeModal');
                    }
                },
                complete() {
                    self.loading(false);
                    self.cancellingSubscription(false);
                    window.location.reload();

                    $('button#cancelSubscription')
                        .removeClass('sp-button--loading')
                        .removeClass('sp-button--inactive');
                },
            })
        },

        /**
         * Navigator change hash handler.
         *
         * @param {Object} step - navigation step
         */
        navigate: function (step) {
            step && step.isVisible(true);
        },

        /**
         * @return {*}
         */
        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                        click: self.saveNewAddress.bind(self)
                    },
                    {
                        text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                        class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',

                        /** @inheritdoc */
                        click: this.onClosePopUp.bind(this)
                    }
                ];

                /** @inheritdoc */
                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // Store temporary address for revert action in case when user click cancel action
                    self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                };
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        },

        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            checkoutData.setShippingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },

        /**
         * Save new shipping address
         */
        saveNewAddress: function () {
            var addressData,
                newShippingAddress;

            this.source.set('params.invalid', false);
            this.triggerShippingDataValidateEvent();

            if (!this.source.get('params.invalid')) {
                addressData = this.source.get('shippingAddress');
                // if user clicked the checkbox, its value is true or false. Need to convert.
                addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;

                // New address must be selected as a shipping address
                newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressData));
                this.getPopUp().closeModal();
                this.isNewAddressAdded(true);
            }
        },

        /**
         * Shipping Method View
         */
        rates: shippingService.getShippingRates(),
        isLoading: shippingService.isLoading,
        isSelected: ko.computed(function () {
            return quote.shippingMethod() ?
                quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] :
                null;
        }),

        /**
         * @param {Object} shippingMethod
         * @return {Boolean}
         */
        selectShippingMethod: function (shippingMethod) {
            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingRate(shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']);

            return true;
        },

        /**
         * Set shipping information handler
         */
        setShippingInformation: function () {
            if (this.validateShippingInformation()) {
                quote.billingAddress(null);
                checkoutDataResolver.resolveBillingAddress();
                setShippingInformationAction().done(
                    function () {
                        stepNavigator.next();
                    }
                );
            }
        },

        /**
         * @return {Boolean}
         */
        validateShippingInformation: function () {
            var shippingAddress,
                addressData,
                loginFormSelector = 'form[data-role=email-with-possible-login]',
                emailValidationResult = customer.isLoggedIn(),
                field,
                country = registry.get(this.parentName + '.shippingAddress.shipping-address-fieldset.country_id'),
                countryIndexedOptions = country.indexedOptions,
                option = countryIndexedOptions[quote.shippingAddress().countryId],
                messageContainer = registry.get('checkout.errors').messageContainer;

            if (!quote.shippingMethod()) {
                this.errorValidationMessage(
                    $t('The shipping method is missing. Select the shipping method and try again.')
                );

                return false;
            }

            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
            }

            if (this.isFormInline) {
                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();

                if (emailValidationResult &&
                    this.source.get('params.invalid') ||
                    !quote.shippingMethod()['method_code'] ||
                    !quote.shippingMethod()['carrier_code']
                ) {
                    this.focusInvalid();

                    return false;
                }

                shippingAddress = quote.shippingAddress();
                addressData = addressConverter.formAddressDataToQuoteAddress(
                    this.source.get('shippingAddress')
                );

                //Copy form data to quote shipping address object
                for (field in addressData) {
                    if (addressData.hasOwnProperty(field) &&  //eslint-disable-line max-depth
                        shippingAddress.hasOwnProperty(field) &&
                        typeof addressData[field] != 'function' &&
                        _.isEqual(shippingAddress[field], addressData[field])
                    ) {
                        shippingAddress[field] = addressData[field];
                    } else if (typeof addressData[field] != 'function' &&
                        !_.isEqual(shippingAddress[field], addressData[field])) {
                        shippingAddress = addressData;
                        break;
                    }
                }

                if (customer.isLoggedIn()) {
                    shippingAddress['save_in_address_book'] = 1;
                }
                selectShippingAddress(shippingAddress);
            } else if (customer.isLoggedIn() &&
                option &&
                option['is_region_required'] &&
                !quote.shippingAddress().region
            ) {
                messageContainer.addErrorMessage({
                    message: $t('Please specify a regionId in shipping address.')
                });

                return false;
            }

            if (!emailValidationResult) {
                $(loginFormSelector + ' input[name=username]').focus();

                return false;
            }

            return true;
        },

        /**
         * Trigger Shipping data Validate Event.
         */
        triggerShippingDataValidateEvent: function () {
            this.source.trigger('shippingAddress.data.validate');

            if (this.source.get('shippingAddress.custom_attributes')) {
                this.source.trigger('shippingAddress.custom_attributes.data.validate');
            }
        }
    });
});
