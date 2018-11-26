<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Recurring\Order\Item;

use Vantiv\Payment\Model\Recurring\Subscription;
use Magento\Sales\Model\Order;

class ToSubscription
{
    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \Vantiv\Payment\Model\Recurring\SubscriptionFactory
     */
    private $subscriptionFactory;

    /**
     * @var \Vantiv\Payment\Model\Recurring\Subscription\AddonFactory
     */
    private $addonFactory;
    /**
     * @var \Vantiv\Payment\Model\Recurring\Subscription\DiscountFactory
     */
    private $discountFactory;

    /**
     * @var \Vantiv\Payment\Helper\Recurring
     */
    private $recurringHelper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory
     * @param \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory
     * @param \Vantiv\Payment\Model\Recurring\Subscription\AddonFactory $addonFactory
     * @param \Vantiv\Payment\Helper\Recurring $recurringHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Vantiv\Payment\Model\Recurring\SubscriptionFactory $subscriptionFactory,
        \Vantiv\Payment\Model\Recurring\Subscription\AddonFactory $addonFactory,
        \Vantiv\Payment\Model\Recurring\Subscription\DiscountFactory $discountFactory,
        \Vantiv\Payment\Helper\Recurring $recurringHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->objectCopyService = $objectCopyService;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->addonFactory = $addonFactory;
        $this->discountFactory = $discountFactory;
        $this->recurringHelper = $recurringHelper;
        $this->eventManager = $eventManager;
    }

    /**
     * Convert order/order item data to subscription
     *
     * @param Order\Item
     * @param Order $order
     * @param array $data
     * @return Subscription
     */
    public function convert(Order\Item $item, Order $order, $data = [])
    {
        $subscription = $this->subscriptionFactory->create();

        $subscriptionDataFromItem = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_order_item',
            'to_vantiv_subscription',
            $item
        );

        $subscriptionDataFromOrder = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_order',
            'to_vantiv_subscription',
            $order
        );

        $subscription->addData(array_merge(
            $subscriptionDataFromItem,
            $subscriptionDataFromOrder,
            $data
        ));

        $this->eventManager->dispatch(
            'sales_convert_order_item_to_vantiv_subscription',
            ['item' => $item, 'subscription' => $subscription]
        );

        $plan = $this->recurringHelper->getOrderItemPlan($item);
        $subscription->setPlan($plan);

        if (!$subscription->hasPlanId()) {
            $subscription->setPlanId($plan->getId());
        }

        if (!$subscription->hasStartDate()
            && ($startDate = $this->recurringHelper->getOrderItemSubscriptionStartDate($item))
        ) {
            $subscription->setStartDate($startDate);
        }

        if (!$subscription->hasPlanCode()) {
            $subscription->setPlanCode($plan->getCode());
        }

        if (!$subscription->hasIntervalAmount()) {
            $subscription->setIntervalAmount(
                (abs($plan->getIntervalAmount() - $order->getBaseTotalDue()) > 0.0001)
                    ? $order->getBaseTotalDue() : null
            );
        }

        if (!$subscription->hasBillingName() && ($billingAddress = $order->getBillingAddress())) {
            $subscription->setBillingName($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
        }

        if (!$subscription->hasShippingName() && ($shippingAddress = $order->getShippingAddress())) {
            $subscription->setShippingName($shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname());
        }

        if (!$subscription->hasStatus()) {
            $subscription->setStatus(\Vantiv\Payment\Model\Recurring\Source\SubscriptionStatus::ACTIVE);
        }

        $this->createAddonsAndDiscounts($subscription, $order);

        return $subscription;
    }

    /**
     * @param Subscription $subscription
     * @param Order $order
     */
    private function createAddonsAndDiscounts(Subscription $subscription, Order $order)
    {
        if ($subscription->getIntervalAmount() === null) {
            return;
        }

        $addonList = [];
        $addonsAmount = 0;
        $discountList = [];
        $discountsAmount = 0;

        $amount = $order->getBaseTaxAmount();
        if ($amount >= 0.01) {
            $addonList[] = $this->createAddon(
                $subscription,
                [
                    'code' => Subscription\Addon::TAX_CODE,
                    'name' => __('Tax'),
                    'amount' => $amount
                ]
            );
            $addonsAmount += $amount;
        }

        $amount = $order->getBaseShippingAmount();
        if ($amount >= 0.01) {
            $addonList[] = $this->createAddon(
                $subscription,
                [
                    'code' => Subscription\Addon::SHIPPING_CODE,
                    'name' => __('Shipping'),
                    'amount' => $amount
                ]
            );
            $addonsAmount += $amount;
        }

        $amount = abs($order->getBaseDiscountAmount());
        if ($amount >= 0.01) {
            $discountList[] = $this->createDiscount(
                $subscription,
                [
                    'code' => Subscription\Discount::DISCOUNT_CODE,
                    'name' => $order->getDiscountDescription()
                        ? __('Discount (%1)', $order->getDiscountDescription()) : Subscription\Discount::DISCOUNT_NAME,
                    'amount' => $amount
                ]
            );
            $discountsAmount += $amount;
        }

        $reconciliationAmount = $subscription->getIntervalAmount() - $subscription->getPlan()->getIntervalAmount()
            - $addonsAmount + $discountsAmount;
        if (abs($reconciliationAmount) >= 0.01) {
            if ($reconciliationAmount > 0) {
                $addonList[] = $this->createAddon(
                    $subscription,
                    [
                        'code' => Subscription\Addon::RECONCILIATION_CODE,
                        'name' => __('Reconciliation'),
                        'amount' => $reconciliationAmount
                    ]
                );
            } else {
                $discountList[] = $this->createDiscount(
                    $subscription,
                    [
                        'code' => Subscription\Discount::RECONCILIATION_CODE,
                        'name' => __('Reconciliation'),
                        'amount' => abs($reconciliationAmount)
                    ]
                );
            }
        }

        $subscription->setAddonList($addonList);
        $subscription->setDiscountList($discountList);
    }

    /**
     * @param Subscription $subscription
     * @param array $data
     * @return Subscription\Addon
     */
    private function createAddon(Subscription $subscription, $data = [])
    {
        /** @var Subscription\Addon $addon */
        $addon = $this->addonFactory->create();

        $addon->addData($data);
        $addon->setIsSystem(true);

        if ($subscription->getStartDate()) {
            $startDate = $subscription->getStartDate();
        } else {
            $startDate = date('Y-m-d');
        }
        $addon->setStartDate($startDate);

        $endDate = $this->recurringHelper->calculateEndDate($subscription);
        $addon->setEndDate($endDate);

        return $addon;
    }

    /**
     * @param Subscription $subscription
     * @param array $data
     * @return Subscription\Discount
     */
    private function createDiscount(Subscription $subscription, $data = [])
    {
        /** @var Subscription\Discount $discount */
        $discount = $this->discountFactory->create();

        $discount->addData($data);
        $discount->setIsSystem(true);

        if ($subscription->getStartDate()) {
            $startDate = $subscription->getStartDate();
        } else {
            $startDate = date('Y-m-d');
        }
        $discount->setStartDate($startDate);

        $endDate = $this->recurringHelper->calculateEndDate($subscription);
        $discount->setEndDate($endDate);

        return $discount;
    }
}
