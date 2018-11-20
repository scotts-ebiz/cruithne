<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Vault;

use Magento\Framework\View\Element\Template;
use Vantiv\Payment\Model\Config\Source\EcheckAccountTypes;
use Magento\Framework\View\Element\Template\Context;

class EcheckForm extends Template
{
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
     * Constructor.
     *
     * @param EcheckAccountTypes $echeckAccountTypes
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        EcheckAccountTypes $echeckAccountTypes,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->echeckAccountTypesSource = $echeckAccountTypes;
    }

    /**
     * Get "Back" button URL.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('vault/cards/listaction');
    }

    /**
     * Get form submit action URL.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/echecksave');
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
}
