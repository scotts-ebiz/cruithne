define(
    [
        'ko',
        'jquery',
        'recurly',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'domReady!',
    ],
    function (ko, $, recurly, Component, Modal) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SMG_SubscriptionApi/payment/recurly'
            },

            initialize: function () {
                this._super();
                let self = this;
                this.rscoChecked = ko.observable(false);
                this.cardInputTouched = ko.observable(false);
                this.orderProcessing = ko.observable(false);
                this.billingFormInputs = ko.observableArray([]);
                this.error = ko.observable('');
                this.refreshCheckout = ko.observable(false);

                /** Can get current value of the checkbox (checked or not) on this observable */
                this.sameBillingShippingChecked = ko.observable(true);
                this.billingInfo = ko.observable(self.getBillingAddress());
                this.subscriptionType = ko.observable(window.sessionStorage.getItem('subscription_plan'));
                this.loading = ko.observable(false);
                this.loadingMask = ko.observable(null);

                /** Can query children of the form element (the inputs) based on this observable, only when it != null */
                this.billingForm = ko.observable(null);

                /**
                 * Computed function that returns whether or not the the checkout
                 * button is disabled. Validates based on the following:
                 * - Billing info having input for all required fields
                 * - RSCO checkbox checked
                 * - Any character typed into the recurly card iframe
                 */
                this.checkoutButtonDisabled = ko.computed(function () {
                    if (!self.sameBillingShippingChecked()) {
                        /**
                         * Check and ensure that every required field on the billing info
                         * has some input before enabled the checkout button.
                         */
                        const billingInfoValid = Array.prototype.every.call(
                            Object.keys(self.billingInfo()),
                            key => {
                                const streetValue = key === 'street' ? self.billingInfo()[key][0] : '';
                                const value = key !== 'street' ? self.billingInfo()[key] : '';

                                if (key === 'street') {
                                    if (
                                        /^\s*\S+(?:\s+\S+){2}/.test(streetValue) &&
                                        streetValue !== ''
                                    ) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }

                                if (key === 'telephone') {
                                    if (
                                        value.length > 9 &&
                                        /^[(]?(\d{3})[)]?[-|\s]?(\d{3})[-|\s]?(\d{4})$/.test(value)
                                    ) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }

                                if (key === 'postcode') {
                                    if (
                                        /^[0-9]{5}$/.test(value)
                                    ) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }

                                if (
                                    key === 'firstname' ||
                                    key === 'lastname'
                                ) {
                                    if (
                                        value.length > 0 &&
                                        /^[a-zA-Z\.\-\'\sàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸåÅæÆœŒœŒçÇðÐøØ¿¡ß]*$/.test(value)
                                    ) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }

                                if (key === 'city') {
                                    if (
                                        value.length > 0 &&
                                        /^[a-zA-z ]*$/.test(value)
                                    ) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }

                                if (
                                    key === 'company' ||
                                    key === 'region'
                                ) {
                                    return true;
                                }

                                return value !== '';
                            }
                        );
                        return (
                            !billingInfoValid ||
                            !self.rscoChecked() ||
                            !self.cardInputTouched()
                        );
                    }

                    return (
                        !self.rscoChecked() ||
                        !self.cardInputTouched()
                    );
                });

                /**
                 * Set an interval to add an event listener to the rsco checkbox
                 * when the element enters the dom. Clear the interval once the
                 * click listener to blur all billing field inputs is set.
                 */
                let rscoCheckboxInterval = setInterval(() => {
                    if (document.querySelector('input[name="rsco_accept"]')) {
                        document.querySelector('input[name="rsco_accept"]').addEventListener('click', () => {
                            Array.prototype.forEach.call(self.billingFormInputs(), input => {
                                $(input).blur();
                            });
                            $('select[name="region_id"]').focusout();
                        });
                        clearInterval(rscoCheckboxInterval);
                    }
                }, 250);

                let loadingMaskInterval = setInterval(() => {
                    if (document.querySelector('.loading-mask')) {
                        self.loadingMask(document.querySelector('.loading-mask'));
                        clearInterval(loadingMaskInterval);
                    }
                }, 200);

                /**
                 * An interval to set the observable for billing form input fields.
                 * It also sets blur and focus listeners for the input fields on the
                 * billing form to ensure that validation is handled on this form.
                 */
                let billingListenerInterval = setInterval(() => {
                    if (self.billingForm() != null) {
                        const inputs = self.billingForm().querySelectorAll('input');
                        self.billingFormInputs(inputs);

                        // Handle select case for region_id
                        $('select[name="region_id"]').focusout(() => {
                            if ($('select[name="region_id"]').length && $('[name="billingAddressrecurly.region_id"] .sp-form-error').length) {
                                $('.sp-form-error-region').remove();
                            }
                            if ($('select[name="region_id"]').val() && $('[name="billingAddressrecurly.region_id"] .sp-form-error').length) {
                                $('.sp-form-error-region').remove();
                            }
                        });

                        Array.prototype.forEach.call(inputs, input => {
                            // Perform manual required validation
                            $(input).blur(() => {
                                if (!$(input).val()) {
                                    if ($(input).attr("name") === "telephone" && !$('[name="billingAddressrecurly.telephone"] .sp-form-error').length) {
                                        $('[name="billingAddressrecurly.telephone"]').append(`
                                        <div class="sp-form-error sp-form-error-telephone">
                                            <span>This is a required field.</span>
                                        </div>
                                        `);
                                    }
                                    if ($(input).attr("name") === "postcode" && !$('[name="billingAddressrecurly.postcode"] .sp-form-error').length) {
                                        $('[name="billingAddressrecurly.postcode"]').append(`
                                        <div class="sp-form-error sp-form-error-postcode">
                                            <span>This is a required field.</span>
                                        </div>
                                        `);
                                    }
                                    if ($(input).attr("name") === "city" && !$('[name="billingAddressrecurly.city"] .sp-form-error').length) {
                                        $('[name="billingAddressrecurly.city"]').append(`
                                        <div class="sp-form-error sp-form-error-city">
                                            <span>This is a required field.</span>
                                        </div>
                                        `);
                                    }
                                    if ($(input).attr("name") === "street[0]" && !$('[name="billingAddressrecurly.street.0"] .sp-form-error').length) {
                                        $('[name="billingAddressrecurly.street.0"]').append(`
                                        <div class="sp-form-error sp-form-error-street-0">
                                            <span>This is a required field.</span>
                                        </div>
                                        `);
                                    }
                                }
                            });

                            // Remove manually generated validation fields when magento validation takes over
                            $(input).focus(function () {
                                if ($(input).attr("name") === "telephone" && $('[name="billingAddressrecurly.telephone"] .sp-form-error').length) {
                                    $('.sp-form-error-telephone').remove();
                                }
                                if ($(input).attr("name") === "postcode" && $('[name="billingAddressrecurly.postcode"] .sp-form-error').length) {
                                    $('.sp-form-error-postcode').remove();
                                }
                                if ($(input).attr("name") === "region" && $('[name="billingAddressrecurly.region_id"] .sp-form-error').length) {
                                    $('.sp-form-error-region').remove();
                                }
                                if ($(input).attr("name") === "city" && $('[name="billingAddressrecurly.city"] .sp-form-error').length) {
                                    $('.sp-form-error-city').remove();
                                }
                                if ($(input).attr("name") === "street[0]" && $('[name="billingAddressrecurly.street.0"] .sp-form-error').length) {
                                    $('.sp-form-error-street-0').remove();
                                }
                            });

                            /**
                             * Listen on all input fields and set the billing info to
                             * a newly created object with the current information set
                             */
                            input.addEventListener('change', e => {
                                self.billingInfo(
                                    Object.assign(
                                        {},
                                        self.getBillingAddress(),
                                        {
                                            [e.target.name]: e.target.value
                                        }
                                    )
                                );
                            });

                            self.billingInfo(
                                Object.assign(
                                    {},
                                    self.getBillingAddress(),
                                    {
                                        [input.name]: input.value
                                    }
                                )
                            );

                        });

                        clearInterval(billingListenerInterval);
                    }
                }, 100);

                /**
                 * An interval for checking whether or not the billing form has
                 * entered the DOM. Once it has entered the dom, set a listener on
                 * the checkbox input for the shipping and billing info being the same.
                 * Update observable value accordingly.
                 */
                let billingFormInterval = setInterval(() => {
                    if (document.querySelector('input[name="billing-address-same-as-shipping"]')) {
                        if (
                            document.querySelector('.billing-address-form')
                        ) {
                            const checkbox = document.querySelector('input[name="billing-address-same-as-shipping"]')
                            self.billingForm(document.querySelector('.billing-address-form'));

                            checkbox.addEventListener('change', e => {
                                const currentVal = self.sameBillingShippingChecked();
                                self.sameBillingShippingChecked(!currentVal);
                            });

                            clearInterval(billingFormInterval);
                        }
                    }
                }, 100);

                // Setup zip modal
                this.zipModalOptions = {
                    type: 'popup',
                    innerScroll: true,
                    title: 'Your Zip Code Has Changed',
                    closeText: 'Cancel',
                    focus: 'none',
                    buttons: [{
                        text: 'Cancel',
                        class: 'sp-link sp-mx-4',
                        click() {
                            window.location.hash = 'shipping';
                            window.location.reload();
                        },
                    }, {
                        text: 'Create New Plan',
                        class: 'sp-button sp-button--primary sp-mx-4',
                        click() {
                            window.location.href = '/quiz'
                        }
                    }],
                    closed() {
                        window.location.hash = 'shipping';
                        window.location.reload();
                    },
                };

                // Setup zip modal
                this.errorModalOptions = ko.computed(() => {
                    return {
                        type: 'popup',
                        innerScroll: true,
                        title: 'Your Order Could Not Be Completed',
                        subTitle: this.error(),
                        focus: 'none',
                        buttons: [{
                            text: 'Try Again',
                            class: 'sp-button sp-button--primary sp-mx-4',
                            click() {
                                if (self.refreshCheckout()) {
                                    self.loadingMask().style.display = 'block';
                                    window.location.hash = 'shipping';
                                    window.location.reload();
                                }

                                $('#error-modal').modal('closeModal');
                            },
                        }],
                        closed() {
                            if (self.refreshCheckout()) {
                                window.location.hash = 'shipping';
                                window.location.reload();
                            }
                        },
                    };
                });
            },

            initializeRecurly() {
                const self = this;

                recurly.configure(window.recurlyApi);

                /**
                 * Change listener on the recurly hosted card field.
                 * Sets the cardInputTouched observable true if any of
                 * the card fields has been touched.
                 *
                 * @param {object} state recurly form state
                 */
                recurly.on('change', (state) => {
                    if (
                        !state.fields.card.number.empty ||
                        !state.fields.card.cvv.empty ||
                        !state.fields.card.expiry.empty
                    ) {
                        self.cardInputTouched(true);
                    } else {
                        self.cardInputTouched(false);
                    }
                });
            },

            closeZipModal() {
                $('#zip-popup-modal').modal('closeModal');
            },

            getShippingAddress: function () {
                var checkoutData = JSON.parse(localStorage['mage-cache-storage']);
                checkoutData = checkoutData['checkout-data'];

                return checkoutData.shippingAddressFromData;
            },

            getBillingAddress: function () {
                var checkoutData = JSON.parse(localStorage['mage-cache-storage']);
                checkoutData = checkoutData['checkout-data'];

                return checkoutData.billingAddressFromData;
            },

            createNewSubscription(token_id) {
                const self = this;
                const form = document.querySelector('.recurly-form');
                const formKey = document.querySelector('input[name=form_key]').value;
                const quizID = window.sessionStorage.getItem('quiz-id');
                const subscriptionPlan = window.sessionStorage.getItem('subscription_plan');
                const isBillingSameAsShipping = $('input[name="billing-address-same-as-shipping"]:checked').val() === 'on';
                const address = (isBillingSameAsShipping === false) ? this.getBillingAddress() : this.getShippingAddress();

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + '/rest/V1/subscription/createSubscription',
                    contentType: 'application/json',
                    processData: false,
                    showLoader: true,
                    data: JSON.stringify({
                        'key': formKey,
                        'token': token_id,
                        'quiz_id': quizID,
                        'billing_address': address,
                        'billing_same_as_shipping': isBillingSameAsShipping,
                    }),
                    success(response) {
                        response = JSON.parse(response);

                        window.sessionStorage.setItem('subscription_id', response.data.subscription_id);
                        window.location.href = '/success';
                    },
                    error(response) {
                        response = JSON.parse(response.responseJSON);

                        self.error(response.message);
                        self.refreshCheckout(!!response.data.refresh);

                        if (response.data.error_code === 'Z1') {
                            Modal(self.zipModalOptions, $('#zip-popup-modal'));
                            $('#zip-popup-modal').modal('openModal');
                            self.orderProcessing(false);
                        } else {
                            Modal(self.errorModalOptions(), $('#error-modal'));
                            $('#error-modal').modal('openModal');

                            // No need to refresh the screen so enable the order
                            // button.
                            if (!response.data.refresh) {
                                self.orderProcessing(false);
                            }
                        }
                    },
                });
            },

            updateRecurlyFormData: function () {
                var shippingAddress = this.getShippingAddress();

                // Check if customer has selected to use the same address for both billing and shipping
                var isBillingSameAsShipping = ($('input[name="billing-address-same-as-shipping"]:checked').val() == 'on') ? true : false;

                // Get the billing address data based on the customer selection
                var address = (isBillingSameAsShipping === false) ? this.getBillingAddress() : this.getShippingAddress();

                // Get full state name by it's id
                var stateName = $('select[name="region_id"] option[value="' + address.region_id + '"]').attr('data-title');

                // Get full country name by it's id
                var countryName = $('select[name="country_id"] option[value="' + address.country_id + '"]').attr('data-title');

                // Update Recurly form
                $('input[data-recurly="first_name"]').val(address.firstname);
                $('input[data-recurly="last_name"]').val(address.lastname);
                $('input[data-recurly="address1"]').val(address.street[0]);
                $('input[data-recurly="city"]').val(address.city);
                $('input[data-recurly="state"]').val(stateName);
                $('input[data-recurly="country"]').val(countryName);
                $('input[data-recurly="postal_code"]').val(address.postcode);

                return true;
            },

            myPlaceOrder: function () {
                var self = this;
                self.orderProcessing(true);
                var recurlyForm = $('.recurly-form');
                var rsco = $('input[name="rsco_accept"]');

                if (!rsco[0].checked) {
                    rsco[0].setCustomValidity('This field is required.');
                    self.orderProcessing(false);
                    return false;
                } else {
                    rsco[0].setCustomValidity('');
                }

                if (!self.updateRecurlyFormData()) {
                    self.orderProcessing(false);
                    return false;
                }

                recurly.token(recurlyForm, function (err, token) {
                    if (err) {
                        self.orderProcessing(false);
                        if (err.code === 'validation') {
                            if (err.fields.includes('number')) {
                                $('.recurly-form-error').text('Please enter a valid card number.');
                            } else if (
                                !err.fields.includes('number') &&
                                (err.fields.includes('month') || err.fields.includes('year'))
                            ) {
                                $('.recurly-form-error').text('Please enter a valid expiration date.');
                            } else {
                                $('.recurly-form-error').text(err.message);
                            }
                        } else {
                            $('.recurly-form-error').text(err.message);
                        }
                    } else {
                        self.createNewSubscription(token.id);
                    }
                })
            },
        });
    }
);
