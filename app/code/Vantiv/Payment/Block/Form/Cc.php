<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form as PaymentForm;
use Vantiv\Payment\Model\Ui\CcConfigProvider;
use Vantiv\Payment\Helper\Vault as VaultHelper;

/**
 * Class Form
 */
class Cc extends PaymentForm
{
    /**
     * Template file path.
     *
     * @var string
     */
    protected $_template = 'Vantiv_Payment::form/cc.phtml';

    /**
     * UI configuration provider.
     *
     * @var CcConfigProvider
     */
    private $uiConfigProvider = null;

    /**
     * Vault helper
     *
     * @var VaultHelper
     */
    private $vaultHelper;

    /**
     * Constructor
     *
     * @param CcConfigProvider $uiConfigProvider
     * @param Context $context
     * @param VaultHelper $vaultHelper
     * @param array $data
     */
    public function __construct(
        CcConfigProvider $uiConfigProvider,
        Context $context,
        VaultHelper $vaultHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->uiConfigProvider = $uiConfigProvider;
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
     * Get UI configuration provider.
     *
     * @return CcConfigProvider
     */
    private function getUiConfigProvider()
    {
        return $this->uiConfigProvider;
    }

    /**
     * Get form init JSON.
     *
     * @return string
     */
    public function getMageInitJson()
    {
        $method = $this->getMethod();

        $eprotect = $this->getUiConfigProvider()->getEprotectConfig($method);
        $eprotect['scriptUrl'] = $this->getUiConfigProvider()->getScriptUrl($method);
        $data = [
            'Vantiv_Payment/js/eprotect' => [
                'config' => $eprotect,
            ],
        ];

        $json = json_encode($data);
        return $json;
    }

    /**
     * Checks if vault enabled
     *
     * @return bool
     */
    public function isVaultEnabled()
    {
        return $this->getVaultHelper()->isCcVaultEnabled();
    }
}
