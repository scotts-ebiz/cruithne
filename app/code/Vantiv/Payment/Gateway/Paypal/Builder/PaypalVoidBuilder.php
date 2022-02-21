<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;

/**
 * Paypal void builder.
 */
class PaypalVoidBuilder extends AbstractPaymentRequestBuilder
{
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
    public function buildBody(array $subject)
    {
        $method = $this->getReader()->readPayment($subject)->getMethodInstance();

        /*
         * Prepare document variables.
         */
        $reportGroup = $method->getConfigData('report_group');
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
        $litleTxnId = $this->getReader()->readPayment($subject)->getParentTransactionId();

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('authReversal');
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }
        $writer->writeAttribute('reportGroup', $reportGroup);

        $writer->startElement('litleTxnId');
        $writer->text($litleTxnId);
        $writer->endElement();

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
