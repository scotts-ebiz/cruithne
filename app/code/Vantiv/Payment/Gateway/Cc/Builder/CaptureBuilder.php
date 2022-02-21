<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\EnhancedDataBuilder;
use Vantiv\Payment\Gateway\Common\Renderer\CcCaptureRenderer;

/**
 * Capture (Credit Card) request builder.
 */
class CaptureBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Enhanced data builder
     *
     * @var EnhancedDataBuilder
     */
    private $enhancedDataBuilder = null;

    /**
     * @var CcCaptureRenderer
     */
    private $ccCaptureRenderer;

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
     * @param CcCaptureRenderer $ccCaptureRenderer
     */
    public function __construct(
        SubjectReader $reader,
        EnhancedDataBuilder $enhancedDataBuilder,
        CcCaptureRenderer $ccCaptureRenderer
    ) {
        parent::__construct($reader);

        $this->enhancedDataBuilder = $enhancedDataBuilder;
        $this->ccCaptureRenderer = $ccCaptureRenderer;
    }

    /**
     * Build <capture> XML node.
     *
     * <capture reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID" partial="IS_PARTIAL">
     *     <litleTxnId>TRANSACTION_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     * </capture>
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
        $data = [
            'reportGroup' => $method->getConfigData('report_group'),
            'customerId' => $this->getReader()->readOrderAdapter($subject)->getCustomerId(),
            'id' => $this->getId(),
            'partial' => $payment->getBaseAmountAuthorized() > $this->getReader()->readAmount($subject),
            'litleTxnId' => $payment->getParentTransactionId(),
            'amount' => $this->getReader()->readAmount($subject) * 100,
        ];
        $data += $this->getAuthenticationData($subject);
        $data += $this->getEnhancedDataBuilder()->extract($subject);

        return $this->ccCaptureRenderer->render($data);
    }
}
