<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

class DeleteDiscountSubscriptionBuilder extends UpdateSubscriptionBuilder
{
    /**
     * Build <deleteDiscount> inside <updateSubscription> body XML.
     *
     * <updateSubscription>
     *      <subscriptionId>subscription_id</subscriptionId>
     *      <deleteDiscount>
     *          <discountCode>discount_code</discountCode>
     *      </deleteDiscount>
     * </updateSubscription>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        $discount = $this->getReader()->readDiscount($subject);

        $writer = $this->initWriterAndRootNode();
        {
            $writer->writeElement('subscriptionId', $discount->getSubscription()->getVantivSubscriptionId());
            $this->buildDeleteDiscount($writer, $discount);
        }
        $writer->endElement();

        $xml = $writer->outputMemory();
        return $xml;
    }
}
