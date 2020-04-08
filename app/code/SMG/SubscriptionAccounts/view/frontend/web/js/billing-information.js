define([
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery'
], function (Component, ko, modal, $) {
    let successModal;

    return Component.extend({
		isVisible: ko.observable(true),
		
        initialize(config) {
            const self = this;
            this.billing = ko.observable(config.billing);
            this.states = ko.observable(config.states);
            this.countries = ko.observable(config.countries);
            

            setTimeout( function() {
                recurly.configure({
                    publicKey: config.recurlyApi,
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
            $('body').trigger('processStart');
            recurly.token( recurlyForm, function( err, token ) {
                if( err ) {
	    $('body').trigger('processStop');
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
			    $('body').trigger('processStop');
				location.reload(); 
                            },
			    error: function( response ) {
			    $('body').trigger('processStop');
			        location.reload(); 
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
        },
		
		hideChange() {
            $('.changehide').hide();
			$('.cardview').hide();
            $('#recurlyForm').trigger('reset');
            $('.hideme').show();
			$('.cardedit').show();
        },
		
		cancelBilling() {
            $('body').trigger('processStart');
			location.reload(); 
		}
    });
});
