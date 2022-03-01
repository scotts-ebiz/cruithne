<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Credit card capture request renderer.
 */
class CcCaptureRenderer extends AbstractRenderer
{
    /**
     * Request wrapper.
     *
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper = null;

    /**
     * @var \Vantiv\Payment\Gateway\Common\Renderer\EnhancedDataRenderer
     */
    private $enhancedDataRenderer = null;

    /**
     * Constructor.
     *
     * @param LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        EnhancedDataRenderer $enhancedDataRenderer
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
        $this->enhancedDataRenderer = $enhancedDataRenderer;
    }

    /**
     * Build <capture> XML node.
     *
     * <capture reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>LITLE_TXN_ID</litleTxnId>
     *     <amount>AMOUNT</amount>
     *     <enhancedData />
     * </capture>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $reportGroup = $this->readDataOrNull($subject, 'reportGroup');
        $customerId = $this->readDataOrNull($subject, 'customerId');
        $id = $this->readDataOrNull($subject, 'id');
        $partial = $this->readDataOrNull($subject, 'partial');

        $enhancedData = $this->readDataOrNull($subject, 'enhancedData');

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
        if ($partial) {
            $writer->writeAttribute('partial', 'true');
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
