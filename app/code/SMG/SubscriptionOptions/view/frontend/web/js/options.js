define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    return Component.extend({
        hasResults: ko.observable(false),
        result: ko.observable(null),

        initialize() {
            this.getResult();
        },

		proceedToCheckout() {
			const subscriptionPlan = $('input[name="subscription_plan"]:checked').val();
			const addonProducts = $('input[name="addon_products"]:checked').map(function() { return this.value }).get();
            const formKey = document.querySelector('input[name=form_key]').value;

			if (! subscriptionPlan) {
				alert('You must select a subscription plan.');
			}

			const self = this;

			$.ajax(
                `/rest/V1/subscription/process`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({
                        key: formKey,
                        subscription_plan: subscriptionPlan,
                        data: self.result(),
                        addons: addonProducts
                    }),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        data = JSON.parse(data);

                        if (data.success === true) {
                            localStorage.setItem('estimated_arrival', data.estimated_arrival);
                            window.sessionStorage.setItem('subscription_plan', subscriptionPlan );
                        	window.location.href = '/checkout/#shipping';
                        } else {
                            alert( 'Error creating your order ' + data.message + '. Please try again.' );
                        }
                    },
                },
            );
        },

        getCompletedQuiz(id) {
            const self = this;
            var formKey = document.querySelector('input[name=form_key]').value;

            $.ajax(
                `/rest/V1/recommendations/quiz/result`,
                {
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ key: formKey, id: id }),
                    dataType: 'json',
                    method: 'post',
                    success(data) {
                        if (data.error_message) {
                            alert( 'Error getting quiz data: ' + data.error_message + '. Please try again.');
                        } else {
                            if (Array.isArray(data)) {
                                data = data[0];
                            }

                            self.hasResults(true);
                            self.results(data);
                        }
                    },
                },
            );
        },

        /**
         * Load the quiz from the session storage.
         */
        getResult() {
            const result = window.sessionStorage.getItem('result');

            if (result && JSON.parse(result)) {
                this.hasResults(true);
                this.result(JSON.parse(result));
            } else {
                // Quiz not found, need to redirect.
                window.location.href = '/quiz';
            }

            window.sessionStorage.removeItem('result');
        },
    });
});
