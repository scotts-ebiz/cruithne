<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Capture card transaction request renderer.
 */
class CcCreditRenderer extends AbstractRenderer
{
    /**
     * Request wrapper.
     *
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper = null;

    /**
     * @var enhancedDataRenderer
     */
    private $enhancedDataRenderer = null;

    /**
     * @param \Vantiv\Payment\Gateway\Common\Renderer\LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     * @param \Vantiv\Payment\Gateway\Common\Renderer\EnhancedDataRenderer $enhancedDataRenderer
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        EnhancedDataRenderer $enhancedDataRenderer
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
        $this->enhancedDataRenderer = $enhancedDataRenderer;
    }

    /**
     * Build <credit> XML node.
     *
     * <credit reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>LITLE_TXN_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     *     <enhancedData />
     * </credit>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $reportGroup = $this->readDataOrNull($subject, 'reportGroup');
        $customerId = $this->readDataOrNull($subject, 'customerId');
        $id = $this->readDataOrNull($subject, 'id');

        $enhancedData = $this->readDataOrNull($subject, 'enhancedData');

        /*
         * Generate XML document.
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
        if ($id) {
            $writer->writeAttribute('id', $id);
        }
        $this->addSimpleNode($writer, 'litleTxnId', $subject, true);
        $this->addSimpleNode($writer, 'amount', $subject);
        if ($enhancedData) {
            $writer->writeRaw($this->enhancedDataRenderer->render($enhancedData));
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        $merchantId = $this->readDataOrNull($subject, 'merchantId');
        $user = $this->readDataOrNull($subject, 'user');
        $password = $this->readDataOrNull($subject, 'password');
        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
