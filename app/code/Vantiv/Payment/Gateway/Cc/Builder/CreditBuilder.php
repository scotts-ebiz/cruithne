<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\EnhancedDataBuilder;
use Vantiv\Payment\Gateway\Common\Renderer\CcCreditRenderer;

/**
 * Credit (refund) request builder.
 */
class CreditBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Enhanced data builder
     *
     * @var EnhancedDataBuilder
     */
    private $enhancedDataBuilder = null;

    private $ccCreditRenderer = null;

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
     * Constructor
     *
     * @param SubjectReader $reader
     * @param EnhancedDataBuilder $enhancedDataBuilder
     * @param CcCreditRenderer $ccCreditRenderer
     */
    public function __construct(
        SubjectReader $reader,
        EnhancedDataBuilder $enhancedDataBuilder,
        CcCreditRenderer $ccCreditRenderer
    ) {
        parent::__construct($reader);

        $this->enhancedDataBuilder = $enhancedDataBuilder;
        $this->ccCreditRenderer = $ccCreditRenderer;
    }

    /**
     * Build <credit> XML node.
     *
     * <credit reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>TRANSACTION_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     *     <enhancedData />
     * </credit>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();

        /*
         * Prepare document variables.
         */
        $data = [
            'reportGroup' => $method->getConfigData('report_group'),
            'customerId' => $this->getReader()->readOrderAdapter($subject)->getCustomerId(),
            'id' => $this->getId(),
            'litleTxnId' => $this->getReader()->readPayment($subject)->getParentTransactionId(),
            'amount' => $this->getReader()->readAmount($subject) * 100
        ];
        $data += $this->getAuthenticationData($subject);
        $data += $this->getEnhancedDataBuilder()->extract($subject);

        return $this->ccCreditRenderer->render($data);
    }
}
