<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer\GiftCard;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Renderer\AbstractRenderer;

/**
 * Virtual Gift Card XML node builder.
 */
class VirtualGiftCardRenderer extends AbstractRenderer
{
    /**
     * Build <virtualGiftCard> XML node.
     *
     * <virtualGiftCard reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <accountNumberLength>GIFT_CARD_NUMBER_LENGTH</accountNumberLength>
     *     <giftCardBin>GIFT_CARD_BIN_NUMBER</giftCardBin>
     * </virtualGiftCard>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $accountNumberLength = $this->readDataOrNull($subject, 'accountNumberLength');
        $giftCardBin = $this->readDataOrNull($subject, 'giftCardBin');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('virtualGiftCard');
        {
            $writer->writeElement('accountNumberLength', $accountNumberLength);
            $writer->writeElement('giftCardBin', $giftCardBin);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
