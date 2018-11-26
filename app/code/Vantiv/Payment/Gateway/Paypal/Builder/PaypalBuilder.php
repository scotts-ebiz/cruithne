<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\RequestBuilderInterface;
use Magento\Paypal\Model\Express\Checkout;

/**
 * Paypal XML node builder.
 */
class PaypalBuilder implements RequestBuilderInterface
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     */
    public function __construct(SubjectReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Build <paypal> XML node.
     *
     * <paypal>
     *     <payerId>PAYER_ID</payerId>
     *     <transactionId>TRANSACTION_ID</transactionId>
     * </paypal>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        /*
         * Prepare document variables.
         */

        $payment = $this->reader->readPayment($subject);
        $paypalPayerId = $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_PAYER_ID);
        $paypalTransactionId = $payment->getTransactionId();

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('paypal');

        $writer->startElement('payerId');
        $writer->text($paypalPayerId);
        $writer->endElement();

        $writer->startElement('transactionId');
        $writer->text($paypalTransactionId);
        $writer->endElement();

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
