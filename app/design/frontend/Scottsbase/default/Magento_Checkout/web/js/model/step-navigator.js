/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/action/select-shipping-address'
], function ($,
             ko,
             quote,
             urlBuilder,
             storage,
             customer,
             fullScreenLoader,
             addressConverter,
             checkoutData,
             selectBillingAddress,
             selectShippingAddress) {
    'use strict';

    var steps = ko.observableArray();

    return {
        steps: steps,
        stepCodes: [],
        validCodes: [],

        /**
         * @return {Boolean}
         */
        handleHash: function () {
            var hashString = window.location.hash.replace('#', ''),
                isRequestedStepVisible;

            if (hashString === '') {
                return false;
            }

            if ($.inArray(hashString, this.validCodes) === -1) {
                window.location.href = window.checkoutConfig.pageNotFoundUrl;

                return false;
            }

            isRequestedStepVisible = steps.sort(this.sortItems).some(function (element) {
                return (element.code == hashString || element.alias == hashString) && element.isVisible(); //eslint-disable-line
            });

            //if requested step is visible, then we don't need to load step data from server
            if (isRequestedStepVisible) {
                return false;
            }

            steps().sort(this.sortItems).forEach(function (element) {
                if (element.code == hashString || element.alias == hashString) { //eslint-disable-line eqeqeq
                    element.navigate(element);
                } else {
                    element.isVisible(false);
                }

            });

            return false;
        },

        /**
         * @param {String} code
         * @param {*} alias
         * @param {*} title
         * @param {Function} isVisible
         * @param {*} navigate
         * @param {*} sortOrder
         */
        registerStep: function (code, alias, title, isVisible, navigate, sortOrder) {
            var hash, active;

            if ($.inArray(code, this.validCodes) !== -1) {
                throw new DOMException('Step code [' + code + '] already registered in step navigator');
            }

            if (alias != null) {
                if ($.inArray(alias, this.validCodes) !== -1) {
                    throw new DOMException('Step code [' + alias + '] already registered in step navigator');
                }
                this.validCodes.push(alias);
            }
            this.validCodes.push(code);
            steps.push({
                code: code,
                alias: alias != null ? alias : code,
                title: title,
                isVisible: isVisible,
                navigate: navigate,
                sortOrder: sortOrder
            });
            active = this.getActiveItemIndex();
            steps.each(function (elem, index) {
                if (active !== index) {
                    elem.isVisible(false);
                }
            });
            this.stepCodes.push(code);
            hash = window.location.hash.replace('#', '');

            if (hash != '' && hash != code) { //eslint-disable-line eqeqeq
                //Force hiding of not active step
                isVisible(false);
            }
        },

        /**
         * @param {Object} itemOne
         * @param {Object} itemTwo
         * @return {Number}
         */
        sortItems: function (itemOne, itemTwo) {
            return itemOne.sortOrder > itemTwo.sortOrder ? 1 : -1;
        },

        /**
         * @return {Number}
         */

        getActiveItemIndex: function () {
            var activeIndex = 0;

            steps().sort(this.sortItems).some(function (element, index) {
                if (element.isVisible()) {
                    activeIndex = index;

					if (activeIndex == 1) {
						$('.custom-checkout-nav-btns').css("display", "none");
						setTimeout(function(){

							$(".checkout-billing-address .street input").focusout(function() {
								var str = $(this).val();
								var nval = str.replace(/  +/g, ' ');
								$(this).val(nval);
							});

							$(".checkout-billing-address input[name='telephone']").focusout(function() {
								var str = $(this).val()
								var nval = str.replace(/^[(]?(\d{3})[)]?[-|\s]?(\d{3})[-|\s]?(\d{4})$/,'$1-$2-$3')
								$(this).val(nval);
							});

						}, 7000);
					}

					else {
					    $('.custom-checkout-nav-btns').css("display", "block");
						setTimeout(function(){

								$('.form-shipping-address input:visible').focusout(function() {
									var str = $(this).val();
									var nval = str.replace(/[&\/\\#,+$~%*?<>{}@!^]/g, '');
									$(this).val(nval);
								});
								$(".form-shipping-address .street input").focusout(function() {
									var str = $(this).val();
									var nval = str.replace(/  +/g, ' ');
									$(this).val(nval);
								});

								if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
										$("select[name='region_id']").click();
										$('#shipping-method-buttons-container button').prop('disabled', false);
								}

								$(".checkout-shipping-address input:visible, select[name='region_id']").on('focusout change', function(e) {
									$('#shipping-method-buttons-container button').prop('disabled', true);
									if ($("input[name='username']").val() != '' && $("select[name='region_id']").val() != '' && $("input[name='firstname']").val() != '' && $("input[name='lastname']").val() != '' && $("input[name='street[0]']").val() != '' && $("input[name='city']").val() != '' && $("input[name='postcode']").val() != '' && $("input[name='telephone']").val() != '') {
										$('#shipping-method-buttons-container button').prop('disabled', false);
									}
								});

						$("#shipping-new-address-form input[name='telephone']").focusout(function() {
							var str = $(this).val();
									var nval = str.replace(/^[(]?(\d{3})[)]?[-|\s]?(\d{3})[-|\s]?(\d{4})$/,'$1-$2-$3')
							$(this).val(nval);
						});
						}, 7000);
					}


                    return true;
                }

                return false;
            });

            return activeIndex;
        },


        /**
         * @param {*} code
         * @return {Boolean}
         */
        isProcessed: function (code) {
            var activeItemIndex = this.getActiveItemIndex(),
                sortedItems = steps().sort(this.sortItems),
                requestedItemIndex = -1;

            sortedItems.forEach(function (element, index) {
                if (element.code == code) { //eslint-disable-line eqeqeq
                    requestedItemIndex = index;
                }
            });

            return activeItemIndex > requestedItemIndex;
        },

        /**
         * @param {*} code
         * @param {*} scrollToElementId
         */
        navigateTo: function (code, scrollToElementId) {
            var sortedItems = steps().sort(this.sortItems),
                bodyElem = $('body');

            scrollToElementId = scrollToElementId || null;

            if (!this.isProcessed(code)) {
                return;
            }
            sortedItems.forEach(function (element) {
                if (element.code == code) { //eslint-disable-line eqeqeq
                    element.isVisible(true);
                    bodyElem.animate({
                        scrollTop: $('#' + code).offset().top
                    }, 0, function () {
                        window.location = window.checkoutConfig.checkoutUrl + '#' + code;
                    });

                    if (scrollToElementId && $('#' + scrollToElementId).length) {
                        bodyElem.animate({
                            scrollTop: $('#' + scrollToElementId).offset().top
                        }, 0);
                    }
                } else {
                    element.isVisible(false);
                }

            });
        },

        /**
         * Sets window location hash.
         *
         * @param {String} hash
         */
        setHash: function (hash) {
            window.location.hash = hash;
        },

        /**
         * Next step.
         */
        next: function () {
            var activeIndex = 0,
                code;

            // refresh the address data from the database
            this.refreshFromServer();

            steps().sort(this.sortItems).forEach(function (element, index) {
                if (element.isVisible()) {
                    element.isVisible(false);
                    activeIndex = index;
                }
            });

            if (steps().length > activeIndex + 1) {
                code = steps()[activeIndex + 1].code;
                steps()[activeIndex + 1].isVisible(true);
                this.setHash(code);
                document.body.scrollTop = document.documentElement.scrollTop = 0;
            }
        },

        /**
         * Gets the updated address from the server after
         * Avalara has updated the data.
         *
         * @returns {*}
         */
        refreshFromServer: function () {
            var serviceUrl,
                payload;

            /**
             * Checkout for guest and registered customer.
             */
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId', {
                    cartId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId()
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine', {});
                payload = {
                    cartId: quote.getQuoteId()
                };
            }

            // start the loader image
            fullScreenLoader.startLoader();

            // make a call to retrieve the desired data
            return storage.get(
                serviceUrl, JSON.stringify(payload)
            ).done(
                function (response) {
                    var shippingAddress,
                        newShippingAddress;

                    // get the shipping address from the database
                    shippingAddress = response['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

                    // convert the database data to form data
                    newShippingAddress = addressConverter.formAddressDataToQuoteAddress(shippingAddress);

                    // update the Shipping Address with the database address
                    selectShippingAddress(newShippingAddress);

                    // update the Billing Address with the shipping address
                    // this works because we always default the billing address to the shipping address
                    selectBillingAddress(newShippingAddress);

                    // stop the loader image
                    fullScreenLoader.stopLoader();
                }
            ).fail(
                function (response) {
                    console.log("Failed to update address from database.");
                    console.log(response);

                    // stop the loader image
                    fullScreenLoader.stopLoader();
                }
            );
        }
    };
});