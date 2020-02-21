define([
  "jquery",
  "Magento_Ui/js/modal/modal",
  "mage/validation",
  "domReady!"
], function($, modal) {
  "use strict";

  function main(config, element) {
    var $element = $(element);
    var dataForm = $("#contact-form");
    dataForm.mage("validation", {});

    $.validator.addMethod(
        'validate-name',
        function (value) {
            if (value != '') {
              if (!isNaN(value)) {
                return false;
              }
              if( value.match(/^[a-zA-Z\.\-\'\sàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸåÅæÆœŒœŒçÇðÐøØ¿¡ß]*$/) ) {

                 return true
              } else {
                return false;
              }

            } else {
              return !$.mage.isEmpty(value);
            }
          },
        $.mage.__('Please enter a valid name.')
    );

    $.validator.addMethod(
        'validate-email-address',
        function(value) {
            return (
                /^([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/i.test(value) &&
                value.length >= 1
            );
        },
        $.mage.__('Please enter a valid email address.')
    );

    $.validator.addMethod(
        'validate-message',
        function (value) {
            if (value != '') {
              if (!isNaN(value)) {
                return false;
              }
              if (value.length > 10) {
                 return true;
              }

              return false;
            } else {
              return !$.mage.isEmpty(value);
            }
          },
        $.mage.__('Please enter a valid message.')
    );

    const nameInput = document.querySelector('input[name="name"]');
    const emailInput = document.querySelector('input[name="email"]');
    const messageTextarea = document.querySelector('textarea[name="comment"]');

    const inputs = [ nameInput, emailInput, messageTextarea ];

    Array.prototype.forEach.call(inputs, function(input) {
        input.addEventListener('blur', function() {
            $.validator.validateSingleElement($(input));

        });
    });

    $(document).on("submit", "#contact-form", function() {
      event.preventDefault();
      if (dataForm.valid()) {
        $.ajax({
          type: "POST",
          url: config.AjaxUrl,
          data: dataForm.serialize(),
          success: function(response) {
            if (response.success == true) {
              var options = {
                type: "popup",
                responsive: true,
                innerScroll: true,
                buttons: [
                  {
                    text: $.mage.__("Close"),
                    class: "sp-button sp-button--primary",
                    click: function() {
                      this.closeModal();
                    }
                  }
                ]
              };

              var popup = modal(options, $("#popup-modal"));
              $("#popup-modal").modal("openModal");
            } else {
              var popup = modal(options, $("#popup-modal"));
              $("#popup-modal").html(
                '<h3 style="text-align: center">' + response.message + "</h3>"
              );
              $("#popup-modal").modal("openModal");

              $(":input", dataForm)
                .not(":button, :submit, :reset, :hidden")
                .val("")
                .removeAttr("selected");
              $("#topic").val(0);
            }
          }
        });
      }
    });
  }

  return main;
});
