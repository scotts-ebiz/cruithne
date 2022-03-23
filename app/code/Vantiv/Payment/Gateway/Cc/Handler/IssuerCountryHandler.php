<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Handler;

use Vantiv\Payment\Model\Config\Source\SuspectIssuerAction;
use Magento\Directory\Model\CountryFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;

/**
 * Handle <issuerCountry> response data.
 */
class IssuerCountryHandler
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Country model factory.
     *
     * @var CountryFactory
     */
    private $countryFactory = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param CountryFactory $countryFactory
     */
    public function __construct(
        SubjectReader $reader,
        CountryFactory $countryFactory
    ) {
        $this->reader = $reader;
        $this->countryFactory = $countryFactory;
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
     * Get country model factory.
     *
     * @return CountryFactory
     */
    private function getCountryFactory()
    {
        return $this->countryFactory;
    }

    /**
     * Set credit card data into payment.
     *
     * @throws CommandException
     * @param array $subject
     * @param Parser $parser
     * @return boolean
     */
    public function handle(array $subject, Parser $parser)
    {
        $payment = $this->getReader()->readPayment($subject);
        $result = true;

        $issuerCountry = $parser->getIssuerCountry();
        if (!empty($issuerCountry)) {
            $issuerCountry = $this->convertCountry($issuerCountry);

            $suspectIssuerCountryString = $payment->getMethodInstance()->getConfigData('suspect_issuer_country');
            $suspectIssuerCountry = explode(',', $suspectIssuerCountryString);
            $suspectIssuerAction =  $payment->getMethodInstance()->getConfigData('suspect_issuer_action');
            if (in_array($issuerCountry, $suspectIssuerCountry)) {
                switch ($suspectIssuerAction) {
                    case SuspectIssuerAction::REJECT_CODE:
                        $result = false;
                        break;
                    case SuspectIssuerAction::ACCEPT_CODE:
                        $payment->setIsFraudDetected(true);
                        break;
                }
            }

            $payment->setAdditionalInformation('issuer_country', $issuerCountry);
        }

        return $result;
    }

    /**
     * Convert ISO3 country code to ISO2 country code.
     *
     * @param string $code
     * @return string
     */
    private function convertCountry($code)
    {
        $country = $this->getCountryFactory()->create();
        $country->loadByCode($code);
        return $country->getData('iso2_code');
    }
}
