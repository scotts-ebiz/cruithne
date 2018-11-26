<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Observer\CcDataAssignObserver;
use Vantiv\Payment\Gateway\Common\Builder\RequestBuilderInterface;

/**
 * Paypage XML node builder.
 */
class PaypageBuilder implements RequestBuilderInterface
{
    /**
     * Card's CVV
     *
     * @var string
     */
    const CARD_VALIDATION_NUM = '000';

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
     * Get SubjectReader.
     *
     * @return SubjectReader
     */
    private function getReader()
    {
        return $this->reader;
    }

    /**
     * Build <paypage> XML node.
     *
     * <paypage>
     *     <paypageRegistrationId>PAYPAGE_REGISTRATION_ID</paypageRegistrationId>
     *     <expDate>EXP_DD_YY</expDate>
     *     <type>CARD_TYPE</type>
     * </paypage>
     *
     * @param array $subject
     * @return string
     */
    public function build(array $subject)
    {
        /*
         * Prepare document variables.
         */
        $payment = $this->getReader()->readPayment($subject);
        $paypageRegistrationId = $payment->getAdditionalInformation(CcDataAssignObserver::PAYPAGE_KEY);

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('paypage');
        {
            $writer->startElement('paypageRegistrationId');
            $writer->text($paypageRegistrationId);
            $writer->endElement();
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * @param array $subject
     * @return array
     */
    public function extract(array $subject)
    {
        $paypageRegistrationId = $this->getReader()->readPayment($subject)->getAdditionalInformation(
            \Vantiv\Payment\Observer\CcDataAssignObserver::PAYPAGE_KEY
        );
        $expMonth = $this->getReader()->readPayment($subject)->getAdditionalInformation(
            CcDataAssignObserver::CCEXPMONTH_KEY
        );
        $expYear = $this->getReader()->readPayment($subject)->getAdditionalInformation(
            CcDataAssignObserver::CCEXPYEAR_KEY
        );

        return [
            'paypageRegistrationId' => $paypageRegistrationId,
            'expDate' => $expMonth . $expYear,
            'cardValidationNum' => self::CARD_VALIDATION_NUM
        ];
    }
}
