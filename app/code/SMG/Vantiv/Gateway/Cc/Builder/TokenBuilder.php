<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace SMG\Vantiv\Gateway\Cc\Builder;

use Magento\Framework\App\Action\Context;
use Magento\Payment\Gateway\Http\ClientException;
use Psr\Log\LoggerInterface;
use SMG\Vantiv\Gateway\KeyPad\Config\VantivKeyPadConfig;
use SMG\Vantiv\Gateway\KeyPad\Parser\TokenResponseParserFactory;
use Vantiv\Payment\Gateway\Cc\Builder\PaypageBuilder;
use Vantiv\Payment\Gateway\Common\Client\HttpClient as Client;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use XMLWriter;

/**
 * Token XML node builder.
 */
class TokenBuilder extends \Vantiv\Payment\Gateway\Cc\Builder\TokenBuilder
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    protected $_reader;

    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var Client
     */
    protected $_client;

    /**
     * @var VantivKeyPadConfig
     */
    protected $_vantivKeyPadConfig;

    /**
     * @var TokenResponseParserFactory
     */
    protected $_tokenResponseParserFactory;

    public function __construct(LoggerInterface $logger,
        SubjectReader $reader,
        Context $context,
        Client $client,
        VantivKeyPadConfig $vantivKeyPadConfig,
        TokenResponseParserFactory $tokenResponseParserFactory)
    {
        $this->_logger = $logger;
        $this->_reader = $reader;
        $this->_context = $context;
        $this->_client = $client;
        $this->_vantivKeyPadConfig = $vantivKeyPadConfig;
        $this->_tokenResponseParserFactory = $tokenResponseParserFactory;
    }

    /**
     * Build <paypage> XML node.
     *
     * <token>
     *     <litleToken>TOKEN</litleToken>
     * </token>
     *
     * @param array $subject
     * @return string
     * @throws ClientException
     */
    public function build(array $subject)
    {
        // get the payment for getting details
        $payment = $this->_reader->readPayment($subject);

        // get the payment method for creating the XML
        $method = $payment->getMethodInstance();

        // get the token
        $token = $this->getTokenFromKeyPadEntry($method);

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('token');
        {
            $writer->startElement('litleToken');
            $writer->text($token);
            $writer->endElement();
        }
        $writer->endElement();
        $xml = $writer->outputMemory();

        return $xml;
    }

    /**
     * @param array $subject
     * @return array
     * @throws ClientException
     */
    public function extract(array $subject)
    {
        // get the payment for getting details
        $payment = $this->_reader->readPayment($subject);

        // get the payment method for creating the XML
        $method = $payment->getMethodInstance();

        // get the token
        $token = $this->getTokenFromKeyPadEntry($method);

        // get the date information
        $expMonth = $payment->getAdditionalInformation('exp_month');
        $expYear = $payment->getAdditionalInformation('exp_year');

        $expDate = null;
        if (isset($expMonth) && isset($expYear))
        {
            $expDate = $expMonth . $expYear;
        }

        $cardValidationNum = PaypageBuilder::CARD_VALIDATION_NUM;

        return [
            'litleToken'        => $token,
            'expDate'           => $expDate,
            'cardValidationNum' => $cardValidationNum
        ];
    }

    /**
     * This method makes the actual POST call to retrieve the token
     * for later use.
     *
     * @param \Magento\Payment\Model\MethodInterface $method
     * @return mixed
     * @throws ClientException
     */
    private function getTokenFromKeyPadEntry(\Magento\Payment\Model\MethodInterface $method)
    {
        $response = $this->_client->post([
            'url' => $this->_vantivKeyPadConfig->getUrlByEnvironment($method->getConfigData('environment')),
            'body' => $this->getTokenRequestBody($method),
            'debug' => $method->getConfigData('debug'),
            'http_timeout' => $method->getConfigData('http_timeout'),
            'http_proxy' => $method->getConfigData('http_proxy'),
        ]);

        /**
         * @var \SMG\Vantiv\Gateway\KeyPad\Parser\TokenResponseParser $tokenResponseParserFactory
         */
        $tokenResponseParserFactory = $this->_tokenResponseParserFactory->create(['xml' => $response]);

        // determine if the token request was succesful or not
        if (!$tokenResponseParserFactory->isSuccess())
        {
            throw new ClientException(__("Payment Authorization Declined."));
        }

        // return the token
        return $tokenResponseParserFactory->getToken();
    }

    /**
     * Create the XML for obtaining a token from the keypad
     *
     * @param \Magento\Payment\Model\MethodInterface $method
     * @return string
     */
    private function getTokenRequestBody(\Magento\Payment\Model\MethodInterface $method)
    {
        // get the config data for request
        $accountId = $method->getConfigData('account_id');
        $accountToken = $method->getConfigData('account_token');
        $acceptorId = $method->getConfigData('acceptor_id');
        $applicationId = $method->getConfigData('application_id');
        $applicationName = $method->getConfigData('application_name');
        $applicationVersion = $method->getConfigData('application_version');

        // get the request to get form data
        $request = $this->_context->getRequest();

        // get the form data
        $formatType = $request->getParam('payment-vantiv-keypadpayment-ecdata-type');
        $ecData = $request->getParam('payment-vantiv-keypadpayment-ecdata');
        $serialNumber = $request->getParam('payment-vantiv-keypadpayment-serial-number');

        /*
         * Generate document.
         */
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));
        $writer->startElement('TokenCreate');
        $writer->writeAttribute('xmlns', 'https://services.elementexpress.com');
        $writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        {
            $writer->startElement('Credentials');
            {
                $writer->startElement('AccountID');
                $writer->text($accountId);
                $writer->endElement();
                $writer->startElement('AccountToken');
                $writer->text($accountToken);
                $writer->endElement();
                $writer->startElement('AcceptorID');
                $writer->text($acceptorId);
                $writer->endElement();
            }
            $writer->endElement();

            $writer->startElement('Application');
            {
                $writer->startElement('ApplicationID');
                $writer->text($applicationId);
                $writer->endElement();
                $writer->startElement('ApplicationName');
                $writer->text($applicationName);
                $writer->endElement();
                $writer->startElement('ApplicationVersion');
                $writer->text($applicationVersion);
                $writer->endElement();
            }
            $writer->endElement();

            $writer->startElement('Card');
            {
                // the XML and Default formats required different XML tags
                if ($formatType === 'default')
                {
                    $writer->startElement('MagneprintData');
                    $writer->text($ecData);
                    $writer->endElement();
                }
                else if ($formatType === 'xml')
                {
                    $writer->startElement('EncryptedCardData');
                    $writer->text($ecData);
                    $writer->endElement();
                }
                else
                {
                    $this->_logger->error("The Input format was incorrect.");
                }

                $writer->startElement('CardDataKeySerialNumber');
                $writer->text($serialNumber);
                $writer->endElement();
                $writer->startElement('EncryptedFormat');
                $writer->text(4);
                $writer->endElement();
            }
            $writer->endElement();

            $writer->startElement('Token');
            {
                $writer->startElement('TokenProvider');
                $writer->text(2);
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();

        $xml = $writer->outputMemory();

        // return the XML
        return $xml;
    }
}
