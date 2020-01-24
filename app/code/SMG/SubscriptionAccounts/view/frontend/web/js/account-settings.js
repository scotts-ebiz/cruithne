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
            this.modalValues = ko.observable({});

            setTimeout(function() {
                successModal = modal({
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
                    console.log(data);
                    if (data.success) {
                        self.modalValues({
                            header: "Changes Saved",
                            copy: "The changes to your account have been saved."
                        });
                    } else {
                        self.modalValues({
                            header: "Issue",
                            copy: "There was a problem with updating your account details, please try again later."
                        });
                    }

                    self.showSuccess()
                },
                error(err) {
                    console.log(err);
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

