<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckoutCartUpdateItemsAfterObserver implements ObserverInterface
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     */
    public function __construct(
        \Vantiv\Payment\Helper\Recurring $recurringHelper
    ) {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Force subscription items qty to 1
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getCart()->getQuote();
        $info = $observer->getEvent()->getInfo();

        foreach ($info->getData() as $itemId => $itemInfo) {
            $item = $quote->getItemById($itemId);
            if ($this->recurringHelper->getSelectedPlan($item->getProduct())) {
                $item->setQty(1);
            }
        }
    }
}
