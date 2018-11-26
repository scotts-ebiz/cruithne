<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Renderer;

use XMLWriter;

/**
 * PayPal XML node builder.
 */
class PaypalRenderer extends AbstractRenderer
{
    /**
     * Render PayPal XML node.
     *
     * <paypal>
     *     <payerId>PAYER_ID</payerId>
     *     <transactionId>TRANSACTION_ID</transactionId>
     * </paypal>
     *
     * @param array $subject
     * @return string
     */
    public function render(array $subject)
    {
        $paypalPayerId = $this->readDataOrNull($subject, 'payerId');
        $paypalTransactionId = $this->readDataOrNull($subject, 'transactionId');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('paypal');
        {
            $writer->startElement('payerId');
            $writer->text($paypalPayerId);
            $writer->endElement();

            $writer->startElement('transactionId');
            $writer->text($paypalTransactionId);
            $writer->endElement();
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
