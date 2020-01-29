define([
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery'
], function (Component, ko, modal, $) {
    let successModal;

    return Component.extend({
        initialize(config) {
            const self = this;
            this.billing = ko.observable(config.billing);
            this.states = ko.observable(config.states);
            this.countries = ko.observable(config.countries);

            setTimeout( function() {
                recurly.configure(config.recurlyApi);

                successModal = modal({
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

            recurly.token( recurlyForm, function( err, token ) {
                if( err ) {
                    alert( err.message );
                } else {
                    if( token ) {
                        $.ajax({
                            type: 'POST',
                            url: '/account/billing/save',
                            data: JSON.stringify( {
                                form_key: formKey,
                                token: token.id,
                                form: recurlyForm.serializeArray()
                            } ),
                            success: function( response ) {
                                self.hideSuccess();
                            }
                        })
                    }
                }
            })
        },

        hideSuccess() {
            successModal.closeModal();
        },

        showSuccess() {
            successModal.openModal();
        }
    });
});
