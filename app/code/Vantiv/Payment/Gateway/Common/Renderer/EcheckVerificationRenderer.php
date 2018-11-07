<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Echeck verification request renderer.
 */
class EcheckVerificationRenderer extends AbstractRenderer
{
    /**
     * Request wrapper.
     *
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper = null;

    /**
     * Billing address renderer.
     *
     * @var BillToAddressRenderer
     */
    private $billToAddressRenderer = null;

    /**
     * Echeck node renderer.
     *
     * @var EcheckRenderer
     */
    private $echeckRenderer = null;

    /**
     * Constructor.
     *
     * @param LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     * @param BillToAddressRenderer $billToAddressRenderer
     * @param EcheckRenderer $echeckRenderer
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        BillToAddressRenderer $billToAddressRenderer,
        EcheckRenderer $echeckRenderer
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
        $this->billToAddressRenderer = $billToAddressRenderer;
        $this->echeckRenderer = $echeckRenderer;
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
    public function render(array $subject)
    {
        /*
         * Prepare document variables.
         */
        $merchantId = $this->readDataOrNull($subject, 'merchantId');
        $user = $this->readDataOrNull($subject, 'user');
        $password = $this->readDataOrNull($subject, 'password');

        $reportGroup = $this->readDataOrNull($subject, 'reportGroup');
        $customerId = $this->readDataOrNull($subject, 'customerId');
        $id = $this->readDataOrNull($subject, 'id');
        $orderId = $this->readDataOrNull($subject, 'orderId');
        $amount = $this->readDataOrNull($subject, 'amount');
        $orderSource = $this->readDataOrNull($subject, 'orderSource');

        /*
         * Prepare children documents.
         */
        $billToAddress = $this->billToAddressRenderer->render($this->readDataOrNull($subject, 'billToAddress'));
        $echeck = $this->echeckRenderer->render($subject);

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
        if ($id) {
            $writer->writeAttribute('id', $id);
        }
        {
            $writer->writeElement('orderId', $orderId);
            $writer->writeElement('amount', $amount);
            $writer->writeElement('orderSource', $orderSource);

            $writer->writeRaw($billToAddress);
            $writer->writeRaw($echeck);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
