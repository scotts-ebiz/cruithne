<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Ui\Adminhtml;

use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Framework\View\Element\Template;
use Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig as Config;

class CcVaultTokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * Token UI component factory
     *
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory = null;

    /**
     * Constructor
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(TokenUiComponentInterfaceFactory $componentFactory)
    {
        $this->componentFactory = $componentFactory;
    }

    /**
     * Get token UI component factory
     *
     * @return TokenUiComponentInterfaceFactory
     */
    private function getComponentFactory()
    {
        return $this->componentFactory;
    }

    /**
     * Returns UI component for payment token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $data = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $component = $this->getComponentFactory()->create(
            [
                'config' => [
                    'code' => Config::VAULT_CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $data,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'Vantiv_Payment::form/cc/vault.phtml'
                ],
                'name' => Template::class
            ]
        );

        return $component;
    }
}
