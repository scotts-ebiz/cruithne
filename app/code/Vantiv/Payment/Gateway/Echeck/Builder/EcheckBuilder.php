<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Builder\RequestBuilderInterface;

/**
 * Echeck XML node builder.
 */
class EcheckBuilder implements RequestBuilderInterface
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     */
    public function __construct(SubjectReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get subject reader.
     *
     * @return SubjectReader
     */
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Build <echeck> or <echeckToken> XML node.
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        $xml = '';

        $litleToken = $this->getReader()
            ->readPayment($subject)
            ->getAdditionalInformation('litle_token');

        if (!empty($litleToken)) {
            $xml = $this->buildToken($subject);
        } else {
            $xml = $this->buildEcheck($subject);
        }

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
    private function buildEcheck(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);

        $echeckAccountType = $payment->getEcheckAccountType();
        $echeckRoutingNumber = $payment->getEcheckRoutingNumber();

        $encryptedAccountNumber = $payment->getAdditionalInformation('encrypted_account_number');
        $echeckAccountNumber = empty($encryptedAccountNumber)
            ? $payment->getEcheckAccountName()
            : $payment->decrypt($encryptedAccountNumber);

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('echeck');
        {
            $writer->startElement('accType');
            $writer->text($echeckAccountType);
            $writer->endElement();

            $writer->startElement('accNum');
            $writer->text($echeckAccountNumber);
            $writer->endElement();

            $writer->startElement('routingNum');
            $writer->text($echeckRoutingNumber);
            $writer->endElement();
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
    private function buildToken(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);

        $echeckAccountType = $payment->getEcheckAccountType();
        $echeckRoutingNumber = $payment->getEcheckRoutingNumber();
        $litleToken = $payment->getAdditionalInformation('litle_token');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('echeckToken');
        {
            $writer->startElement('litleToken');
            $writer->text($litleToken);
            $writer->endElement();

            $writer->startElement('routingNum');
            $writer->text($echeckRoutingNumber);
            $writer->endElement();

            $writer->startElement('accType');
            $writer->text($echeckAccountType);
            $writer->endElement();
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }
}
