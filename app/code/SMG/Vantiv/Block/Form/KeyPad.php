<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace SMG\Vantiv\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form as PaymentForm;
use SMG\Vantiv\Model\Ui\KeyPadConfigProvider;
use Vantiv\Payment\Helper\Vault as VaultHelper;

/**
 * Class Form
 */
class KeyPad extends PaymentForm
{
    /**
     * Template file path.
     *
     * @var string
     */
    protected $_template = 'SNG_Vantiv::form/keypad.phtml';

    /**
     * UI configuration provider.
     *
     * @var KeyPadConfigProvider
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
     * @param KeyPadConfigProvider $uiConfigProvider
     * @param Context $context
     * @param VaultHelper $vaultHelper
     * @param array $data
     */
    public function __construct(
        KeyPadConfigProvider $uiConfigProvider,
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
     * @return KeyPadConfigProvider
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
        $data = [
            'SMG_Vantiv/js/keypad' => [],
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
        return $this->getVaultHelper()->isKeyPadVaultEnabled();
    }
}
