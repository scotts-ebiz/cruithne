/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'mageUtils',
        'uiRegistry',
        'Firebear_ImportExport/js/form/element/additional-select',
        'uiLayout'
    ],
    function ($, _, utils, registry, Abstract, layout) {
        'use strict';

        return Abstract.extend(
            {
                defaults: {
                    code: '',
                    imports: {
                        changeOptions: '${$.parentName}.use_api:value'
                    },
                    apiOptions: null
                },
                initialize: function () {
                    this._super();
                    var elements = this.getOption(this.value());
                    if (elements != undefined) {
                        this.setCode(elements.code);
                    }

                    return this;
                },
                initObservable: function () {
                    this._super();

                    this.observe('code');

                    return this;
                },
                setCode: function (value) {
                    this.code(value);
                    /* Hidding extra fields when entity is CART PRICE RULE (POSTBACK) */
                    var entityValue = value;
                    setTimeout(function () {
                        $('.admin__field').each(function () {
                            var dataIndex = $(this).data('index');
                            if (dataIndex == 'category_levels_separator') {
                                if (entityValue == 'cart_price_rule_behavior' || entityValue == 'order_behavior')
                                    $(this).css('display', 'none');
                                else
                                    $(this).css('display', 'block');
                            }
                            if (dataIndex == 'categories_separator') {
                                if (entityValue == 'cart_price_rule_behavior' || entityValue == 'order_behavior')
                                    $(this).css('display', 'none');
                                else
                                    $(this).css('display', 'block');
                            }
                        });
                    }, 3000);
                },

                onUpdate: function () {
                    this._super();
                    var map = registry.get(this.ns + '.' + this.ns + '.source_data_map_container.source_data_map');
                    var mapCategory = registry.get(this.ns + '.' + this.ns + '.source_data_map_container_category.source_data_categories_map');
                    var removeMapping = registry.get(this.ns + '.' + this.ns + '.source.remove_current_mappings');
                    if (removeMapping !== undefined && removeMapping.value() == 1) {
                        map.deleteRecords();
                    }
                    map._updateCollection();
                    mapCategory.deleteRecords();
                    mapCategory._updateCollection();
                    registry.get(this.ns + '.' + this.ns + '.source.check_button').showMap(0);
                    if (this.value() == undefined) {
                        this.setCode('');
                    } else {
                        var elements = this.getOption(this.value());
                        this.setCode(elements.code);
                    }
                    /* Hidding extra fields when entity CART PRICE RULE is selected*/
                    var entityValue = this.value();
                    $('.admin__field').each(function () {
                        var dataIndex = $(this).data('index');
                        if (dataIndex == 'category_levels_separator') {
                            if (entityValue == 'cart_price_rule' || entityValue == 'order')
                                $(this).css('display', 'none');
                            else
                                $(this).css('display', 'block');
                        }
                        if (dataIndex == 'categories_separator') {
                            if (entityValue == 'cart_price_rule' || entityValue == 'order')
                                $(this).css('display', 'none');
                            else
                                $(this).css('display', 'block');
                        }
                        if (dataIndex == 'clear_attribute_value') {
                            if (entityValue == 'catalog_product')
                                $(this).css('display', 'block');
                            else
                                $(this).css('display', 'none');
                        }
                        if (dataIndex == 'remove_product_association') {
                            if (entityValue == 'catalog_product')
                                $(this).css('display', 'block');
                            else
                                $(this).css('display', 'none');
                        }
                        if (dataIndex === 'remove_product_website') {
                            if (entityValue === 'catalog_product')
                                $(this).css('display', 'block');
                            else
                                $(this).css('display', 'none');
                        }
                        if (dataIndex === 'remove_product_categories') {
                            if (entityValue === 'catalog_product')
                                $(this).css('display', 'block');
                            else
                                $(this).css('display', 'none');
                        }
                        if (dataIndex == 'root_category_id') {
                            if (entityValue == 'catalog_product')
                                $(this).css('display', 'block');
                            else
                                $(this).css('display', 'none');
                        }
                    });
                },
                changeOptions : function (value) {
                    if (this.apiOptions == null) {
                        this.apiOptions = [];
                        this.apiOptions.push(this.getOption('catalog_product'));
                    }
                    if (value === "1") {
                        this.setOptions(this.apiOptions);
                        this.value('catalog_product');
                    } else {
                        this.setOptions(this.initialOptions);
                    }
                }
            }
        );
    }
);