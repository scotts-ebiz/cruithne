<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer\GiftCard;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Renderer\LitleOnlineRequestWrapper;
use Vantiv\Payment\Gateway\Common\Renderer\AbstractRenderer;

/**
 * Gift Card UnloadReversal request renderer.
 */
class GiftCardUnloadReversalRenderer extends AbstractRenderer
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
     * Build <unloadReversal> XML node.
     *
     * <unloadReversal reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>UNLOAD_TRANSACTION_ID</orderId>
     * </unloadReversal>
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

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('unloadReversal');
        $writer->writeAttribute('reportGroup', $reportGroup);
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }
        if ($id) {
            $writer->writeAttribute('id', $customerId);
        }
        {
            $writer->writeElement('litleTxnId', $litleTxnId);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
