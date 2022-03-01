<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

class UpdateDiscountSubscriptionBuilder extends UpdateSubscriptionBuilder
{
    /**
     * Build <updateDiscount> inside <updateSubscription> body XML.
     *
     * <updateSubscription>
     *      <subscriptionId>subscription_id</subscriptionId>
     *      <updateDiscount>
     *          <discountCode>discount_code</discountCode>
     *          <name>name</name>
     *          <amount>amount_in_cents<amount>
     *          <startDate>start_date</startDate>
     *          <endDate>end_date</endDate>
     *      </updateDiscount>
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
            $this->buildCreateUpdateDiscount($writer, $discount, true);
        }
        $writer->endElement();

        $xml = $writer->outputMemory();
        return $xml;
    }
}
