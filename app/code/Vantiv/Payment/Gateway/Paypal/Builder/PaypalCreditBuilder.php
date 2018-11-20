<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;

/**
 * Paypal credit builder.
 */
class PaypalCreditBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Build <credit> XML node.
     *
     * <credit reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>TRANSACTION_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     * </credit>
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
        $amount = $this->getReader()->readAmount($subject) * 100;
        $litleTxnId = $this->getReader()->readPayment($subject)->getParentTransactionId();

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('credit');
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
