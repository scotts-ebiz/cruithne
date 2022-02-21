<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductSaveBeforeObserver implements ObserverInterface
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     */
    public function __construct(\Vantiv\Payment\Helper\Recurring $recurringHelper)
    {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Set has_options if subscriptions are enabled for product
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Vantiv\Payment\Observer\CatalogProductSaveBeforeObserver
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if (in_array($product->getTypeId(), $this->recurringHelper->getAllowedProductTypeIds())
            && $product->getVantivRecurringEnabled()
        ) {
            $product->setHasOptions(true);
        }

        return $this;
    }
}
