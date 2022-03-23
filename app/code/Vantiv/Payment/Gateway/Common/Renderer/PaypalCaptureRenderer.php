<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * PayPal capture request renderer.
 */
class PaypalCaptureRenderer extends AbstractRenderer
{
    /**
     * Request wrapper.
     *
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper = null;

    /**
     * Constructor.
     *
     * @param LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
    }

    /**
     * Build <capture> XML node.
     *
     * <capture reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>AUTH_TRANSACTION_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     *     <payPalOrderComplete>ORDER_COMPLETE_FLAG</payPalOrderComplete>
     * </capture>
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
        $litleTxnId = $this->readDataOrNull($subject, 'litleTxnId');
        $amount = $this->readDataOrNull($subject, 'amount');
        $payPalOrderComplete = $this->readDataOrNull($subject, 'payPalOrderComplete');

        /*
         * Generate XML document.
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
        if ($id) {
            $writer->writeAttribute('id', $id);
        }

        $writer->writeElement('litleTxnId', $litleTxnId);
        $writer->writeElement('amount', $amount);
        $writer->writeElement(
            'payPalOrderComplete',
            ($payPalOrderComplete !== null) ? $payPalOrderComplete : 'true'
        );

        $writer->endElement();
        $xml = $writer->outputMemory();

        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
