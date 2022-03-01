<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Handler;

use Magento\Vault\Api\PaymentTokenManagementInterface;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Helper\Vault as VaultHelper;
use Psr\Log\LoggerInterface;
use Magento\Vault\Model\VaultPaymentInterface;

class AccountUpdaterHandler
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
     * Vault helper
     *
     * @var VaultHelper
     */
    private $vaultHelper = null;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    private $logger = null;

    /**
     * Constructor
     *
     * @param SubjectReader $reader
     * @param PaymentTokenManagementInterface $tokenManager
     * @param VaultHelper $vaultHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        SubjectReader $reader,
        PaymentTokenManagementInterface $tokenManager,
        VaultHelper $vaultHelper,
        LoggerInterface $logger
    ) {
        $this->reader = $reader;
        $this->tokenManager = $tokenManager;
        $this->vaultHelper = $vaultHelper;
        $this->logger = $logger;
    }

    /**
     * Get Vault helper instance
     *
     * @return VaultHelper
     */
    private function getVaultHelper()
    {
        return $this->vaultHelper;
    }

    /**
     * Handle vault token data
     *
     * @param array $subject
     * @param Parser $parser
     * @return void
     */
    public function handle(array $subject, Parser $parser)
    {
        try {
            $newTokenValue = $parser->getNewCardTokenInfoLitleToken();
            if (empty($newTokenValue)) {
                return;
            }

            $oldTokenValue = $parser->getOriginalCardTokenInfoLitleToken();
            if (empty($oldTokenValue)) {
                return;
            }

            $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
            if (empty($customerId)) {
                return;
            }

            $methodCode = null;
            $method = $this->getReader()->readPayment($subject)->getMethodInstance();
            if ($method instanceof VaultPaymentInterface) {
                $methodCode = $method->getProviderCode();
            } else {
                $methodCode = $method->getCode();
            }
            $token = $this->getTokenManager()->getByGatewayToken(
                $oldTokenValue,
                $methodCode,
                $customerId
            );
            if ($token === null) {
                return;
            }

            $expDate = $parser->getNewCardTokenInfoExpDate();
            $details = [
                'ccType'     => $parser->getNewCardTokenInfoType(),
                'ccLast4'    => substr($newTokenValue, -4),
                'ccExpMonth' => substr($expDate, 0, 2),
                'ccExpYear'  => substr($expDate, 2, 2),
            ];
            $token->setGatewayToken($newTokenValue);
            $token->setTokenDetails(json_encode($details));
            $this->getVaultHelper()->saveToken($token);
        } catch (\Exception $e) {
            /*
             * Do not break transaction if account apdate fails.
             */
            $this->getLogger()->error($e->getMessage());
        }
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

    /**
     * Get logger instance.
     *
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return $this->logger;
    }
}
