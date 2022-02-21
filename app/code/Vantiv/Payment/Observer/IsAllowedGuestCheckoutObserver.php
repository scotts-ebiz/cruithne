<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;

class IsAllowedGuestCheckoutObserver implements ObserverInterface
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    protected $recurringHelper;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     */
    public function __construct(\Vantiv\Payment\Helper\Recurring $recurringHelper)
    {
        $this->recurringHelper = $recurringHelper;
    }

    /**
     * Restrict guest checkout if quote contain subscription product
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result = $observer->getEvent()->getResult();

        if ($this->recurringHelper->quoteContainsSubscription($observer->getEvent()->getQuote())) {
            $result->setIsAllowed(false);
        }

        return $this;
    }
}
