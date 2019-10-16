<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SMG\Vantiv\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Vantiv\Payment\Helper\Vault as VaultHelper;

class Keypad extends \Magento\Payment\Block\Form
{
    /**
     * Purchase order template
     *
     * @var string
     */
    protected $_template = 'SMG_Vantiv::form/keypad.phtml';

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
    public function __construct(Context $context,
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
