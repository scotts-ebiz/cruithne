<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Echeck XML node builder.
 */
class EcheckRenderer extends AbstractRenderer
{
    /**
     * Render <echeck> or <echeckToken> XML node.
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $litleToken = $this->readDataOrNull($subject, 'litleToken');

        $xml = ($litleToken !== null)
            ? $this->renderToken($subject)
            : $this->renderEcheck($subject);

        return $xml;
    }

    /**
     * Build <echeck> XML node.
     *
     * <echeck>
     *     <accType>ACCOUNT_TYPE</accType>
     *     <accNum>ACCOUNT_NUMBER</accNum>
     *     <routingNum>ROUTING_NUMBER</routingNum>
     * </echeck>
     *
     * @param array $subject
     * @return string
     */
    private function renderEcheck(array $subject)
    {
        $accType = $this->readDataOrNull($subject, 'accType');
        $accNum = $this->readDataOrNull($subject, 'accNum');
        $routingNum = $this->readDataOrNull($subject, 'routingNum');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('echeck');
        {
            $writer->writeElement('accType', $accType);
            $writer->writeElement('accNum', $accNum);
            $writer->writeElement('routingNum', $routingNum);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * Build <echeckToken> XML node.
     *
     * <echeckToken>
     *     <accType>ACCOUNT_TYPE</accType>
     *     <litleToken>LITLE_TOKEN</litleToken>
     *     <routingNum>ROUTING_NUMBER</routingNum>
     * </echeckToken>
     *
     * @param array $subject
     * @return string
     */
    private function renderToken(array $subject)
    {
        $litleToken = $this->readDataOrNull($subject, 'litleToken');
        $routingNum = $this->readDataOrNull($subject, 'routingNum');
        $accType = $this->readDataOrNull($subject, 'accType');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('echeckToken');
        {
            $writer->writeElement('litleToken', $litleToken);
            $writer->writeElement('routingNum', $routingNum);
            $writer->writeElement('accType', $accType);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
