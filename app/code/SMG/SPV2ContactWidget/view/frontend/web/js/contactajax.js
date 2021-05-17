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
                    self.form.firstName = ko.observable(config.firstName || '');
                    self.form.lastName = ko.observable(config.lastName || '');
                    self.form.address = ko.observable(config.address || '');
                    self.form.address2 = ko.observable(config.address2 || '');
                    self.form.city = ko.observable(config.city || '');
                    self.form.state = ko.observable(config.state || null);
                    self.form.country = ko.observable(config.country || null);
                    self.form.postalCode = ko.observable(config.postalCode || '');
                    self.form.email = ko.observable(config.email || '');
                    self.form.phone = ko.observable(config.phone || '');
                    self.form.topic = ko.observable(config.topic || null);
                    self.form.comment = ko.observable(config.comment || '');
                    self.form.grassType = ko.observable(config.grassType || null);
                    self.form.productName = ko.observable(config.productName || '');
                    self.form.subscriptionNumber = ko.observable(config.subscriptionNumber || '');


                    self.saving = ko.observable(false);

                    $('#contact-form').attr('action', $('#salesForceUrl').attr('value'));
                    $('#salesForceOrgId').appendTo('#contact-form');
                    if ($('#g-recaptcha').length) {
                        $('#g-recaptcha').appendTo('#g-recaptcha-container');
                    }


                    // Only show lawn specific fields if user selects relevant lawn topics.
                    self.lawnInfoVisible = ko.pureComputed(
                        function () {
                            let showLawnInfoFields = self.form.topic() === 'I have a lawn, garden, or product question';

                            // Initialize validation meta data for each form field.
                            $('#grassType').each(
                                function () {
                                    $(this).metadata();
                                }
                            );

                            // Turn on/off M2 validation based on visibility
                            if (showLawnInfoFields) {
                                $('#grassType').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = true;
                                    }
                                );
                            } else {
                                $('#grassType').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = false;
                                    }
                                );
                            }

                            return showLawnInfoFields;
                        }
                    );


                    // Only show lawn specific fields if user selects relevant lawn topics.
                    self.productNameVisible = ko.pureComputed(
                        function () {
                            var shouldShow = self.form.topic() === 'I want to request product Material Safety Data Sheet';

                            // Initialize validation meta data for each form field.
                            $('#productName').each(
                                function () {
                                    $(this).metadata();
                                }
                            );

                            // Turn on/off M2 validation based on visibility
                            if (shouldShow) {
                                $('#productName').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = true;
                                    }
                                );
                            } else {
                                $('#productName').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = false;
                                    }
                                );
                            }

                            return shouldShow;
                        }
                    );

                    // Only show lawn specific fields if user selects relevant lawn topics.
                    self.subscriptionNumberVisible = ko.pureComputed(
                        function () {
                            var shouldShow =
                                self.form.topic() === 'I need to check on the status of an order' ||
                                self.form.topic() === 'I want to cancel an order';

                            // Initialize validation meta data for each form field.
                            $('#subscriptionNumber').each(
                                function () {
                                    $(this).metadata();
                                }
                            );

                            // Turn on/off M2 validation based on visibility
                            if (shouldShow) {
                                $('#subscriptionNumber').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = true;
                                    }
                                );
                            } else {
                                $('#subscriptionNumber').each(
                                    function () {
                                        $(this).data('metadata')['validate']['required'] = false;
                                    }
                                );
                            }

                            return shouldShow;
                        }
                    );


                    self.topics = ko.observableArray(
                        [
                            'I have a lawn, garden, or product question',
                            'I want to report a product safety concern',
                            'I want to request product Material Safety Data Sheet',
                            'I need to check on the status of an order',
                            'I want to cancel an order',
                            'I have a question/concern with the Mobile App',
                            'Other'
                        ]
                    );

                    self.states = ko.observableArray(
                        [
                            {'label': 'Alabama', 'value': 'AL'},
                            {'label': 'Alaska', 'value': 'AK'},
                            {'label': 'Arizona', 'value': 'AZ'},
                            {'label': 'Arkansas', 'value': 'AR'},
                            {'label': 'California', 'value': 'CA'},
                            {'label': 'Colorado', 'value': 'CO'},
                            {'label': 'Connecticut', 'value': 'CT'},
                            {'label': 'Delaware', 'value': 'DE'},
                            {'label': 'District of Columbia', 'value': 'DC'},
                            {'label': 'Florida', 'value': 'FL'},
                            {'label': 'Georgia', 'value': 'GA}'},
                            {'label': 'Hawaii', 'value': 'HI'},
                            {'label': 'Idaho', 'value': 'ID'},
                            {'label': 'Illinois', 'value': 'IL'},
                            {'label': 'Indiana', 'value': 'IN'},
                            {'label': 'Iowa', 'value': 'IA'},
                            {'label': 'Kansas', 'value': 'KS'},
                            {'label': 'Kentucky', 'value': 'KY'},
                            {'label': 'Louisiana', 'value': 'LA'},
                            {'label': 'Maine', 'value': 'ME'},
                            {'label': 'Maryland', 'value': 'MD'},
                            {'label': 'Massachusetts', 'value': 'MA'},
                            {'label': 'Michigan', 'value': 'MI'},
                            {'label': 'Minnesota', 'value': 'MN'},
                            {'label': 'Mississippi', 'value': 'MS'},
                            {'label': 'Missouri', 'value': 'MO'},
                            {'label': 'Montana', 'value': 'MT'},
                            {'label': 'Nebraska', 'value': 'NE'},
                            {'label': 'Nevada', 'value': 'NV'},
                            {'label': 'New Hampshire', 'value': 'NH'},
                            {'label': 'New Jersey', 'value': 'NJ'},
                            {'label': 'New Mexico', 'value': 'NM'},
                            {'label': 'New York', 'value': 'NY'},
                            {'label': 'North Carolina', 'value': 'NC'},
                            {'label': 'North Dakota', 'value': 'ND'},
                            {'label': 'Ohio', 'value': 'OH'},
                            {'label': 'Oklahoma', 'value': 'OK'},
                            {'label': 'Oregon', 'value': 'OR'},
                            {'label': 'Pennsylvania', 'value': 'PA'},
                            {'label': 'Rhode Island', 'value': 'RI'},
                            {'label': 'South Carolina', 'value': 'SC'},
                            {'label': 'South Dakota', 'value': 'SD'},
                            {'label': 'Tennessee', 'value': 'TN'},
                            {'label': 'Texas', 'value': 'TX'},
                            {'label': 'Utah', 'value': 'UT'},
                            {'label': 'Vermont', 'value': 'VT'},
                            {'label': 'Virginia', 'value': 'VA'},
                            {'label': 'Washington', 'value': 'WA'},
                            {'label': 'West Virginia', 'value': 'WV'},
                            {'label': 'Wisconsin', 'value': 'WI'},
                            {'label': 'Wyoming', 'value': 'WY'}
                        ]
                    );


                    self.grassTypes = ko.observableArray(
                        [
                            {'value': "BRF", 'label': 'Blue / Rye / Fescue'},
                            {'value': "UNK", 'label': 'Unknown'},
                            {'value': "BAH", 'label': 'Bahia'},
                            {'value': "BASA", 'label': 'Bahia / St. Augustine'},
                            {'value': "BEN", 'label': 'Bentgrass'},
                            {'value': "BRST", 'label': 'Bermuda / St. Augustine Mix'},
                            {'value': "BER", 'label': 'Bermudagrass'},
                            {'value': "BOB", 'label': 'Bobsod/ Hybrid Of Bermuda'},
                            {'value': "BUFF", 'label': 'Buffalograss'},
                            {'value': "CAR", 'label': 'Carpetgrass'},
                            {'value': "CENT", 'label': 'Centipede'},
                            {'value': "CESA", 'label': 'Centipede/St Augustine Mix'},
                            {'value': "CLVR", 'label': 'Clover'},
                            {'value': "DIC", 'label': 'Dichondra'},
                            {'value': "DCGS", 'label': 'Dichondra/Grass Mix'},
                            {'value': "FLOR", 'label': 'Floratam'},
                            {'value': "KIKU", 'label': 'Kikuyugrass'},
                            {'value': "SEAP", 'label': 'Seashore Paspalum'},
                            {'value': "SA", 'label': 'St Augustine'},
                            {'value': "ZOY", 'label': 'Zoysiagrass'},
                            {'value': "PVKBG", 'label': 'Provista Kentucky Bluegrass'},
                            {'value': "PVSTA", 'label': 'Provista St. Augustine Grass'}
                        ]
                    );

                    self.countries = ko.observableArray([
                        'US',
                        'CA'
                    ])

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

                    $.validator.addMethod(
                        'required-entry-telephone',
                        function (value) {
                            if ($("input[name='telephone']").val() != '') {
                                var filter = /^[(]?(\d{3})[)]?[-|\s]?(\d{3})[-|\s]?(\d{4})$/;
                                if (value.length > 9 && filter.test(value)) {
                                    return true;
                                } else {
                                    return false;
                                }


                            } else {
                                return !$.mage.isEmpty(value);
                            }
                        },
                        $.mage.__('Please enter a valid phone number.')
                    );

                    $.validator.addMethod(
                        'required-entry-bcity',
                        function (value) {
                            if ($(".billing-address-form input[name='city']").val() != '') {
                                if (!isNaN(value)) {
                                    return false;
                                }
                                if (value.match(/^[a-zA-Z ]*$/)) {
                                    return true
                                } else {
                                    return false;
                                }
                            } else {
                                return !$.mage.isEmpty(value);
                            }
                        },
                        $.mage.__('Please enter a valid city')
                    );

                    /* Start shipping and billing street address validation */
                    $.validator.addMethod(
                        'required-entry-street-0',
                        function (value) {
                            if ($("input[name='street[0]']").val() != '') {
                                if (!isNaN(value)) {
                                    return false;
                                }
                                if (/^([a-zA-Z0-9()":;'-.]+ )+[A-Za-z0-9()":;'-.]+$|^[A-Za-z0-9()":;'-.]*$/.test(value)) {
                                    return true
                                } else {
                                    return false;
                                }

                            } else {
                                return !$.mage.isEmpty(value);
                            }
                        },
                        $.mage.__('Please enter a valid street address.')
                    );

                    // If user leaves a given required field, validate that field.
                    $('body').on('blur', "#firstName, #lastName, #email, #comment, #topic, #address, #city, #00N3t00000GW27z, #postalCode, #grassType, #phone", function () {
                        if ($.validator.validateSingleElement($(this))) {
                            $(this).removeAttr('aria-invalid');
                        }
                    });

                    $(document).on(
                        "submit", "#contact-form", function () {
                            if (self.form.productName) {
                                self.form.comment(self.form.productName() + ': ' + self.form.comment());
                            }
                        });

                }
            }
        );
    }
);
