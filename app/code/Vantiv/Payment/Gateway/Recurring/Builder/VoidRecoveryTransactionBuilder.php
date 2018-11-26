<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Gateway\Recurring\Builder;

use XMLWriter;

/**
 * Class VoidBuilder
 */
class VoidRecoveryTransactionBuilder extends AbstractSubscriptionRequestBuilder
{
    /**
     * Build <void> XML node.
     *
     * <void reportGroup="REPORT_GROUP">
     *     <litleTxnId>TXN_ID</litleTxnId>
     * </void>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        $recoveryTransaction = $this->getReader()->readRecoveryTransaction($subject);

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('void');

        $writer->writeAttribute('reportGroup', $recoveryTransaction->getReportGroup());
        {
            $writer->writeElement('litleTxnId', $recoveryTransaction->getLitleTxnId());
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
