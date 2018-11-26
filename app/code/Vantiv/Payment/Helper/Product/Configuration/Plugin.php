<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vantiv\Payment\Helper\Product\Configuration;

class Plugin
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * Plugin constructor.
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     */
    public function __construct(\Vantiv\Payment\Helper\Recurring $recurringHelper)
    {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Retrieve configuration options for configurable product
     *
     * @param \Magento\Catalog\Helper\Product\Configuration $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetCustomOptions(
        \Magento\Catalog\Helper\Product\Configuration $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
    ) {
        $product = $item->getProduct();

        if (in_array($product->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())) {
            $subscriptionOptions = array_merge(
                $this->recurringHelper->getSelectedPlanOptionInfo($product),
                $this->recurringHelper->getSelectedPlanStartDateOptionInfo($product)
            );
            return array_merge($subscriptionOptions, $proceed($item));
        }
        return $proceed($item);
    }
}
