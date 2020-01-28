define([
    'Magento_Customer/js/customer-data',
    'jquery',
    'underscore'
], function (customerData, $, _) {
    'use strict';

    var lastPushedCart = {};
    var lastPushedCustomer = {};

    //check if object contain keys
    function objectKeyExist(object)
    {
        return _.some(object, function (o) {
            return !_.isEmpty(_.pick(o, ['customer', 'cart']));
        })
    }

    //Update datalayer
    function updateDataLayer(_gtmDataLayer, _dataObject, _forceUpdate)
    {
        var customer = {isLoggedIn : false},
            cart = {hasItems: false};



        if (_gtmDataLayer !== undefined && (!objectKeyExist(_gtmDataLayer) || _forceUpdate)) {
            if (_.isObject(_dataObject) && _.has(_dataObject, 'customer')) {
                customer = _dataObject.customer;
            }

            if (_.isObject(_dataObject) && _.has(_dataObject, 'cart')) {
                cart = _dataObject.cart;
            }

            if (!_.isEqual(lastPushedCart, cart) || !_.isEqual(lastPushedCustomer, customer)) {
                var actions = { remove: [], add: [], update: []};
                var coupons = [];


                if (lastPushedCart && lastPushedCart.items && lastPushedCart.items.length) {
                    if (!cart || !cart.hasItems) {
                        actions.remove = lastPushedCart.items;
                    } else {
                        // If both old cart and new have items, compare
                        var oldSkus = _.pluck(lastPushedCart.items, "sku");
                        var newSkus = _.pluck(cart.items, "sku");

                        var newItems = _.difference(newSkus, oldSkus);
                        var lostItems = _.difference(oldSkus, newSkus);
                        var inBoth = _.intersection(oldSkus, newSkus);

                        actions.remove = _.filter(lastPushedCart.items, function (i) { return _.contains(lostItems, i.sku); }) || [];
                        actions.add = _.filter(cart.items, function (i) { return _.contains(newItems, i.sku); }) || [];
                        actions.update = _.filter(cart.items, function(i) {
                                return _.contains(inBoth, i.sku) && i.quantity !== _.findWhere(lastPushedCart.items, { sku: i.sku }).quantity;
                            }) || [];
                        if (cart.hasCoupons) {
                            coupons.push({ couponCode: cart.couponCode });
                        }
                    }
                } else {
                    if (cart && cart.hasItems) {
                        actions.add = cart.items;
                    }
                }

                _gtmDataLayer.push({'event': 'mpCustomerSession', 'actions': actions, 'coupons': coupons, 'customer': customer, 'cart': cart});
                $('body').trigger('mpCustomerSession', [customer, cart, _gtmDataLayer, actions]);

                lastPushedCustomer = customer;
                lastPushedCart = cart;
                localStorage.setItem('launch-cart', JSON.stringify(cart));
            }

        }
    }

    function isTrackingAllowed(config)
    {
        var allowServices = false,
            allowedCookies,
            allowedWebsites;

        if (!config.isGdprEnabled) {
            allowServices = true;
        } else if (config.isCookieRestrictionModeEnabled && config.gdprOption === 1) {
            allowedCookies = $.mage.cookies.get(config.cookieName);

            if (allowedCookies !== null) {
                allowedWebsites = JSON.parse(allowedCookies);

                if (allowedWebsites[config.currentWebsite] === 1) {
                    allowServices = true;
                }
            }
        } else if (config.gdprOption === 2) {
            allowServices = $.mage.cookies.get(config.cookieName) !== null ? true : false;
        } else if (config.gdprOption === 3) {
            allowServices = $.mage.cookies.get(config.cookieName) === null ? true : false;
        }

        return allowServices;
    }

    //load gtm
    function initTracking(dataLayerName, accountId)
    {
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start':
                    new Date().getTime(), event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s), dl = l != dataLayerName ? '&l=' + l : '';
            j.async = true;
            j.src = '//www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', dataLayerName, accountId);
    }

    function pushData(dataLayerName, dataLayer)
    {
        if (_.isArray(dataLayer)) {
            _.each(dataLayer, function (data) {
                window[dataLayerName].push(data);
            });
        }
    }

    return function (config) {

        window[config.dataLayer] = window[config.dataLayer] || [];

        if (isTrackingAllowed(config)) {
            pushData(config.dataLayer, config.data);
            initTracking(config.dataLayer, config.accountId);
        }

        var cacheCart = JSON.parse(localStorage.getItem('launch-cart'));

        if (cacheCart) {
            lastPushedCart = JSON.parse(localStorage.getItem('launch-cart')) || '{}';
        }

        var dataObject = customerData.get('magepal-gtm-jsdatalayer');
        var gtmDataLayer = window[config.dataLayer];


        dataObject.subscribe(function (_dataObject) {
            updateDataLayer(gtmDataLayer, _dataObject, true);
        }, this);

        updateDataLayer(gtmDataLayer, dataObject(), false);
    }

});