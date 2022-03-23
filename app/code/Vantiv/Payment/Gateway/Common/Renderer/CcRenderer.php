<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * Credit card XML node builder.
 */
class CcRenderer extends AbstractRenderer
{
    /**
     * Render credit card XML node.
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $litleToken = $this->readDataOrNull($subject, 'litleToken');
        if ($litleToken !== null) {
            $xml = $this->renderToken($subject);
            return $xml;
        }

        $paypageRegistrationId = $this->readDataOrNull($subject, 'paypageRegistrationId');
        if ($paypageRegistrationId !== null) {
            $xml = $this->renderPaypage($subject);
            return $xml;
        }

        $xml = $this->renderCard($subject);
        return $xml;
    }

    /**
     * Build token XML node.
     *
     * @param array $subject
     * @return string
     */
    private function renderToken(array $subject)
    {
        $litleToken = $this->readDataOrNull($subject, 'litleToken');
        $expDate = $this->readDataOrNull($subject, 'expDate');
        $cardValidationNum = $this->readDataOrNull($subject, 'cardValidationNum');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('token');
        {
            $writer->writeElement('litleToken', $litleToken);

            if ($expDate !== null) {
                $writer->writeElement('expDate', $expDate);
            }

            if ($cardValidationNum !== null) {
                $writer->writeElement('cardValidationNum', $cardValidationNum);
            }
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * Render paypage XML node.
     *
     * @param array $subject
     * @return string
     */
    private function renderPaypage(array $subject)
    {
        $paypageRegistrationId = $this->readDataOrNull($subject, 'paypageRegistrationId');
        $expDate = $this->readDataOrNull($subject, 'expDate');
        $cardValidationNum = $this->readDataOrNull($subject, 'cardValidationNum');
        
        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('paypage');
        {
            $writer->writeElement('paypageRegistrationId', $paypageRegistrationId);
            $writer->writeElement('expDate', $expDate);
            $writer->writeElement('cardValidationNum', $cardValidationNum);
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * Render credit card XML node.
     *
     * @param array $subject
     * @return string
     */
    private function renderCard(array $subject)
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
        {
            $writer->writeElement('type', $type);
            $writer->writeElement('number', $number);
            $writer->writeElement('expDate', $expDate);
            if ($cardValidationNum !== null) {
                $writer->writeElement('cardValidationNum', $cardValidationNum);
            }
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
