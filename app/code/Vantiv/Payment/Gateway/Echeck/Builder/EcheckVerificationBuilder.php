<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\BillToAddressBuilder;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;

/**
 * Echeck verification request builder.
 */
class EcheckVerificationBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Address builder.
     *
     * @var BillToAddressBuilder
     */
    private $billToAddressBuilder = null;

    /**
     * Echeck node builder.
     *
     * @var EcheckBuilder
     */
    private $echeckBuilder = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param BillToAddressBuilder $billToAddressBuilder
     * @param EcheckBuilder $echeckBuilder
     */
    public function __construct(
        SubjectReader $reader,
        BillToAddressBuilder $billToAddressBuilder,
        EcheckBuilder $echeckBuilder
    ) {
        parent::__construct($reader);

        $this->billToAddressBuilder = $billToAddressBuilder;
        $this->echeckBuilder = $echeckBuilder;
    }

    /**
     * Build <echeckVerification> XML node.
     *
     * <echeckVerification reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <BILLING_NODE/>
     *     <ECHECK_NODE/>
     * </echeckVerification>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        $reader = $this->getReader();

        /*
         * Prepare document variables.
         */
        $reportGroup = $reader->readPayment($subject)->getMethodInstance()->getConfigData('report_group');
        $customerId = $reader->readOrderAdapter($subject)->getCustomerId();
        $orderIncrementId = $reader->readOrderAdapter($subject)->getOrderIncrementId();
        $amount = $reader->readAmount($subject) * 100;

        /*
         * Prepare children documents.
         */
        $billToAddress = $this->billToAddressBuilder->build($subject);
        $echeck = $this->echeckBuilder->build($subject);

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('echeckVerification');
        $writer->writeAttribute('reportGroup', $reportGroup);
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }

        $writer->startElement('orderId');
        $writer->text($orderIncrementId);
        $writer->endElement();

        $writer->startElement('amount');
        $writer->text($amount);
        $writer->endElement();

        $writer->startElement('orderSource');
        $writer->text('ecommerce');
        $writer->endElement();

        $writer->writeRaw($billToAddress);
        $writer->writeRaw($echeck);

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
