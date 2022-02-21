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
 * Class DenyBuilder
 */
class DenyBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Const for <authReversal> XML node.
     *
     * @var string
     */
    const VOID_REQUEST_AUTH_NODE = 'authReversal';

    /**
     * Const for <void> XML node.
     *
     * @var string
     */
    const VOID_REQUEST_SALE_NODE = 'void';

    /**
     * @var \Vantiv\Payment\Gateway\Common\Renderer\CcAuthReversalRenderer
     */
    private $ccAuthReversalRenderer;

    /**
     * @param SubjectReader $reader
     * @param CcAuthReversalRenderer $ccAuthReversalRenderer
     */
    public function __construct(
        SubjectReader $reader,
        CcAuthReversalRenderer $ccAuthReversalRenderer
    ) {
        parent::__construct($reader);
        $this->ccAuthReversalRenderer = $ccAuthReversalRenderer;
    }

    /**
     * Build <authReversal> or <void> XML node.
     *
     * <authReversal reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>TXN_ID</litleTxnId>
     * </authReversal>
     *      or
     * <void reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>TXN_ID</litleTxnId>
     * </void>
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
        $litleTxnId = $payment->getLastTransId() ?
            $payment->getLastTransId(): $this->getReader()->readTransactionId($subject);

        $data = [
            'reportGroup' => $method->getConfigData('report_group'),
            'customerId' => $this->getReader()->readOrderAdapter($subject)->getCustomerId(),
            'id' => $this->getId(),
            'litleTxnId' => $litleTxnId,
            'voidNodeName' => $this->getReader()->readVoidNode($subject)
        ];
        $data += $this->getAuthenticationData($subject);

        return $this->ccAuthReversalRenderer->render($data);
    }
}
