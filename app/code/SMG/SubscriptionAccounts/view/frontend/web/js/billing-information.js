define([
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery',
    'mage/mage',
    'knockoutjs/knockout-toggle-click',
    "mage/validation"
], function (Component, ko, modal, $) {
    let modalBilling;
   
    return Component.extend({
        isVisible: ko.observable(true),
        
        initialize(config) {
            let self = this;

            // Initialize billing info observables
            self.billing = {};
            self.billing.first_name = ko.observable(config.billing.first_name);
            self.billing.last_name = ko.observable(config.billing.last_name);
            self.billing.address1 = ko.observable(config.billing.address1);
            self.billing.address2 = ko.observable(config.billing.address2);
            self.billing.city = ko.observable(config.billing.city);
            self.billing.state = ko.observable(config.billing.state);
            self.billing.zip = ko.observable(config.billing.zip);
            self.billing.country = ko.observable(config.billing.country || '')
            self.billing.card_on_file = ko.observable(config.billing.card_on_file.replace(/_/g, '-'));

            // Initialize billing info options list
            self.states = ko.observableArray(Object.values(config.states));
            self.countries = ko.observableArray(Object.values(config.countries));

            // Form state variables
            self.billingInfoEditable = ko.observable(false);
            self.saving = ko.observable(false);
            self.modalErrorMessage = ko.observable('');

            setTimeout(function () {
                recurly.configure({
                    publicKey: config.recurlyApi,
                    required : ['cvv'],
                    fields: {
                        card: {
                            // Field style properties
                            style: {
                                fontSize: '12px',
                            }
                        }

                    }
                });

                $(window).on('resize init', function (event) {
                    if ($(this).width() <= 767) {
                        recurly.configure({
                            required : ['cvv'],
                            fields: {
                                card: {
                                    // Field style properties
                                    style: {
                                        fontSize: '12px',
                                    }
                                }
                            }
                        });
                    } else {
                        recurly.configure({
                            required : ['cvv'],
                            fields: {
                                card: {
                                    // Field style properties
                                    style: {
                                        fontSize: '14px',
                                    }
                                }
                            }
                        });
                    }
                }).triggerHandler('init');

                modalBilling = modal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    buttons: [],
                    opened: function ($Event) {
                        $('.modal-header').remove();
                    }
                }, $('#popup-modal'));

                $.validator.addMethod(
                    'validate-name',
                    function (value) {
                        if (value != '') {
                            if (!isNaN(value)) {
                                return false;
                            }

                            if (value.match(/^[a-zA-Z\.\-\'\sàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸåÅæÆœŒœŒçÇðÐøØ¿¡ß]*$/)) {
                                return true
                            } else {
                                return false;
                            }

                        } else {
                            return !$.mage.isEmpty(value);
                        }
                    },
                    $.mage.__('Please enter a valid name.')
                );

                $.validator.addMethod(
                    'validate-postal-code',
                    function (value) {
                        return (/(^\d{5}$)|(^\d{5}-\d{4}$)/).test(value);
                    },
                    $.mage.__('Please enter a valid 5 digit US ZIP.')
                );

                $.validator.addMethod(
                    'required-entry-street-0',
                    function (value) {
                        if ($("input[name='street[0]']").val() != '') {
                            if (!isNaN(value)) {
                                return false;
                            }
                            if(/^([a-zA-Z0-9()":;'-.]+ )+[A-Za-z0-9()":;'-.]+$|^[A-Za-z0-9()":;'-.]*$/.test(value)){
                                return true
                            } else {
                                return false;
                            }

                        } else {
                            return !$.mage.isEmpty(value);
                        }
                    },
                    $.mage.__('Please enter a valid street address.')
                );

                $.validator.addMethod(
                    'required-entry-bcity',
                    function (value) {
                        if ($(".billing-address-form input[name='city']").val() != '') {
                            if (!isNaN(value)) {
                                return false;
                            }
                            if( value.match( /^[a-zA-Z ]*$/) ) {
                                return true
                            }else{
                                return false;
                            }
                        } else {
                            return !$.mage.isEmpty(value);
                        }
                    },
                    $.mage.__('Please enter a valid city')
                );

            }, 2000);
        },

        validateField (item, event) {
            if ($.validator.validateSingleElement($(event.target))) {
                $(event.target).removeAttr('aria-invalid');
            }
        },

        saveBilling() {
            const self = this;

            const recurlyForm = $('form#recurlyForm');
            const formKey = document.querySelector('input[name=form_key]').value;
            if (recurlyForm.validation('isValid') === false) {
                return false;
            }

            self.modalErrorMessage('');
            self.saving(true);

            recurly.token(recurlyForm, function ( err, token ) {
                if ( err ) {
                    self.saving(false);
                    self.modalErrorMessage(err);
                    return;
                }
                if ( token ) {
                    $.ajax({
                        type: 'POST',
                        url: '/account/billing/save',
                        data: JSON.stringify({
                            form_key: formKey,
                            token: token.id,
                            form: recurlyForm.serializeArray()
                        }),
                    success: function ( response ) {

                        self.saving(false);

                        if (!response.success) {
                            self.modalErrorMessage(response.message);
                        }
                        else {
                            self.billingInfoEditable(false);

                            // Refresh the page to ensure the CC field mask is updated
                            // since we cannot update it to the new one.
                            window.location.reload();
                        }

                        modalBilling.openModal();
                    },
                    error: function ( response ) {
                        self.saving(false);
                    }
                    })
                }
            });
        },

        hideSuccess() {
            modalBilling.closeModal();
        },

        showSuccess() {
            modalBilling.openModal();
        },
        
        hideChange() {
            let self = this;
            self.billingInfoEditable(true);
            $('form#recurlyForm').mage('validation', {});
        },
        
        cancelBilling() {
            let self = this;
            self.billingInfoEditable(false);
        }
    });
});
