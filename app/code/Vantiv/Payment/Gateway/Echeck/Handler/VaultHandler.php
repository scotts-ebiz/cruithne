<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Handler;

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Model\Vault\EcheckTokenFactory as TokenFactory;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Helper\Vault as VaultHelper;

/**
 * Echeck token vault handler.
 */
class VaultHandler
{
    /**
     * Payment extension attributes factory.
     *
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory = null;

    /**
     * Subject reader.
     *
     * @var SubjectReader
     */
    private $reader = null;

    /**
     * Payment tokent factory.
     *
     * @var TokenFactory
     */
    private $tokenFactory = null;

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
    private $vaultHelper;

    /**
     * Json
     *
     * @var Json
     */
    private $json;

    /**
     * Constructor
     *
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param SubjectReader $reader
     * @param TokenFactory $tokenFactory
     * @param PaymentTokenManagementInterface $tokenManager
     * @param VaultHelper $vaultHelper
     * @param Json $json
     */
    public function __construct(
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        SubjectReader $reader,
        TokenFactory $tokenFactory,
        PaymentTokenManagementInterface $tokenManager,
        VaultHelper $vaultHelper,
        Json $json
    ) {
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->reader = $reader;
        $this->tokenFactory = $tokenFactory;
        $this->tokenManager = $tokenManager;
        $this->vaultHelper = $vaultHelper;
        $this->json = $json;
    }

    /**
     * Get Vault helper instance
     *
     * @return VaultHelper
     */
    public function getVaultHelper()
    {
        return $this->vaultHelper;
    }

    /**
     * Get Json helper instance
     *
     * @return Json
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Handle vault token data.
     *
     * @param array $subject
     * @param Parser $parser
     * @return void
     */
    public function handle(array $subject, Parser $parser)
    {
        $payment = $this->getReader()->readPayment($subject);
        $echeckAccountNumber = $payment->getEcheckAccountName();
        $maskedAccountNumber = substr($echeckAccountNumber, -3);

        $isVaultActive = $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);
        $tokenResponseCode = $parser->getTokenResponseCode();
        $successResponseCodes = [
            ResponseParserInterface::TOKEN_SUCCESSFULLY_REGISTERED,
            ResponseParserInterface::TOKEN_PREVIOUSLY_REGISTERED
        ];

        if ($isVaultActive && in_array($tokenResponseCode, $successResponseCodes)) {
            $value = $parser->getLitleToken();
            $details = [
                'echeckAccountType' => $payment->getEcheckAccountType(),
                'maskedAccountNumber' => $maskedAccountNumber,
                'echeckRoutingNumber' => $payment->getEcheckRoutingNumber(),
            ];

            $tokenDetails = $this->getJson()->serialize($details);
            $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
            $token = $this->getTokenManager()
                ->getByGatewayToken($value, $payment->getMethod(), $customerId);

            if ($token === null) {
                /** @var TokenFactory $token */
                $token = $this->getTokenFactory()->create(TokenFactory::TOKEN_TYPE_ECHECK);
            }

            $token->setCustomerId($customerId);
            $token->setPaymentMethodCode($payment->getMethod());
            $token->setGatewayToken($value);
            $token->setTokenDetails($tokenDetails);

            $this->getVaultHelper()->saveToken($token);

            $this->getPaymentExtensionAttributes($payment)
                ->setVaultPaymentToken($token);
        } else {
            /*
             * We encrypt account number if token is not available.
             */
            $encryptedAccountNumber = $payment->encrypt($echeckAccountNumber);
            $payment->setAdditionalInformation('encrypted_account_number', $encryptedAccountNumber);
        }

        /*
         * Finally we mask account number.
         */
        $payment->setEcheckAccountName($maskedAccountNumber);
    }

    /**
     * Get payment extension attributes factory.
     *
     * @return OrderPaymentExtensionInterfaceFactory
     */
    private function getPaymentExtensionFactory()
    {
        return $this->paymentExtensionFactory;
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
     * Get payment token factory.
     *
     * @return TokenFactory
     */
    private function getTokenFactory()
    {
        return $this->tokenFactory;
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
     * Get payment extension attributes.
     *
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getPaymentExtensionAttributes(InfoInterface $payment)
    {
        $attributes = $payment->getExtensionAttributes();
        if ($attributes === null) {
            $attributes = $this->getPaymentExtensionFactory()->create();
            $payment->setExtensionAttributes($attributes);
        }
        return $attributes;
    }
}
