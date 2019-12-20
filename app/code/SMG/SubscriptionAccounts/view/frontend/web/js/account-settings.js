define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        initialize(config) {
            let self = this;

            this.account = ko.observable(config.account);
            this.success = ko.observable(null);
        },

        saveAccount: function() {
            const self = this;
            const formKey = document.querySelector('input[name=form_key]').value;

            $.ajax({
                type: 'POST',
                url: '/account/settings/save',
            
                data: JSON.stringify( {
                    form_key: formKey,
                    firstname: $('input[name="firstname"]').val(),
                    lastname: $('input[name="lastname"]').val(),
                    email: $('input[name="email"]').val(),
                    password: $('input[name="password"]').val(),
                    newPassword: $('input[name="newPassword"]').val(),
                    passwordRetype: $('input[name="passwordRetype"]').val(),
                } ),
                success() {
                    self.success('Your account has been saved.');

                    setTimeout(() => {
                        self.success(null);
                    }, 5000);
                }
            })
        }
    });
});

