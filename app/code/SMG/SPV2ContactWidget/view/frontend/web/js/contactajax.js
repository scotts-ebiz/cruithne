define(
    [
    'uiComponent',
    'ko',
    'Magento_Ui/js/modal/modal',
    'jquery',
    "mage/validation",
    "domReady!"
    ], function (Component, ko, modal, $) {
        'use strict';
        return Component.extend(
            {
                initialize(config) {

                    let self = this;

                    self.defaultConfig = config;

                    // Initialize contact form observables.
                    self.form = {};
                    self.form.name = ko.observable(config.customerName || '');
                    self.form.address = ko.observable(config.address || '');
                    self.form.address2 = ko.observable(config.address2 || '');
                    self.form.city = ko.observable(config.city || '');
                    self.form.state = ko.observable(config.state || null);
                    self.form.postalCode = ko.observable(config.postalCode || '');
                    self.form.email = ko.observable(config.email || '');
                    self.form.phone = ko.observable(config.phone || '');
                    self.form.topic = ko.observable(config.topic || null);
                    self.form.comment = ko.observable(config.comment || '');
                    self.form.grassType = ko.observable(config.grassType || null);

                    self.saving = ko.observable(false);

                    // Only show address fields if user selects relevant address topics.
                    self.addressVisible = ko.pureComputed(
                        function () {

                            let showAddressFields = self.form.topic() === 'Question or concern with my order' ||
                            self.form.topic() === 'Canceling my subscription';

                            // Initialize validation meta data for each form field.
                            $('#address, #city, #state, #postalCode, #phone').each(
                                function () {
                                    $(this).metadata();
                                }
                            );

                            // Turn on/off M2 validation based on visibility.
                            if (showAddressFields) {
                                $('#address, #city, #state, #postalCode, #phone').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = true;
                                    }
                                );
                            }
                            else {
                                $('#address, #city, #state, #postalCode, #phone').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = false;
                                    }
                                );
                            }

                            return showAddressFields;
                        }
                    );

                // Only show lawn specific fields if user selects relevant lawn topics.
                self.lawnInfoVisible = ko.pureComputed(
                    function () {
                        let showLawnInfoFields = self.form.topic() === 'Information about products or my lawn care plan';

                        // Initialize validation meta data for each form field.
                        $('#grassType, #postalCode').each(
                            function () {
                                $(this).metadata();
                            }
                        );

                        // Turn on/off M2 validation based on visibility
                        if (showLawnInfoFields) {
                            $('#grassType, #postalCode').each(
                                function () {
                                    $(this).data('metadata')['validate']['required'] = true;
                                }
                            );
                        }
                        else {
                            $('#grassType, #postalCode').each(
                                function () {
                                    $(this).data('metadata')['validate']['required'] = false;
                                }
                            );
                        }

                        return showLawnInfoFields;
                    }
                );

                self.topics = ko.observableArray(
                    [
                    'Information about products or my lawn care plan',
                    'Question or concern with my order',
                    'Canceling my subscription',
                    'Question or concern with the website or mobile app',
                    'General question or comment'
                    ]
                );
                self.states = ko.observableArray(
                    [
                    "Alabama",
                    "Alaska",
                    "Arizona",
                    "Arkansas",
                    "California",
                    "Colorado",
                    "Connecticut",
                    "Delaware",
                    "District of Columbia",
                    "Florida",
                    "Georgia",
                    "Guam",
                    "Hawaii",
                    "Idaho",
                    "Illinois",
                    "Indiana",
                    "Iowa",
                    "Kansas",
                    "Kentucky",
                    "Louisiana",
                    "Maine",
                    "Maryland",
                    "Massachusetts",
                    "Michigan",
                    "Minnesota",
                    "Mississippi",
                    "Missouri",
                    "Montana",
                    "Nebraska",
                    "Nevada",
                    "New Hampshire",
                    "New Jersey",
                    "New Mexico",
                    "New York",
                    "North Carolina",
                    "North Dakota",
                    "Ohio",
                    "Oklahoma",
                    "Oregon",
                    "Pennsylvania",
                    "Puerto Rico",
                    "Rhode Island",
                    "South Carolina",
                    "South Dakota",
                    "Tennessee",
                    "Texas",
                    "Utah",
                    "Vermont",
                    "Virginia",
                    "Washington",
                    "West Virginia",
                    "Wisconsin",
                    "Wyoming"
                    ]
                );

                self.grassTypes = ko.observableArray(
                    [
                    'Bahia',
                    'Bahia & St. Augustine',
                    'Bermuda',
                    'Bermuda & St. Augustine',
                    'Bluegrass/Rye/Fescue',
                    'Buffalo grass',
                    'Carpetgrass',
                    'Centipede',
                    'Dichondra',
                    'Fine Fescue',
                    'Kentucky Bluegrass',
                    'Perennial Ryegrass',
                    'St. Augustine/Floratam',
                    'Tall Fescue',
                    'Zoysia',
                    'I don\'t know'
                    ]
                );

                var dataForm = $("#contact-form");
                dataForm.mage("validation", {});
                dataForm.show();
                $('.loader').hide();

                $.validator.addMethod(
                    'validate-name',
                    function (value) {
                        if (value != '') {
                            if (!isNaN(value)) {
                                return false;
                            }

                            if (value.match(/^[a-zA-Z\.\-\'\sàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸåÅæÆœŒœŒçÇðÐøØ¿¡ß]*$/)) {
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
                    function (value) {
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

                $.validator.addMethod(
                    'validate-postal-code',
                    function (value) {
                        return (/(^\d{5}$)|(^\d{5}-\d{4}$)/).test(value);
                    },
                    $.mage.__('Please enter a valid 5 digit US ZIP.')
                );

                // If user leaves a given required field, validate that field.
                $('body').on('blur', "#name, #email, #comment, #topic, #address, #city, #state, #postalCode, #grassType, #phone", function () {
                    if ($.validator.validateSingleElement($(this))) {
                        $(this).removeAttr('aria-invalid');
                    }
                });

                $(document).on(
                    "submit", "#contact-form", function () {
                        event.preventDefault();
                        if (dataForm.valid()) {
                            self.saving(true);
                            $.ajax(
                                {
                                    type: "POST",
                                    url: config.AjaxUrl,
                                    data: dataForm.serialize(),
                                    success: function (response) {
                                        self.saving(false);

                                        if (response.success == true) {
                                            var options = {
                                                type: "popup",
                                                responsive: true,
                                                innerScroll: true,
                                                buttons: [
                                                {
                                                    text: $.mage.__("Close"),
                                                    class: "sp-button sp-button--primary",
                                                    click: function () {
                                                        this.closeModal();
                                                    }
                                                }
                                                ]
                                            };

                                            // Reset form to what was loaded initially.
                                            self.form.name(self.defaultConfig.customerName || '');
                                            self.form.address(self.defaultConfig.address || '');
                                            self.form.address2(self.defaultConfig.address2 || '');
                                            self.form.city(self.defaultConfig.city || '');
                                            self.form.state(self.defaultConfig.state || null);
                                            self.form.postalCode(self.defaultConfig.postalCode || '');
                                            self.form.email(self.defaultConfig.email || '');
                                            self.form.phone(self.defaultConfig.phone || '');
                                            self.form.topic(self.defaultConfig.topic || null);
                                            self.form.comment(self.defaultConfig.comment || '');
                                            self.form.grassType(self.defaultConfig.grassType || null);


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
                                }
                            );
                        }
                    }
                );
                }
            }
        );
    }
);
