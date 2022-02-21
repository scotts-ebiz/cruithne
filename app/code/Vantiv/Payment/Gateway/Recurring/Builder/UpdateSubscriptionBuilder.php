<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

use XMLWriter;

class UpdateSubscriptionBuilder extends AbstractSubscriptionRequestBuilder
{
    /**
     * Build <updateSubscription> body XML
     *
     * Most fields optional.
     * Can only choose one of <paypage> or <token>
     *
     * <updateSubscription>
     *      <subscriptionId>Subscription Id</subscriptionId>
     *      <planCode>New Plan Code</planCode>
     *      <billToAddress>
     *          <name>Customer’s Full Name</name>
     *          <firstName>Customer's First Name</firstName>
     *          <lastName>Customer's Last Name</lastName>
     *          <addressLine1>Address Line 1</addressLine1>
     *          <city>City</city>
     *          <state>State Abbreviation</state>
     *          <zip>Postal Code</zip>
     *          <country>Country Code</country>
     *          <email>Email Address</email>
     *          <phone>Telephone Number</phone>
     *      </billToAddress>
     *      <paypage>
     *          <paypageRegistrationId>Registration ID from PayPage</paypageRegistrationId>
     *      </paypage>
     *      <token>
     *          <litleToken>Token</litleToken>
     *      </token>
     * </updateSubscription>
     *
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        /** @var \Vantiv\Payment\Model\Recurring\Subscription $subscription */
        $subscription = $this->getReader()->readSubscription($subject);

        $writer = $this->initWriterAndRootNode();

        $writer->writeElement('subscriptionId', $subscription->getVantivSubscriptionId());

        if ($subscription->dataHasChangedFor('plan_id')) {
            $writer->writeElement('planCode', $subscription->getPlan()->getCode());
        }

        if ($subscription->getBillingAddress()) {
            $this->buildBillToAddress($writer, $subscription->getBillingAddress());
        }

        foreach ($subscription->getDiscountList() as $discount) {
            if (!$discount->getId()) {
                $this->buildCreateUpdateDiscount($writer, $discount);
            } elseif ($discount->isDeleted()) {
                $this->buildDeleteDiscount($writer, $discount);
            } elseif ($discount->hasDataChanges()) {
                $this->buildCreateUpdateDiscount($writer, $discount, true);
            }
        }

        foreach ($subscription->getAddonList() as $addon) {
            if (!$addon->getId()) {
                $this->buildCreateUpdateAddon($writer, $addon);
            } elseif ($addon->isDeleted()) {
                $this->buildDeleteAddon($writer, $addon);
            } elseif ($addon->hasDataChanges()) {
                $this->buildCreateUpdateAddon($writer, $addon, true);
            }
        }

        if ($subscription->getData('paypage_registration_id')) {
            $writer->startElement('paypage');

            $writer->writeElement('paypageRegistrationId', $subscription->getData('paypage_registration_id'));

            $writer->endElement();
        } elseif ($subscription->getData('token')) {
            $writer->startElement('token');

            $writer->writeElement('litleToken', $subscription->getData('token'));

            $writer->endElement();
        }

        $writer->endElement();

        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * Initialize writer and request XML root node
     *
     * @return XMLWriter
     */
    protected function initWriterAndRootNode()
    {
        $writer = new XMLWriter();

        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startElement('updateSubscription');

        return $writer;
    }
}
