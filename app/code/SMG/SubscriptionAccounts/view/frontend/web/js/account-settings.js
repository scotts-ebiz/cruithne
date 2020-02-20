define([
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery'
], function (Component, ko, modal, $) {
    let successModal;

    return Component.extend({
        initialize(config) {
            let self = this;

            this.account = ko.observable(config.account);
            this.accountEmail = ko.observable(config.account.email);
            this.modalValues = ko.observable({});

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
                }),
                success(data) {
                    const { success, message } = data;
                    if (!success) {
                        if (message.indexOf('updating the account details') >= 0) {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'There was a problem saving your account details, please try again later.'
                            })
                        }
                        if (message.indexOf('password') >= 0) {
                            self.modalValues({
                                header: 'Problem saving changes',
                                message: 'There was a problem updating your password.'
                            });
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
                            const newAccount = {
                                ...self.account(),
                                email: self.accountEmail()
                            }
                            self.account(newAccount);

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
        }
    });
});

