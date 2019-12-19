define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        initialize(config) {
            console.log(config);
            this.billing = ko.observable(config.billing);
            this.states = ko.observable(config.states);
            this.countries = ko.observable(config.countries);

            setTimeout( function() {
                recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
            }, 2000);
        },
        saveBilling: function() {
            var recurlyForm = $('form#recurlyForm');
            recurly.token( recurlyForm, function( err, token ) {
                if( err ) {
                    alert( err.message );
                } else {
                    if( token ) {
                        $.ajax({
                            type: 'POST',
                            url: '/account/billing/save',
                            data: JSON.stringify( {
                                token: token.id
                            } ),
                            success: function( response ) {
                                var response = JSON.parse( response );
                            }
                        })
                    }
                }
            })
        }
    });
});