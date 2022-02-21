/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Vault/js/view/payment/vault-enabler'
    ],
    function (Component, $, VaultEnabler) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Vantiv_Payment/payment/vantiv-echeck',
                echeckAccountType: '',
                echeckAccountNumber: '',
                echeckRoutingNumber: ''
            },

            initialize: function() {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode('vantiv_echeck_vault');

                return this;
            },

            getData: function () {
                var data = {
                    "method": this.item.method,
                    "additional_data": {
                        "echeck_account_type": this.echeckAccountType(),
                        "echeck_account_name": this.echeckAccountNumber(),
                        "echeck_routing_number": this.echeckRoutingNumber()
                    }
                };

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            },

            /**
             * Check if vault is enabled.
             *
             * @returns {Bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            initObservable: function () {
                this._super().observe([
                    'echeckAccountType',
                    'echeckAccountNumber',
                    'echeckRoutingNumber'
                ]);

                return this;
            },

            getEcheckAccountTypesValues: function () {
                return [
                    {
                        'value': 'Checking',
                        'label': 'Checking'
                    },
                    {
                        'value': 'Savings',
                        'label': 'Savings'
                    },
                    {
                        'value': 'Corporate',
                        'label': 'Corporate Checking'
                    },
                    {
                        'value': 'Corp Savings',
                        'label': 'Corporate Savings'
                    }
                ];
            },

            validate: function () {
                var form = 'form[data-role=vantiv-echeck-form]';
                return $(form).validation() && $(form).validation('isValid');
            }
        });
    }
);
