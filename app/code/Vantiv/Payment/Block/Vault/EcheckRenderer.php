<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;
use Magento\Framework\View\Element\Template\Context;
use Vantiv\Payment\Gateway\Echeck\Config\VantivEcheckConfig as Config;
use Vantiv\Payment\Helper\Vault as VaultHelper;

class EcheckRenderer extends AbstractTokenRenderer
{
    /**
     * Vault helper
     *
     * @var VaultHelper
     */
    private $vaultHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param VaultHelper $vaultHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        VaultHelper $vaultHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->vaultHelper = $vaultHelper;
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
     * Check if can render current token.
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === Config::METHOD_CODE
            && $this->getVaultHelper()->isEcheckVaultEnabled();
    }

    /**
     * Get bank account type.
     *
     * @return string
     */
    public function getAccountType()
    {
        $details = $this->getTokenDetails();
        return $details['echeckAccountType'];
    }

    /**
     * Get bank account last 3 digits.
     *
     * @return string
     */
    public function getAccountNumber()
    {
        $details = $this->getTokenDetails();
        return $details['maskedAccountNumber'];
    }

    /**
     * Get routing number.
     *
     * @return string
     */
    public function getRoutingNumber()
    {
        $details = $this->getTokenDetails();
        return $details['echeckRoutingNumber'];
    }

    /**
     * Get url to icon.
     *
     * @return string
     */
    public function getIconUrl()
    {
        return '';
    }

    /**
     * Get height of icon.
     *
     * @return int
     */
    public function getIconHeight()
    {
        return 0;
    }

    /**
     * Get width of icon.
     *
     * @return int
     */
    public function getIconWidth()
    {
        return 0;
    }
}
