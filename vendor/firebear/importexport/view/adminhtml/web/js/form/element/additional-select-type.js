define(
    [
        'Magento_Ui/js/form/element/select'
    ],
    function (Element) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    imports          : {
                        toggleApi: '${$.ns}.${$.ns}.settings.use_api:value'
                    },
                    apiOptions: null
                },

                toggleApi: function (value) {
                    if (this.apiOptions == null) {
                        this.apiOptions = [];
                        this.apiOptions.push (this.getOption('json'));
                        this.apiOptions.push (this.getOption('xml'));
                    }
                    if (value === "1") {
                        this.setOptions(this.apiOptions);
                    } else {
                        this.setOptions(this.initialOptions);
                    }
                }
            }
        );
    }
);