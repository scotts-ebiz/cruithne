<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Handler;

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonHelper;
use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser as Parser;
use Vantiv\Payment\Gateway\Common\SubjectReader;
use Vantiv\Payment\Gateway\Common\Parser\ResponseParserInterface;
use Vantiv\Payment\Helper\Vault as VaultHelper;
use Vantiv\Payment\Observer\CcDataAssignObserver;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface as Payment;

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
     * @var PaymentTokenFactory
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
     * Json helper
     *
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * Constructor
     *
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param SubjectReader $reader
     * @param PaymentTokenFactory $tokenFactory
     * @param PaymentTokenManagementInterface $tokenManager
     * @param VaultHelper $vaultHelper
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        SubjectReader $reader,
        PaymentTokenFactory $tokenFactory,
        PaymentTokenManagementInterface $tokenManager,
        VaultHelper $vaultHelper,
        JsonHelper $jsonHelper
    ) {
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->reader = $reader;
        $this->tokenFactory = $tokenFactory;
        $this->tokenManager = $tokenManager;
        $this->vaultHelper = $vaultHelper;
        $this->jsonHelper = $jsonHelper;
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
     * @return JsonHelper
     */
    public function getJsonHelper()
    {
        return $this->jsonHelper;
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
        $payment = $this->getReader()->readPayment($subject);
        $isVaultActive = $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);
        $tokenResponseCode = $parser->getTokenResponseCode();

        $successResponseCodes = [
            ResponseParserInterface::TOKEN_SUCCESSFULLY_REGISTERED,
            ResponseParserInterface::TOKEN_PREVIOUSLY_REGISTERED
        ];

        if ($isVaultActive && in_array($tokenResponseCode, $successResponseCodes)) {
            $value = $parser->getLitleToken();

            $details = [
                'ccType' => $parser->getLitleTokenType(),

                'ccLast4' => $payment->hasAdditionalInformation(CcDataAssignObserver::CCLASTFOUR_KEY)
                    ? $payment->getAdditionalInformation(CcDataAssignObserver::CCLASTFOUR_KEY)
                    : substr($value, -4),

                'ccExpMonth' => $payment->hasAdditionalInformation(CcDataAssignObserver::CCEXPMONTH_KEY)
                    ? $payment->getAdditionalInformation(CcDataAssignObserver::CCEXPMONTH_KEY)
                    : '--',

                'ccExpYear' => $payment->hasAdditionalInformation(CcDataAssignObserver::CCEXPYEAR_KEY)
                    ? $payment->getAdditionalInformation(CcDataAssignObserver::CCEXPYEAR_KEY)
                    : '--',
            ];

            $tokenDetails = $this->getJsonHelper()->serialize($details);
            $customerId = $this->getReader()->readOrderAdapter($subject)->getCustomerId();
            $token = $this->getTokenManager()
                ->getByGatewayToken($value, $payment->getMethod(), $customerId);

            if ($token === null) {
                /** @var PaymentTokenInterface $token */
                $token = $this->getTokenFactory()->create(Payment::TOKEN_TYPE_CREDIT_CARD);
            }

            $token->setCustomerId($customerId);
            $token->setPaymentMethodCode($payment->getMethod());
            $token->setGatewayToken($value);
            $token->setTokenDetails($tokenDetails);

            $this->getVaultHelper()->saveToken($token);

            $this->getPaymentExtensionAttributes($payment)
                ->setVaultPaymentToken($token);
        }
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
     * @return PaymentTokenFactory
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
