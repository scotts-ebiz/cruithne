<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Builder;

use XMLWriter;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Vantiv\Payment\Gateway\Common\Builder\RequestBuilderInterface;

/**
 * Token XML node builder.
 */
class TokenBuilder implements RequestBuilderInterface
{
    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Token manager.
     *
     * @var PaymentTokenManagementInterface
     */
    private $tokenManager = null;

    /**
     * Constructor.
     *
     * @param SubjectReader $reader
     * @param PaymentTokenManagementInterface $tokenManager
     */
    public function __construct(
        SubjectReader $reader,
        PaymentTokenManagementInterface $tokenManager
    ) {
        $this->reader = $reader;
        $this->tokenManager = $tokenManager;
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
     * Get token manager.
     *
     * @return PaymentTokenManagementInterface
     */
    private function getTokenManager()
    {
        return $this->tokenManager;
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
     */
    public function build(array $subject)
    {
        /*
         * Prepare document variables.
         */
        $payment = $this->getReader()->readPayment($subject);
        $publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
        $token = $this->getTokenManager()->getByPublicHash($publicHash, $customerId);
        $tokenValue = $token->getGatewayToken();

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
            $writer->text($tokenValue);
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
        $payment = $this->getReader()->readPayment($subject);
        $publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
        $token = $this->getTokenManager()->getByPublicHash($publicHash, $customerId);

        $tokenValue = $token->getGatewayToken();

        $expDate = null;
        $tokenDetails = json_decode($token->getTokenDetails(), true);
        if (isset($tokenDetails['ccExpMonth']) && isset($tokenDetails['ccExpYear'])) {
            $expDate = $tokenDetails['ccExpMonth'] . $tokenDetails['ccExpYear'];
        }

        if (isset($tokenDetails['ccCvv'])) {
            $cardValidationNum = $tokenDetails['ccCvv'];
        } else {
            $cardValidationNum = PaypageBuilder::CARD_VALIDATION_NUM;
        }

        return [
            'litleToken'        => $tokenValue,
            'expDate'           => $expDate,
            'cardValidationNum' => $cardValidationNum
        ];
    }
}
