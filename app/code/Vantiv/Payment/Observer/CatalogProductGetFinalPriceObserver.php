<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductGetFinalPriceObserver implements ObserverInterface
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
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        $plan = $this->recurringHelper->getSelectedPlan($product);
        if ($plan) {
            $product->setFinalPrice($plan->getIntervalAmount());
        }

        return $this;
    }
}
