define([
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery',
    'knockoutjs/knockout-toggle-click'
], function (Component, ko, modal, $) {
    let successModal;

    return Component.extend({
        initialize(config) {
            var self = this;

            // Save default config for use later.
            self.config = config;

            // Setup Account Observables for usage in our form.
            self.account = {
                email: ko.observable(config.account.email || ''),
                firstName: ko.observable(config.account.firstname || ''),
                lastName: ko.observable(config.account.lastname),
                currentPassword: ko.observable(''),
                newPassword: ko.observable(''),
                newPasswordConfirm: ko.observable(''),
            };

            self.modalValues = ko.observable({});
            self.accountInfoEditable = ko.observable(false);
            self.saving = ko.observable(false);

            setTimeout(function() {
                responseModal = modal({
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    buttons: [],
                    opened: function ($Event) {
                        $('.modal-header').remove();
                    }
                }, $('#popup-modal'));

            }, 1000);
        },

        passwordStrength: function() {

            // Get Password Value.
            var val= $('input[name="newPassword"]').val();

            // Create an array and push all requirements for password.
            var matchedCase = new Array();
            // Special Character.
            matchedCase.push("[$@$!%*#?&]");
            // Uppercase.
            matchedCase.push("[A-Z]");
            // Number.
            matchedCase.push("[0-9]");
            // Lowercase.
            matchedCase.push("[a-z]");

            // Check the conditions
            var ctr = 0;
            for (var i = 0; i < matchedCase.length; i++) {
                if (new RegExp(matchedCase[i]).test(val)) {
                    ctr++;
                }
            }

            // Display Vars.
            var color = "";
            var strength = "";
            var meterWidth = "";

            // If the password length is less than 8.
            if(val.length<8) {

                // Display Settings.
                color = "#cb0000";
                strength = "Too Weak";

                // Based on requirements determine meter width.
                switch (ctr) {
                    case 0:
                        meterWidth = "0px";
                        break;
                    case 1:
                        meterWidth = "20%";
                        break;
                    case 2:
                        meterWidth = "40%";
                        break;
                    case 3:
                        meterWidth = "60%";
                        break;
                    case 4:
                        meterWidth = "80%";
                        break;
                }
            }
            // If the password length is 8 or greater but does not meet requirements.
            else if(val.length>=8 && ctr <= 2 ) {

                // Display Settings.
                color = "#cb0000";
                strength = "Too Weak";

                // Based on requirements determine meter width.
                switch (ctr) {
                    case 0:
                        meterWidth = "0px";
                        break;
                    case 1:
                        meterWidth = "20%";
                        break;
                    case 2:
                        meterWidth = "40%";
                        break;
                }
            }
            // If the password length is 8 or greater but does not meet requirements.
            else if(val.length>=8 && ctr >= 3 ) {
                strength = "Very Strong";
                color = "#4ABABC";
                meterWidth = "100%";
            }
            document.getElementById("pass_type").innerHTML = strength;
            $("#pass_type").css("color", color);
            $("#meter").animate({width:meterWidth},100).css("background-color", color);
        },

        saveAccount: function() {
            const self = this;
            const formKey = document.querySelector('input[name=form_key]').value;
            self.saving(true);

            $.ajax({
                type: 'POST',
                url: '/account/settings/save',

                data: JSON.stringify( {
                    form_key: formKey,
                    firstname: self.account.firstName(),
                    lastname: self.account.lastName(),
                    email: self.account.email(),
                    password: self.account.currentPassword(),
                    newPassword: self.account.newPassword(),
                    passwordRetype: self.account.newPasswordConfirm(),
                }),
                success(data) {
                    const { success, message } = data;

                    // Reset form state after saving.
                    self.saving(false);
                    self.accountInfoEditable(false);
                    self.account.currentPassword('');
                    self.account.newPassword('')
                    self.account.newPasswordConfirm('');

                    if (!success) {

                        if (message.indexOf('Login identifier exists') >= 0) {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'An account already exists with the email address you entered, please use a different email address.'
                            });
                        }
                        else if (message.indexOf('updating the account details') >= 0) {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'There was a problem saving your account details, please try again later.'
                            });
                        }
                        if (message.indexOf('password') >= 0) {
                            // Indicate what kind of password error happened.
                            if (message.indexOf('Missing required parameter') >= 0) {
                                self.modalValues({
                                    header: 'Problem saving changes',
                                    message: 'Current Password is a required field when setting new password. Please enter your current password and try again.'
                                });
                            }
                            else if(message.indexOf('Invalid LoginID') >= 0) {
                                self.modalValues({
                                    header: 'Problem saving changes',
                                    message: 'Invalid current password was entered.'
                                });
                            }
                            else {
                                self.modalValues({
                                    header: 'Problem saving changes',
                                    message: 'There was a problem updating your password.'
                                });
                            }
                        }
                        if (message == 'Email is required') {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'Your email address is required, please enter your email address and try again.'
                            });
                        }
                        if (message === 'Last name is required') {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'Last name is a required field, please enter your first name and try again.'
                            });
                        }
                        if (message === 'First name is required') {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'First name is a required field, please enter your first name and try again.'
                            });
                        }
                        if (message.indexOf('New password do not match') >= 0) {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'The new passwords that you entered do not match, please make sure that they match.'
                            });
                        }
                        if (message.indexOf('customer with the same email address') >= 0) {
                            /**
                             * On a failed email address update, pull the ref to the old
                             * email address and update the stored account accordingly
                             * in order to update email input field to correspond to
                             * actual account values.
                             */
                            self.account.email(this.config.account.email);
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'An account already exists with the email address you entered, please use a different email address.'
                            });
                        }
                    } else {
                        self.modalValues({
                            header: 'Changes Saved',
                            message: 'The changes to your account have been saved.'
                        });
                    }

                    self.showModal();
                }
            });
        },

        hideModal() {
            responseModal.closeModal();
        },

        showModal() {
            responseModal.openModal();
        },
        resetFields() {
            // Reset fields back to their original values.
            this.account.firstName(this.config.account.firstname);
            this.account.lastName(this.config.account.lastname);
            this.account.email(this.config.account.email);
            this.account.currentPassword('');
            this.account.newPassword('');
            this.account.newPasswordConfirm('');
        }
    });
});

