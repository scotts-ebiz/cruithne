define(
    [
    'uiComponent',
    'ko',
    ], function (Component, ko) {
        return Component.extend(
            {
                initialize(config) {
                    var self = this;

                    self.subscriptionID = ko.observable(window.sessionStorage.getItem('subscription_id'));

                    // Clear out the subscription ID, we only needed it for this page.
                    window.sessionStorage.removeItem('subscription_id');

                    // There is no subscription ID, so redirect to the home page.
                    if (!self.subscriptionID()) {
                        window.location.href = '/';
                    }

                    // Grab order information.
                    const orderInfo = JSON.parse(window.sessionStorage.getItem('result'));

                    // Grab relevant plan product info if it exists.
                    var products = [];
                    if (orderInfo['plan']) {
                        ko.utils.arrayForEach(
                            orderInfo['plan']['coreProducts'], function (product, index) {
                                products.push(
                                    {
                                        sku: product['sku'],
                                        product_id: product['prodId'],
                                        order_id: self.subscriptionID(),
                                        applicationstatedate: product['applicationStartDate'],
                                        applicationenddate: product['applicationEndDate'],
                                        magento_store_view: config.storeName,
                                        product_order: index
                                    }
                                );
                            }
                        );

                        // Send order information to Zaius.
                        zaius.event(
                            'trigger', {
                                action: 'pseudo_order',
                                identifiers: {
                                    'email': config.customerEmail
                                },
                                data: products
                            }
                        );
                    }
                },
            }
        );
    }
);
