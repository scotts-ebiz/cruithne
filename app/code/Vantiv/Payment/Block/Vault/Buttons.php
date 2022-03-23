<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Vault;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Vantiv\Payment\Helper\Vault as VaultHelper;

class Buttons extends Template
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
     * Get credit card "add" action URL.
     *
     * @return string
     */
    public function getAddCcUrl()
    {
        return $this->getUrl('vantiv/vault/ccform');
    }

    /**
     * Get echeck "add" action URL.
     *
     * @return string
     */
    public function getAddEcheckUrl()
    {
        return $this->getUrl('vantiv/vault/echeckform');
    }

    /**
     * Checks if vault enabled for CC payment method
     *
     * @return bool
     */
    public function isCcVaultEnabled()
    {
        return $this->getVaultHelper()->isCcVaultEnabled();
    }

    /**
     * Checks if vault enabled for Echeck payment method
     *
     * @return bool
     */
    public function isEcheckVaultEnabled()
    {
        return $this->getVaultHelper()->isEcheckVaultEnabled();
    }
}
