<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Vault\Model\VaultPaymentInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Vantiv\Payment\Gateway\Echeck\Config\VantivEcheckConfig;
use Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig;
use Vantiv\Payment\Gateway\Androidpay\Config\VantivAndroidpayConfig;
use Vantiv\Payment\Gateway\Applepay\Config\VantivApplepayConfig;

/**
 * Class Vault
 */
class Vault extends AbstractHelper
{
    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Payment helper
     *
     * @var PaymentDataHelper
     */
    private $paymentDataHelper;

    /**
     * Encryptor
     *
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Token manager
     *
     * @var PaymentTokenManagementInterface
     */
    private $tokenManager;

    /**
     * Token repository
     *
     * @var PaymentTokenRepositoryInterface
     */
    private $tokenRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param PaymentDataHelper $paymentDataHelper
     * @param EncryptorInterface $encryptor
     * @param PaymentTokenManagementInterface $tokenManager
     * @param PaymentTokenRepositoryInterface $tokenRepository
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        PaymentDataHelper $paymentDataHelper,
        EncryptorInterface $encryptor,
        PaymentTokenManagementInterface $tokenManager,
        PaymentTokenRepositoryInterface $tokenRepository
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->paymentDataHelper = $paymentDataHelper;
        $this->encryptor = $encryptor;
        $this->tokenRepository = $tokenRepository;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Get configured vault payment for Vantiv by method code
     *
     * @param string $methodCode
     * @return VaultPaymentInterface
     */
    private function getVaultPayment($methodCode)
    {
        return $this->paymentDataHelper->getMethodInstance($methodCode);
    }

    /**
     * Get public hash for vault token
     *
     * @param array $hashParts
     * @return string
     */
    public function getPublicHash($hashParts)
    {
        return $this->encryptor->getHash(implode('_', $hashParts));
    }

    /**
     * Get expiration date for vault token
     *
     * @return string
     */
    public function getExpiresAt()
    {
        $expDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $expDate->add(new \DateInterval('P5Y'));

        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Checks if vault enabled for CC payment method
     *
     * @return bool
     */
    public function isCcVaultEnabled()
    {
        return $this->isVaultEnabled(VantivCcConfig::VAULT_CODE);
    }

    /**
     * Checks if vault enabled for Androidpay payment method
     *
     * @return bool
     */
    public function isAndroidpayVaultEnabled()
    {
        return $this->isVaultEnabled(VantivAndroidpayConfig::VAULT_CODE);
    }

    /**
     * Checks if vault enabled for Applepay payment method
     *
     * @return bool
     */
    public function isApplepayVaultEnabled()
    {
        return $this->isVaultEnabled(VantivApplepayConfig::VAULT_CODE);
    }

    /**
     * Checks if vault enabled for Echeck payment method
     *
     * @return bool
     */
    public function isEcheckVaultEnabled()
    {
        return $this->isVaultEnabled(VantivEcheckConfig::VAULT_CODE);
    }

    /**
     * Checks if vault enabled for Vantiv by method code
     *
     * @param string $methodCode
     * @return bool
     */
    public function isVaultEnabled($methodCode)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $vaultPayment = $this->getVaultPayment($methodCode);

        return $vaultPayment->isActive($storeId);
    }

    /**
     * Save vault token
     *
     * @param PaymentTokenInterface $token
     */
    public function saveToken(PaymentTokenInterface $token)
    {
        $publicHash = $token->getPublicHash();
        if (empty($publicHash)) {
            $publicHash = $this->getPublicHash(
                [
                    $token->getGatewayToken(),
                    $token->getPaymentMethodCode(),
                    $token->getCustomerId()
                ]
            );
            $token->setPublicHash($publicHash);
        }

        $duplicate = $this->tokenManager->getByGatewayToken(
            $token->getGatewayToken(),
            $token->getPaymentMethodCode(),
            $token->getCustomerId()
        );

        if (!empty($duplicate)) {
            $token->setEntityId($duplicate->getEntityId());
            $token->setPublicHash($duplicate->getPublicHash());
        }

        $expiresAt = $this->getExpiresAt();
        $token->setExpiresAt($expiresAt);

        $token->setIsActive(true);
        $token->setIsVisible(true);

        $this->tokenRepository->save($token);
    }
}
