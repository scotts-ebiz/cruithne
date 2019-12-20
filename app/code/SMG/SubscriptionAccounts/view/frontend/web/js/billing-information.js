define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        initialize(config) {
            this.billing = ko.observable(config.billing);
            this.states = ko.observable(config.states);
            this.countries = ko.observable(config.countries);
            this.success = ko.observable(null);

            setTimeout( function() {
                recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
            }, 2000);
        },

        saveBilling() {
            const self = this;
            const recurlyForm = $('form#recurlyForm');
            const formKey = document.querySelector('input[name=form_key]').value;
            console.log(recurlyForm.serializeArray());

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
                                self.success('Your billing information has been updated.');

                                setTimeout(() => {
                                    self.success(null);
                                }, 5000);
                            }
                        })
                    }
                }
            })
        }
    });
});
