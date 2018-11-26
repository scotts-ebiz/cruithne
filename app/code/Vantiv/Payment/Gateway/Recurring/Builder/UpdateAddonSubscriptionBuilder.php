<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

class UpdateAddonSubscriptionBuilder extends UpdateSubscriptionBuilder
{
    /**
     * Build <updateAddOn> inside <updateSubscription> body XML.
     *
     * <updateSubscription>
     *      <subscriptionId>subscription_id</subscriptionId>
     *      <updateAddOn>
     *          <addOnCode>addon_code</addOnCode>
     *          <name>name</name>
     *          <amount>amount_in_cents<amount>
     *          <startDate>start_date</startDate>
     *          <endDate>end_date</endDate>
     *      </updateAddOn>
     * </updateSubscription>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        $addon = $this->getReader()->readAddon($subject);

        $writer = $this->initWriterAndRootNode();
        {
            $writer->writeElement('subscriptionId', $addon->getSubscription()->getVantivSubscriptionId());
            $this->buildCreateUpdateAddon($writer, $addon, true);
        }
        $writer->endElement();

        $xml = $writer->outputMemory();
        return $xml;
    }
}
