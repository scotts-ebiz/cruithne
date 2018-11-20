<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Plugin\Model\Method;

use Magento\Vault\Model\Method\Vault as OriginalVault;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;
use Vantiv\Payment\Helper\Recurring;

/**
 * Class Vault
 */
class Vault
{
    /**
     * Implement initialize
     *
     * @param OriginalVault $subject
     * @param \Closure $proceed
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     *
     * @return bool
     */
    public function aroundInitialize(
        OriginalVault $subject,
        \Closure $proceed,
        $paymentAction,
        $stateObject
    ) {
        $allowedActions = [
            AbstractMethod::ACTION_AUTHORIZE,
            AbstractMethod::ACTION_AUTHORIZE_CAPTURE
        ];

        if (!in_array($paymentAction, $allowedActions)) {
            return $subject;
        }

        $payment = $subject->getInfoInstance();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Recurring::PENDING_RECURRING_PAYMENT_ORDER_STATUS);

        foreach ($order->getAllItems() as $item) {
            if ($subscription = $item->getVantivSubscription()) {
                $payment->setSubscription($subscription);
                $subject->authorize($payment, 0);
                $payment->unsSubscription();
            }
        }

        return $subject;
    }
}

