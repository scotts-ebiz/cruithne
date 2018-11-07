<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * DetailTax Node renderer.
 */
class DetailTaxRenderer extends AbstractRenderer
{
    /**
     * Build <detailTax> XML node.
     *
     * <detailTax>
     *     <taxIncludedInTotal>true or false</taxIncludedInTotal>
     *     <taxAmount>Additional Tax Amount</taxAmount>
     *     <taxRate>Tax Rate of This Tax Amount</taxRate>
     *     <taxTypeIdentifier>Tax Type Enum</taxTypeIdentifier>
     *     <cardAcceptorTaxId>Tax ID of Card Acceptor</cardAcceptorTaxId>
     * </detailTax>
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
        $writer->startElement('detailTax');

        $this->addSimpleNode($writer, 'taxIncludedInTotal', $subject);
        $this->addSimpleNode($writer, 'taxAmount', $subject, true);
        $this->addSimpleNode($writer, 'taxRate', $subject);
        $this->addSimpleNode($writer, 'taxTypeIdentifier', $subject);
        $this->addSimpleNode($writer, 'cardAcceptorTaxId', $subject);

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
