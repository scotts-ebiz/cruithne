<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;

/**
 * Paypal capture builder.
 */
class PaypalCaptureBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Build <capture> XML node.
     *
     * <capture reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>ORDER_INCREMENT_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <PAYPAL_ORDER_COMPLETE_NODE/>
     *     <PAYPAL_NODE/>
     * </authorization>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);
        $method = $payment->getMethodInstance();

        /*
         * Prepare document variables.
         */
        $reportGroup = $method->getConfigData('report_group');
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
        $originalAmount = $this->getReader()->readAmount($subject);
        $amount = $originalAmount * 100;

        $litleTxnId = $payment->getParentTransactionId();

        $payPalOrderComplete = $payment->isCaptureFinal($originalAmount);
        $isCapturePartial = $payment->getBaseAmountAuthorized() > $originalAmount;

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('capture');
        $writer->writeAttribute('reportGroup', $reportGroup);
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }
        if ($isCapturePartial) {
            $writer->writeAttribute('partial', 'true');
        }

        $writer->startElement('litleTxnId');
        $writer->text($litleTxnId);
        $writer->endElement();

        $writer->startElement('amount');
        $writer->text($amount);
        $writer->endElement();

        $writer->startElement('payPalOrderComplete');
        $writer->text($payPalOrderComplete ? 'true' : 'false');
        $writer->endElement();

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
