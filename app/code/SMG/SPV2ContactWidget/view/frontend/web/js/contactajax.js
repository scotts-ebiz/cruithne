define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'domReady!'
], function($, modal){
    'use strict';

    function main(config, element) {
        var $element = $(element);
        var dataForm = $('#contact-form');
        dataForm.mage('validation', {});

        $(document).on('submit', '#contact-form', function() {
            event.preventDefault();

            if( dataForm.valid() ) {
                $.ajax({
                    type: 'POST',
                    url: config.AjaxUrl,
                    data: dataForm.serialize(),
                    success: function(response) {
                        if( response.success == true ) {
                            var options = {
                                type: 'popup',
                                responsive: true,
                                innerScroll: true,
                                buttons: [
                                    {
                                        text: $.mage.__( 'Close' ),
                                        class: 'sp-button sp-button--primary',
                                        click: function() {
                                            this.closeModal();
                                        }
                                    }
                                ]
                            };

                            var popup = modal(options, $('#popup-modal'));
                            $('#popup-modal').modal('openModal');
                        }
                    }
                })
            }

        });
    }

    return main;
});