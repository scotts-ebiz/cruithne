<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Vault;

use Magento\Vault\Block\Customer\PaymentTokens;
use Vantiv\Payment\Model\Vault\EcheckTokenFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Vault\Model\CustomerTokenManagement;
use Vantiv\Payment\Helper\Vault as VaultHelper;

/**
 * Class EcheckList
 */
class EcheckList extends PaymentTokens
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
     * @param CustomerTokenManagement $customerTokenManagement
     * @param VaultHelper $vaultHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerTokenManagement $customerTokenManagement,
        VaultHelper $vaultHelper,
        array $data = []
    ) {
        parent::__construct($context, $customerTokenManagement, $data);
        $this->vaultHelper = $vaultHelper;
    }

    /**
     * Get token type.
     *
     * @return string
     */
    public function getType()
    {
        return EcheckTokenFactory::TOKEN_TYPE_ECHECK;
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
     * Checks if vault enabled
     *
     * @return bool
     */
    public function isVaultEnabled()
    {
        return $this->getVaultHelper()->isEcheckVaultEnabled();
    }
}
