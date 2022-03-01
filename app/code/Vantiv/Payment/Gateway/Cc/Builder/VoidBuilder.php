<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Renderer\CcAuthReversalRenderer;

/**
 * Class VoidBuilder
 */
class VoidBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * @var CcAuthReversalRenderer
     */
    private $ccAuthReversalRenderer = null;

    /**
     * @param SubjectReader $reader
     * @param CcAuthReversalRenderer $ccAuthReversalRenderer
     */
    public function __construct(SubjectReader $reader, CcAuthReversalRenderer $ccAuthReversalRenderer)
    {
        parent::__construct($reader);
        $this->ccAuthReversalRenderer = $ccAuthReversalRenderer;
    }

    /**
     * Build <authReversal> XML node.
     *
     * <authReversal reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>TXN_ID</litleTxnId>
     * </authReversal>
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
        $reportGroup = $method->getConfigData('report_group');
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
        $litleTxnId = $payment->getParentTransactionId();

        $data = [
            'reportGroup' => $reportGroup,
            'customerId' => $customerId,
            'id' => $this->getId(),
            'litleTxnId' => $litleTxnId,
        ];
        $data += $this->getAuthenticationData($subject);

        return $this->ccAuthReversalRenderer->render($data);
    }
}
