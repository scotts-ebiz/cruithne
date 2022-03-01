<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

class DeleteAddonSubscriptionBuilder extends UpdateSubscriptionBuilder
{
    /**
     * Build <deleteAddOn> inside <updateSubscription> body XML.
     *
     * <updateSubscription>
     *      <subscriptionId>subscription_id</subscriptionId>
     *      <deleteAddOn>
     *          <addOnCode>addon_code</addOnCode>
     *      </deleteAddOn>
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
            $this->buildDeleteAddon($writer, $addon);
        }
        $writer->endElement();

        $xml = $writer->outputMemory();
        return $xml;
    }
}
