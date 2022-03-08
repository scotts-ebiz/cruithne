<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesServiceQuoteSubmitBeforeObserver implements ObserverInterface
{
    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Vantiv\Payment\Model\Recurring\SubscriptionFactory
     */
    private $orderItemToSubscription;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Order\Address\ToSubscriptionAddress
     */
    private $orderAddressToSubscriptionAddress;

    /**
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param \Vantiv\Payment\Model\Recurring\Order\Item\ToSubscription $orderItemToSubscription
     * @param \Vantiv\Payment\Model\Recurring\Order\Address\ToSubscriptionAddress $orderAddressToSubscriptionAddress
     */
    public function __construct(
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        \Vantiv\Payment\Model\Recurring\Order\Item\ToSubscription $orderItemToSubscription,
        \Vantiv\Payment\Model\Recurring\Order\Address\ToSubscriptionAddress $orderAddressToSubscriptionAddress
    ) {
        $this->recurringHelper = $recurringHelper;
        $this->orderItemToSubscription = $orderItemToSubscription;
        $this->orderAddressToSubscriptionAddress = $orderAddressToSubscriptionAddress;
    }

    /**
     * Set corresponding flag to order if quote contains subscription items
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        if ($this->recurringHelper->quoteContainsSubscription($quote)) {
            $order->setContainsVantivSubscription(true);
            foreach ($order->getAllItems() as $item) {
                if ($plan = $this->recurringHelper->getOrderItemPlan($item)) {

                    /** @var \Vantiv\Payment\Model\Recurring\Subscription $subscription */
                    $subscription = $this->orderItemToSubscription->convert($item, $order);

                    $this->convertAddresses($subscription, $order, $item);

                    $item->setVantivSubscription($subscription)
                        ->setLockedDoInvoice(true);
                }
            }
        }
    }

    /**
     * @param \Vantiv\Payment\Model\Recurring\Subscription $subscription
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Item $item
     */
    private function convertAddresses(
        \Vantiv\Payment\Model\Recurring\Subscription $subscription,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Item $item
    ) {
        $addresses = [];
        $billingAddress = $this->orderAddressToSubscriptionAddress->convert(
            $order->getBillingAddress(),
            ['address_type' => 'billing', 'email' => $order->getCustomerEmail()]
        );
        $addresses[] = $billingAddress;
        if (!$item->getIsVirtual()) {
            $shippingAddress = $this->orderAddressToSubscriptionAddress->convert(
                $order->getBillingAddress(),
                ['address_type' => 'shipping', 'email' => $order->getCustomerEmail()]
            );
            $addresses[] = $shippingAddress;
        }
        $subscription->setAddresses($addresses);
    }
}
