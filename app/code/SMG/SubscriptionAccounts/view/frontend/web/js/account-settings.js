define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        initialize(config) {
            console.log(config);
            this.account = ko.observable(config.account);
            let self = this;
        },
        saveAccount: function() {
            var formKey = document.querySelector('input[name=form_key]').value;

            $.ajax({
                type: 'POST',
                url: '/account/settings/save',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify( {
                    form_key: formKey,
                    firstname: $('input[name="firstname"]').val(),
                    lastname: $('input[name="lastname"]').val(),
                    email: $('input[name="email"]').val(),
                    current_password: $('input[name="current_password"]').val(),
                    new_password: $('input[name="new_password"]').val(),
                    confirm_new_password: $('input[name="confirm_new_password"]').val(),
                } ),
                success: function (response) {
                    console.log( response );
                }
            })
        }
    });
});

