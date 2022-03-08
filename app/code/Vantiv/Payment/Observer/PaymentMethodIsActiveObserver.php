<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vantiv\Payment\Helper\Paypal\Shortcut\ValidatorPlugin as PaypalShortcutValidatorPlugin;

class PaymentMethodIsActiveObserver implements ObserverInterface
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Disable payment methods not suitable for subscriptions
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $result = $observer->getEvent()->getResult();
        if (!$result->getData('is_available')) {
            return;
        }

        if (!$quote) {
            if ($this->registry->registry(PaypalShortcutValidatorPlugin::IS_METHOD_AVAILABLE_REG_FLAG)) {
                $quote = $this->checkoutSession->getQuote();
            } else {
                return;
            }
        }

        $paymentMethod = $observer->getEvent()->getMethodInstance();
        if ($paymentMethod->getCode() != \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE
            && $this->recurringHelper->quoteContainsSubscription($quote)
        ) {
            $result->setData('is_available', (bool)$paymentMethod->getConfigData('can_use_for_vantiv_subscription'));
        }
    }
}
