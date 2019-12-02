define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/redirect-on-success',
        'domReady!',
    ],
    function($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SMG_SubscriptionApi/payment/recurly'
            },

            initialize: function() {
                this._super();
                
                setTimeout(function() {
                    recurly.configure('ewr1-aefvtq9Ri3MILWsXFPHyv2');
                }, 2000)
            },

            createNewSubscription: function(token_id, cancel_existing) {
                event.preventDefault();
                var self = this;
                var form = document.querySelector('.recurly-form');
                var quiz = window.sessionStorage.getItem('quiz');
                quiz = JSON.parse(quiz);
                var subscriptionPlan = window.sessionStorage.getItem('subscription_plan');

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + '/rest/V1/subscription/create',
                    dataType: 'json',
                    contentType: 'application/json',
                    processData: false,
                    data: JSON.stringify( {
                        'token': token_id,
                        'quiz': quiz,
                        'plan': subscriptionPlan,
                        'cancel_existing': cancel_existing,
                    } ),
                    success: function(response) {
                        if( response[0].success == true ) {
                            self.createNewOrders();
                        } else {
                            alert( response[0].message );
                        }
                    }
                });
            },

            createNewOrders: function() {
                event.preventDefault();
                var self = this;
                var formKey = document.querySelector('input[name=form_key]').value;
                var quiz = window.sessionStorage.getItem('quiz');
                quiz = JSON.parse(quiz);

                $.ajax({
                    type: 'POST',
                    url: window.location.origin + '/rest/V1/subscription/createorders',
                    dataType: 'json',
                    contentType: 'application/json',
                    processData: false,
                    data: JSON.stringify( { 'key': formKey, 'quiz_id': quiz.id }),
                    success: function(response) {
                        if( response[0].success == true ) {
                            window.location.href = '/thank-you';
                        }
                    }
                })
            },
 
            myPlaceOrder: function() {
                event.preventDefault();
                var self = this;
                var form = document.querySelector('.recurly-form');

                recurly.token(form, function(err, token) {
                    if( err ) {
                        console.log( err );
                    } else {
                        $.ajax({
                            type: 'POST',
                            url: window.location.origin + '/rest/V1/subscription/check',
                            dataType: 'json',
                            contentType: 'application/json',
                            processData: false,
                            success: function(response) {
                                if( response[0].success === false && response[0].has_subscription === true ) {
                                    if( confirm( response[0].message ) ) {
                                        self.createNewSubscription(token.id, true);
                                    } else {
                                        window.location.href = response[0].redirect_url
                                    }
                                } else {
                                    self.createNewSubscription(token.id, false);
                                }
                            }
                        });
                    }
                })
            },
 
 
        });
    }

);