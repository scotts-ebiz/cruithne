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
 * Gift Card Activate Virtual request renderer.
 */
class GiftCardActivateVirtualRenderer extends AbstractRenderer
{
    /**
     * Request wrapper.
     *
     * @var LitleOnlineRequestWrapper
     */
    private $litleOnlineRequestWrapper = null;

    /**
     * Virtual Card data renderer.
     *
     * @var VirtualGiftCardRenderer
     */
    private $virtualGiftCardRenderer = null;

    /**
     * Constructor.
     *
     * @param LitleOnlineRequestWrapper $litleOnlineRequestWrapper
     * @param VirtualGiftCardRenderer $virtualGiftCardRenderer
     */
    public function __construct(
        LitleOnlineRequestWrapper $litleOnlineRequestWrapper,
        VirtualGiftCardRenderer $virtualGiftCardRenderer
    ) {
        $this->litleOnlineRequestWrapper = $litleOnlineRequestWrapper;
        $this->virtualGiftCardRenderer = $virtualGiftCardRenderer;
    }

    /**
     * Build <activate> XML node.
     *
     * <activate reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <orderId>ORDER_INCREMENT_ID</orderId>
     *     <amount>AMOUNT</amount>
     *     <orderSource>ecommerce</orderSource>
     *     <VIRTUAL_GIFT_CARD_NODE/>
     * </activate>
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
        $virtualGiftCard = $this->virtualGiftCardRenderer->render($subject);

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('activate');
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

            $writer->writeRaw($virtualGiftCard);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        $xml = $this->litleOnlineRequestWrapper->wrap($xml, $merchantId, $user, $password);

        return $xml;
    }
}
