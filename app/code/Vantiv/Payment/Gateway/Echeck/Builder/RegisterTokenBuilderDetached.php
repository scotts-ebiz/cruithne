<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\Builder\AbstractCustomRequestBuilder;

/**
 * Register token request builder class.
 */
class RegisterTokenBuilderDetached extends AbstractCustomRequestBuilder
{
    /**
     * Build <registerTokenRequest> XML node.
     *
     * <registerTokenRequest customerId="CUSTOMER_ID" reportGroup="ORDER_SOURCE">
     *     <echeckForToken>
     *         <accNum>ACCOUNT_NUMBER</accNum>
     *         <routingNum>ROUTING_NUMBER</routingNum>
     *     </echeckForToken>
     * </registerTokenRequest>
     *
     * @param array $subject
     * @return string
     */
    public function buildBody(array $subject)
    {
        /*
         * Preapre request data.
         */
        $reportGroup = $this->getPaymentMethodInstance($subject)->getConfigData('report_group');
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();

        $payment = $this->getReader()->readPayment($subject);
        $accountNumber = $payment->getData('echeck_account_name');
        $routingNumber = $payment->getData('echeck_routing_number');

        /*
         * Generate XML document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('registerTokenRequest');
        if ($customerId) {
            $writer->writeAttribute('customerId', $customerId);
        }
        $writer->writeAttribute('reportGroup', $reportGroup);
        {
            $writer->startElement('echeckForToken');
            {
                $writer->startElement('accNum');
                $writer->text($accountNumber);
                $writer->endElement();

                $writer->startElement('routingNum');
                $writer->text($routingNumber);
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * Read API merchant ID.
     *
     * @param array $subject
     * @return string
     */
    protected function readMerchant(array $subject)
    {
        return $this->getPaymentMethodInstance($subject)->getConfigData('merchant_id');
    }

    /**
     * Read API user.
     *
     * @param array $subject
     * @return string
     */
    protected function readUsername(array $subject)
    {
        return $this->getPaymentMethodInstance($subject)->getConfigData('username');
    }

    /**
     * Read API password.
     *
     * @param array $subject
     * @return string
     */
    protected function readPassword(array $subject)
    {
        return $this->getPaymentMethodInstance($subject)->getConfigData('password');
    }

    /**
     * Get Payment method instance.
     *
     * @param array $subject
     * @return \Magento\Payment\Model\MethodInterface
     */
    private function getPaymentMethodInstance(array $subject)
    {
        return $this->getReader()->readPayment($subject)->getMethodInstance();
    }
}
