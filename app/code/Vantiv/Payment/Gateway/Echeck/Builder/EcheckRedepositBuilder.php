<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Echeck\SaleCommand;
use Vantiv\Payment\Gateway\Common\Builder\AbstractPaymentRequestBuilder;

/**
 * Echeck re-deposit request builder.
 */
class EcheckRedepositBuilder extends AbstractPaymentRequestBuilder
{
    /**
     * Build <echeckRedeposit> XML node.
     *
     * <echeckRedeposit reportGroup="REPORT_GROUP" customerId="CUSTOMER_ID">
     *     <litleTxnId>TRANSACTION_ID</litleTxnId>
     * </echeckRedeposit>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        /*
         * Prepare document variables.
         */
        $reportGroup = $this->getReader()->readPayment($subject)->getMethodInstance()->getConfigData('report_group');
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
        $litleTxnId = $this->getReader()->readPayment($subject)
            ->getAdditionalInformation(SaleCommand::SALE_TRANSACTION_KEY);

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('echeckRedeposit');
        $writer->writeAttribute('reportGroup', $reportGroup);
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }

        $writer->startElement('litleTxnId');
        $writer->text($litleTxnId);
        $writer->endElement();

        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
