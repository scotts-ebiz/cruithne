<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\BillToAddressBuilder;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;
use Vantiv\Payment\Gateway\Common\Builder\EnhancedDataBuilder;
use Vantiv\Payment\Gateway\Recurring\Builder\RecurringRequestBuilder;
use Vantiv\Payment\Gateway\Common\Renderer\CcSaleRenderer;

/**
 * Sale (Saved Credit Card) request builder.
 */
class VaultSaleBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Address builder.
     *
     * @var BillToAddressBuilder
     */
    private $billToAddressBuilder = null;

    /**
     * Token node builder.
     *
     * @var TokenBuilder
     */
    private $tokenBuilder = null;

    /**
     * Advanced fraud builder.
     *
     * @var AdvancedFraudChecksBuilder
     */
    private $advancedFraudChecksBuilder = null;

    /**
     * Enhanced data builder
     *
     * @var EnhancedDataBuilder
     */
    private $enhancedDataBuilder = null;

    /**
     * Recurring/Subscription request builder
     *
     * @var RecurringRequestBuilder
     */
    private $recurringRequestBuilder;

    /**
     * @var CcSaleRenderer
     */
    private $ccSaleRenderer;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param BillToAddressBuilder $billToAddressBuilder
     * @param TokenBuilder $tokenBuilder
     * @param AdvancedFraudChecksBuilder $advancedFraudChecksBuilder
     * @param EnhancedDataBuilder $enhancedDataBuilder
     * @param RecurringRequestBuilder $recurringRequestBuilder
     */
    public function __construct(
        SubjectReader $reader,
        BillToAddressBuilder $billToAddressBuilder,
        TokenBuilder $tokenBuilder,
        AdvancedFraudChecksBuilder $advancedFraudChecksBuilder,
        EnhancedDataBuilder $enhancedDataBuilder,
        RecurringRequestBuilder $recurringRequestBuilder,
        CcSaleRenderer $ccSaleRenderer
    ) {
        parent::__construct($reader);

        $this->billToAddressBuilder = $billToAddressBuilder;
        $this->tokenBuilder = $tokenBuilder;
        $this->advancedFraudChecksBuilder = $advancedFraudChecksBuilder;
        $this->enhancedDataBuilder = $enhancedDataBuilder;
        $this->recurringRequestBuilder = $recurringRequestBuilder;
        $this->ccSaleRenderer = $ccSaleRenderer;
    }

    /**
     * Get billing addres builder.
     *
     * @return BillToAddressBuilder
     */
    private function getBillToAddressBuilder()
    {
        return $this->billToAddressBuilder;
    }

    /**
     * Get token builder.
     *
     * @return TokenBuilder
     */
    private function getTokenBuilder()
    {
        return $this->tokenBuilder;
    }

    /**
     * Get advanced fraud builder.
     *
     * @return AdvancedFraudChecksBuilder
     */
    private function getAdvancedFraudChecksBuilder()
    {
        return $this->advancedFraudChecksBuilder;
    }

    /**
     * Get enhanced data builder
     *
     * @return EnhancedDataBuilder
     */
    private function getEnhancedDataBuilder()
    {
        return $this->enhancedDataBuilder;
    }

    /**
     * Get recurring/subscription request builder
     *
     * @return RecurringRequestBuilder
     */
    private function getRecurringRequestBuilder()
    {
        return $this->recurringRequestBuilder;
    }

    /**
     * Build <sale> XML node.
     *
     * <sale reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <BILLING_NODE/>
     *     <TOKEN_NODE/>
     *     <enhancedData />
     *     <recurringRequest />
     * </sale>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);
        $method = $payment->getMethodInstance();

        /*
         * Prepare document variables.
         */
        $orderAdapter = $this->getReader()->readOrderAdapter($subject);

        $data = [
            'reportGroup' => $method->getConfigData('report_group'),
            'customerId' => $orderAdapter->getCustomerId(),
            'id' => $this->getId(),
            'orderId' => $orderAdapter->getOrderIncrementId(),
            'amount' => $this->getReader()->readAmount($subject) * 100,
            'orderSource' => $method->getConfigData('order_source'),
        ];
        $data += $this->getAuthenticationData($subject);
        $data += $this->getBillToAddressBuilder()->extract($subject);
        $data += $this->getAdvancedFraudChecksBuilder()->extract($subject);
        $data += $this->getTokenBuilder()->extract($subject);
        $data += $this->getEnhancedDataBuilder()->extract($subject);
        $data += $this->getRecurringRequestBuilder()->extract($subject);

        return $this->ccSaleRenderer->render($data);
    }
}
