<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;

/**
 * Echeck credit (refund) request builder.
 */
class EcheckCreditBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Build <echeckCredit> XML node.
     *
     * <echeckCredit reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>TRANSACTION_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     * </echeckCredit>
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
        $amount = $this->getReader()->readAmount($subject) * 100;

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('echeckCredit');
        $writer->writeAttribute('reportGroup', $reportGroup);
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }

        $writer->startElement('litleTxnId');
        $writer->text($litleTxnId);
        $writer->endElement();

        $writer->startElement('amount');
        $writer->text($amount);
        $writer->endElement();

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
