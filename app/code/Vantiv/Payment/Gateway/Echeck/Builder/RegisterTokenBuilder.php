<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\AbstractCustomRequestBuilder;
use Vantiv\Payment\Gateway\Echeck\Config\VantivEcheckConfig;

/**
 * Register token request builder class.
 */
class RegisterTokenBuilder extends AbstractCustomRequestBuilder
{
    /**
     * Payment configuration instance.
     *
     * @var VantivEcheckConfig
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param VantivEcheckConfig $config
     */
    public function __construct(
        SubjectReader $reader,
        VantivEcheckConfig $config
    ) {
        parent::__construct($reader);

        $this->config = $config;
    }

    /**
     * Get payment configuration instance.
     *
     * @return VantivEcheckConfig
     */
    private function getConfig()
    {
        return $this->config;
    }

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
        $reportGroup = $this->getConfig()->getValue('report_group');
        $customerId = $this->getReader()->readPaymentToken($subject)->getCustomerId();

        $accountNumber = isset($subject['account_number'])
            ? $subject['account_number']
            : null;

        $routingNumber = isset($subject['routing_number'])
            ? $subject['routing_number']
            : null;

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
        return $this->getConfig()->getValue('merchant_id');
    }

    /**
     * Read API user.
     *
     * @param array $subject
     * @return string
     */
    protected function readUsername(array $subject)
    {
        return $this->getConfig()->getValue('username');
    }

    /**
     * Read API password.
     *
     * @param array $subject
     * @return string
     */
    protected function readPassword(array $subject)
    {
        return $this->getConfig()->getValue('password');
    }
}
