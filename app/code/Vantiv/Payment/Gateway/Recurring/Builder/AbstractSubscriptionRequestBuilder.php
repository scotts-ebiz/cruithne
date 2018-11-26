<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Recurring\Builder;

use Vantiv\Payment\Gateway\Recurring\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\AbstractLitleOnlineRequestBuilder;
use Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig;
use Vantiv\Payment\Model\Recurring\Subscription;

/**
 * Vantiv XML request builder.
 *
 * @api
 */
abstract class AbstractSubscriptionRequestBuilder extends AbstractLitleOnlineRequestBuilder
{
    /**
     * Subject reader instance.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Subscription configuration instance.
     *
     * @var VantivSubscriptionConfig
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param VantivSubscriptionConfig $config
     */
    public function __construct(
        SubjectReader $reader,
        VantivSubscriptionConfig $config
    ) {
        $this->reader = $reader;
        $this->config = $config;
    }

    /**
     * Get subject reader.
     *
     * @return SubjectReader
     */
    protected function getReader()
    {
        return $this->reader;
    }

    /**
     * Get configuration instance.
     *
     * @return VantivSubscriptionConfig
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Read API merchant ID.
     *
     * @param array $subject
     * @return string
     */
    protected function readMerchant(array $subject)
    {
        return $this->getConfig()->getValue('merchant_id');
    }

    /**
     * Read API user.
     *
     * @param array $subject
     * @return string
     */
    protected function readUsername(array $subject)
    {
        return $this->getConfig()->getValue('username');
    }

    /**
     * Read API password.
     *
     * @param array $subject
     * @return string
     */
    protected function readPassword(array $subject)
    {
        return $this->getConfig()->getValue('password');
    }

    /**
     * Build billToAddress node
     *
     * @param \XMLWriter $writer
     * @param Subscription\Address $address
     * @return $this
     */
    protected function buildBillToAddress(\XMLWriter $writer, Subscription\Address $address)
    {
        $fieldsToUpdate = [];

        if ($address->dataHasChangedFor('firstname') || $address->dataHasChangedFor('lastname')) {
            $fieldsToUpdate['name'] = $address->getFirstname() . ' ' . $address->getLastname();
        }

        if ($address->dataHasChangedFor('firstname')) {
            $fieldsToUpdate['firstName'] = $address->getFirstname();
        }

        if ($address->dataHasChangedFor('lastname')) {
            $fieldsToUpdate['lastName'] = $address->getLastname();
        }

        if ($address->dataHasChangedFor('street')) {
            $fieldsToUpdate['addressLine1'] = $address->getStreet();
        }

        if ($address->dataHasChangedFor('city')) {
            $fieldsToUpdate['city'] = $address->getCity();
        }

        if ($address->dataHasChangedFor('region')) {
            $fieldsToUpdate['state'] = $address->getRegion();
        }

        if ($address->dataHasChangedFor('postcode')) {
            $fieldsToUpdate['zip'] = $address->getPostcode();
        }

        if ($address->dataHasChangedFor('country_id')) {
            $fieldsToUpdate['country'] = $address->getCountryId();
        }

        if ($fieldsToUpdate) {
            $writer->startElement('billToAddress');
            foreach ($fieldsToUpdate as $fieldName => $fieldValue) {
                $writer->writeElement($fieldName, $fieldValue);
            }
            $writer->endElement();
        }

        return $this;
    }

    /**
     * Build createAddOn/updateAddOn node
     *
     * @param \XMLWriter $writer
     * @param Subscription\Addon $addon
     * @param bool $update
     * @return $this
     */
    protected function buildCreateUpdateAddon(\XMLWriter $writer, Subscription\Addon $addon, $update = false)
    {
        $parentElement = $update ? 'updateAddOn' : 'createAddOn';
        $writer->startElement($parentElement);
        {
            $writer->writeElement('addOnCode', $addon->getCode());
            $writer->writeElement('name', $addon->getName());

            /* Amount sent in cents */
            $amount = number_format(($addon->getAmount() * 100), 0, null, '');
            $writer->writeElement('amount', $amount);

            $writer->writeElement('startDate', $addon->getStartDate());
            $writer->writeElement('endDate', $addon->getEndDate());
        }
        $writer->endElement();

        return $this;
    }

    /**
     * Build deleteAddOn node
     *
     * @param \XMLWriter $writer
     * @param Subscription\Addon $addon
     * @return $this
     */
    protected function buildDeleteAddon(\XMLWriter $writer, Subscription\Addon $addon)
    {
        $writer->startElement('deleteAddOn');
        {
            $writer->writeElement('addOnCode', $addon->getCode());
        }
        $writer->endElement();

        return $this;
    }

    /**
     * @param \XMLWriter $writer
     * @param Subscription\Discount $discount
     * @param bool $update
     * @return $this
     */
    protected function buildCreateUpdateDiscount(\XMLWriter $writer, Subscription\Discount $discount, $update = false)
    {
        $parentElement = $update ? 'updateDiscount' : 'createDiscount';
        $writer->startElement($parentElement);
        {
            $writer->writeElement('discountCode', $discount->getCode());
            $writer->writeElement('name', $discount->getName());

            /* Amount sent in cents */
            $amount = number_format(($discount->getAmount() * 100), 0, null, '');
            $writer->writeElement('amount', $amount);

            $writer->writeElement('startDate', $discount->getStartDate());
            $writer->writeElement('endDate', $discount->getEndDate());
        }
        $writer->endElement();

        return $this;
    }

    /**
     * Build deleteDiscount node
     *
     * @param \XMLWriter $writer
     * @param Subscription\Discount $discount
     * @return $this
     */
    protected function buildDeleteDiscount(\XMLWriter $writer, Subscription\Discount $discount)
    {
        $writer->startElement('deleteDiscount');
        {
            $writer->writeElement('discountCode', $discount->getCode());
        }
        $writer->endElement();

        return $this;
    }
}
