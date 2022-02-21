<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

use XMLWriter;

class RecurringRequestBuilder extends AbstractSubscriptionRequestBuilder
{
    /**
     * @var \Vantiv\Payment\Gateway\Recurring\Builder\CreateAddonSubscriptionBuilder
     */
    private $createAddonSubscriptionBuilder;

    /**
     * @var CreateDiscountSubscriptionBuilder
     */
    private $createDiscountSubscriptionBuilder;

    /**
     * @param \Vantiv\Payment\Gateway\Recurring\SubjectReader $reader
     * @param \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $config
     * @param CreateAddonSubscriptionBuilder $createAddonSubscriptionBuilder
     * @param CreateDiscountSubscriptionBuilder $createDiscountSubscriptionBuilder
     */
    public function __construct(
        \Vantiv\Payment\Gateway\Recurring\SubjectReader $reader,
        \Vantiv\Payment\Gateway\Recurring\Config\VantivSubscriptionConfig $config,
        CreateAddonSubscriptionBuilder $createAddonSubscriptionBuilder,
        CreateDiscountSubscriptionBuilder $createDiscountSubscriptionBuilder
    ) {
        parent::__construct($reader, $config);
        $this->createAddonSubscriptionBuilder = $createAddonSubscriptionBuilder;
        $this->createDiscountSubscriptionBuilder = $createDiscountSubscriptionBuilder;
    }

    /**
     * Build <recurringRequest> XML node.
     *
     * <recurringRequest>
     *     <subscription>
     *         <planCode>Plan Code</planCode>
     *         <numberOfPayments>Number of Payments</numberOfPayments>
     *         <startDate>Start Date (YYYY-MM-DD)</startDate>
     *         <amount>Amount</amount>
     *     </subscription>
     * </recurringRequest>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        /** @var \Vantiv\Payment\Model\Recurring\Subscription $subscription */
        $subscription = $this->getReader()->readPaymentDataObject($subject)->getPayment()->getSubscription();

        if (!$subscription) {
            return '';
        }

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('recurringRequest');

        $writer->startElement('subscription');

        $writer->writeElement('planCode', $subscription->getPlanCode());

        if ($subscription->hasData('number_of_payments')) {
            $writer->writeElement('numberOfPayments', $subscription->getNumberOfPayments());
        }

        if ($subscription->hasData('start_date')) {
            $writer->writeElement('startDate', $subscription->getStartDate());
        }

        foreach ($subscription->getDiscountList() as $discount) {
            $this->buildCreateUpdateDiscount($writer, $discount);
        }

        foreach ($subscription->getAddonList() as $addon) {
            $this->buildCreateUpdateAddon($writer, $addon);
        }

        $writer->endElement();

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * @param array $subject
     * @throws \Exception
     * @return string
     */
    public function buildBody(array $subject)
    {
        throw new \Exception('Cannot be used as a standalone request');
    }

    /**
     * @param array $subject
     * @return array
     */
    public function extract(array $subject)
    {
        /** @var \Vantiv\Payment\Model\Recurring\Subscription $subscription */
        $subscription = $this->getReader()->readPaymentDataObject($subject)->getPayment()->getSubscription();

        if (!$subscription) {
            return [];
        }

        $data = [
            'planCode' => $subscription->getPlanCode(),
        ];
        if ($subscription->hasData('number_of_payments')) {
            $data['numberOfPayments'] = $subscription->getNumberOfPayments();
        }

        if ($subscription->hasData('start_date')) {
            $data['startDate'] = $subscription->getStartDate();
        }

        if ($subscription->getDiscountList()) {
            $data['createDiscountCollection'] = [];
            foreach ($subscription->getDiscountList() as $discount) {
                $item = [
                    'discountCode' => $discount->getCode(),
                    'name' => $discount->getName(),
                    'amount' => number_format(
                        $discount->getAmount() * 100,
                        0,
                        null,
                        ''
                    ),
                    'startDate' => $discount->getStartDate(),
                    'endDate' => $discount->getEndDate(),
                ];
                $data['createDiscountCollection'][] = $item;
            }
        }

        if ($subscription->getAddonList()) {
            $data['createAddOnCollection'] = [];
            foreach ($subscription->getAddonList() as $addOn) {
                $item = [
                    'addOnCode' => $addOn->getCode(),
                    'name' => $addOn->getName(),
                    'amount' => number_format(
                        $addOn->getAmount() * 100,
                        0,
                        null,
                        ''
                    ),
                    'startDate' => $addOn->getStartDate(),
                    'endDate' => $addOn->getEndDate(),
                ];
                $data['createAddOnCollection'][] = $item;
            }
        }

        return ['recurringRequest' => $data];
    }
}
