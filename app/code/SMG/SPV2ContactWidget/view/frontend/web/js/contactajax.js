define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'domReady!'
], function($, modal){
    'use strict';

    function main(config, element) {

        var dataForm = $('#contact-form');
        dataForm.mage('validation', {});

        $(document).on('submit', '#contact-form', function() {
            event.preventDefault();

            $.ajax({
                type: 'POST',
                url: config.AjaxUrl,
                contentType: 'application/json',
                dataType: 'json',
                data: dataForm.serialize(),
                success: function(response) {
                    if( response.status == 200 ) {
                        var options = {
                            type: 'popup',
                            responsive: true,
                            innerScroll: true,
                            buttons: false,
                        };

                        var popup = modal(options, $('#popup-modal'));
                        $('#popup-modal').modal('openModal');

                    }
                }
            })
        });
    }

    return main;
});