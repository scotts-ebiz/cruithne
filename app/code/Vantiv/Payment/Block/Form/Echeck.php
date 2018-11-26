<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vantiv\Payment\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form as PaymentForm;
use Vantiv\Payment\Model\Config\Source\EcheckAccountTypes;
use Vantiv\Payment\Helper\Vault as VaultHelper;

/**
 * Echeck form block.
 */
class Echeck extends PaymentForm
{
    /**
     * Template file path.
     *
     * @var string
     */
    protected $_template = 'Vantiv_Payment::form/echeck.phtml';

    /**
     * Echeck account type options.
     *
     * @var EcheckAccountTypes
     */
    private $echeckAccountTypesSource = null;

    /**
     * Echeck account type options cache.
     *
     * @var array
     */
    private $echeckAccountTypeOptions = null;

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
     * @param EcheckAccountTypes $echeckAccountTypesSource
     * @param VaultHelper $vaultHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        EcheckAccountTypes $echeckAccountTypesSource,
        VaultHelper $vaultHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->echeckAccountTypesSource = $echeckAccountTypesSource;
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
     * Get eCheck account type options.
     *
     * @return array
     */
    public function getEcheckAccountTypeOptions()
    {
        if ($this->echeckAccountTypeOptions === null) {
            $options = [];

            foreach ($this->echeckAccountTypesSource->toOptionArray() as $option) {
                $options[$option['value']] = $option['label'];
            }

            $this->echeckAccountTypeOptions = $options;
        }

        return $this->echeckAccountTypeOptions;
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
