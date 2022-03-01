<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * CreateDiscount Node renderer.
 */
class CreateDiscountRenderer extends AbstractRenderer
{
    /**
     * Build <createDiscount> XML node.
     *
     *  <createDiscount>
     *      <discountCode>Discount Reference Code</addOnCode>
     *      <name>Name of Discount</name>
     *      <amount>Amount of Discount</amount>
     *      <startDate>Start Date of Discount</startDate>
     *      <endDate>End Date of Discount</endDate>
     *  </createDiscount>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('createDiscount');

        $this->addSimpleNode($writer, 'discountCode', $subject, true);
        $this->addSimpleNode($writer, 'name', $subject, true);
        $this->addSimpleNode($writer, 'amount', $subject, true);
        $this->addSimpleNode($writer, 'startDate', $subject, true);
        $this->addSimpleNode($writer, 'endDate', $subject, true);

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
