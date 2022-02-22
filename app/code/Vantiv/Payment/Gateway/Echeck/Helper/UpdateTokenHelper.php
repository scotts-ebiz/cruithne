<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Helper;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Vantiv\Payment\Gateway\Common\SubjectReader;

/**
 * Current class updates token data in payment information model.
 */
class UpdateTokenHelper
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
     * @param PaymentTokenManagementInterface $tokenManager
     * @param SubjectReader $reader
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManager,
        SubjectReader $reader
    ) {
        $this->tokenManager = $tokenManager;
        $this->reader = $reader;
    }

    /**
     * Execute helper.
     *
     * @param array $subject
     * @return void
     */
    public function execute(array $subject)
    {
        $payment = $this->getReader()->readPayment($subject);

        $publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);
        $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
        $token = $this->getTokenManager()->getByPublicHash($publicHash, $customerId);
        $details = json_decode($token->getTokenDetails() ?: '{}', true);

        $payment->setAdditionalInformation('litle_token', $token->getGatewayToken());
        $payment->setEcheckAccountType($details['echeckAccountType']);
        $payment->setEcheckAccountName($details['maskedAccountNumber']);
        $payment->setEcheckRoutingNumber($details['echeckRoutingNumber']);
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
     * Get token manager.
     *
     * @return PaymentTokenManagementInterface
     */
    private function getTokenManager()
    {
        return $this->tokenManager;
    }
}
