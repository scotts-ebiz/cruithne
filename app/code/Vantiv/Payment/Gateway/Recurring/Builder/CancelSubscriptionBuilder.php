<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

use XMLWriter;

class CancelSubscriptionBuilder extends AbstractSubscriptionRequestBuilder
{
    /**
     * Build <cancelSubscription> XML node.
     *
     * <cancelSubscription>
     *     <subscriptionId>subscription_id</subscriptionId>
     * </cancelSubscription>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        $subscription = $this->getReader()->readSubscription($subject);

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('cancelSubscription');
        {
            $writer->writeElement('subscriptionId', $subscription->getVantivSubscriptionId());
        }

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
