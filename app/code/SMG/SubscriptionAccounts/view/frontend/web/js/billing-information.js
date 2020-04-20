define([
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery',
    'mage/mage',
    'knockoutjs/knockout-toggle-click'
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
            }, 2000);
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
                    },
                        error: function ( response ) {
                            self.saving(false);
                            self.modalErrorMessage(JSON.stringify(response));
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
            $('#recurlyForm input').attr('required', false);
            //$('#recurlyForm').trigger('reset');
            $('form#recurlyForm').mage('validation', {});
        },
        
        cancelBilling() {
            let self = this;
            self.billingInfoEditable(false);
        }
    });
});
