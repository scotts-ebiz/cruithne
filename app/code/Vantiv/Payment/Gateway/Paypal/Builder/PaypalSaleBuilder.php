<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Paypal\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\BillToAddressBuilder;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;

/**
 * Paypal sale builder.
 */
class PaypalSaleBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Address builder.
     *
     * @var BillToAddressBuilder
     */
    private $billToAddressBuilder = null;

    /**
     * Paypal node builder.
     *
     * @var PaypalBuilder
     */
    private $paypalBuilder = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param BillToAddressBuilder $billToAddressBuilder
     * @param PaypalBuilder $paypalBuilder
     */
    public function __construct(
        SubjectReader $reader,
        BillToAddressBuilder $billToAddressBuilder,
        PaypalBuilder $paypalBuilder
    ) {
        parent::__construct($reader);

        $this->billToAddressBuilder = $billToAddressBuilder;
        $this->paypalBuilder = $paypalBuilder;
    }

    /**
     * Build <sale> XML node.
     *
     * <sale reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <BILLING_NODE/>
     *     <PAYPAL_NODE/>
     *     <PAYPAL_ORDER_COMPLETE_NODE/>
     * </sale>
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
        $orderIncrementId = $this->getReader()->readOrderAdapter($subject)->getOrderIncrementId();
        $amount = $this->getReader()->readAmount($subject) * 100;

        /*
         * Prepare children documents.
         */
        $billToAddress = $this->billToAddressBuilder->build($subject);
        $paypal = $this->paypalBuilder->build($subject);

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('sale');
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
        $writer->writeRaw($paypal);

        $writer->startElement('payPalOrderComplete');
        $writer->text('true');
        $writer->endElement();

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
