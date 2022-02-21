<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * CreateAddOn Node renderer.
 */
class CreateAddOnRenderer extends AbstractRenderer
{
    /**
     * Build <createAddOn> XML node.
     *
     *  <createAddOn>
     *      <addOnCode>Add On Reference Code</addOnCode>
     *      <name>Name of Add On</name>
     *      <amount>Amount of Add On</amount>
     *      <startDate>Start Date of Add On Charge</startDate>
     *      <endDate>End Date of Add On Charge</endDate>
     *  </createAddOn>
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
        $writer->startElement('createAddOn');

        $this->addSimpleNode($writer, 'addOnCode', $subject, true);
        $this->addSimpleNode($writer, 'name', $subject, true);
        $this->addSimpleNode($writer, 'amount', $subject, true);
        $this->addSimpleNode($writer, 'startDate', $subject, true);
        $this->addSimpleNode($writer, 'endDate', $subject, true);

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
