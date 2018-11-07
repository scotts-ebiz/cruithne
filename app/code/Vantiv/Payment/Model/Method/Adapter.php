<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Model\Method;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Adapter as PaymentAdapter;

class Adapter extends PaymentAdapter
{
    /**
     * @inheritdoc
     */
    public function isInitializeNeeded()
    {
        return $this->getInfoInstance()->getOrder()->getContainsVantivSubscription();
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $allowedActions = [
            AbstractMethod::ACTION_AUTHORIZE,
            AbstractMethod::ACTION_AUTHORIZE_CAPTURE
        ];

        if (!in_array($paymentAction, $allowedActions)) {
            return $this;
        }

        $payment = $this->getInfoInstance();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(\Vantiv\Payment\Helper\Recurring::PENDING_RECURRING_PAYMENT_ORDER_STATUS);

        foreach ($order->getAllItems() as $item) {
            if ($subscription = $item->getVantivSubscription()) {
                $payment->setSubscription($subscription);
                $this->authorize($payment, 0);
                $payment->unsSubscription();
            }
        }

        return $this;
    }
}
