<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Vault;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Vantiv\Payment\Model\Ui\CcConfigProvider;

class CcForm extends Template
{
    /**
     * Credit card payment UI configuration provider.
     *
     * @var CcConfigProvider
     */
    private $uiConfigProvider = null;

    /**
     * Constructor.
     *
     * @param CcConfigProvider $uiConfigProvider
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        CcConfigProvider $uiConfigProvider,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->uiConfigProvider = $uiConfigProvider;
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
        return $this->getUrl('*/*/ccsave');
    }

    /**
     * Get payment UI configuration provider.
     *
     * @return CcConfigProvider
     */
    private function getUiConfigProvider()
    {
        return $this->uiConfigProvider;
    }

    /**
     * Get JSON-encoded mage init data.
     *
     * @return string
     */
    public function getMageInitJson()
    {
        $config = $this->getConfig();

        $eprotect = $this->getUiConfigProvider()->getEprotectConfig();
        $eprotect['scriptUrl'] = $this->getUiConfigProvider()->getScriptUrl();

        $data = [
            'Vantiv_Payment/js/view/vault/eprotect' => [
                'config' => $eprotect,
            ],
        ];

        $json = json_encode($data);

        return $json;
    }

    /**
     * Get requested public hash.
     *
     * @return string
     */
    public function getPublicHash()
    {
        return $this->getRequest()->getParam('public_hash');
    }
}
