<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer\GiftCard;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Renderer\AbstractRenderer;

/**
 * Gift Card XML node builder.
 */
class GiftCardRenderer extends AbstractRenderer
{
    /**
     * Build <card> XML node.
     *
     * <card reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <type>CARD_TYPE</type>
     *     <number>CARD_NUMBER</number>
     *     <expDate>EXP_DATE</expDate>
     *     <cardValidationNum>CARD_VALIDATION_NUMBER</cardValidationNum>
     * </card>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $type = $this->readDataOrNull($subject, 'type');
        $number = $this->readDataOrNull($subject, 'number');
        $expDate = $this->readDataOrNull($subject, 'expDate');
        $cardValidationNum = $this->readDataOrNull($subject, 'cardValidationNum');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('card');

        $writer->writeElement('type', $type);
        $writer->writeElement('number', $number);
        if ($expDate !== null) {
            $writer->writeElement('expDate', $expDate);
        }
        if ($cardValidationNum !== null) {
            $writer->writeElement('cardValidationNum', $cardValidationNum);
        }

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
