<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Ui;

use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Vantiv\Payment\Gateway\Androidpay\Config\VantivAndroidpayConfig as Config;

class AndroidpayTokenComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * Token UI component factory.
     *
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory = null;

    /**
     * Constructor.
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(TokenUiComponentInterfaceFactory $componentFactory)
    {
        $this->componentFactory = $componentFactory;
    }

    /**
     * Get token UI component factory.
     *
     * @return TokenUiComponentInterfaceFactory
     */
    private function getComponentFactory()
    {
        return $this->componentFactory;
    }

    /**
     * Get UI component for Androidpay token.
     *
     * @param PaymentTokenInterface $token
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $token)
    {
        $details = json_decode($token->getTokenDetails() ?: '{}', true);

        $component = $this->getComponentFactory()->create([
            'config' => [
                'code' => Config::VAULT_CODE,
                TokenUiComponentProviderInterface::COMPONENT_DETAILS => $details,
                TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $token->getPublicHash(),
            ],
            'name' => 'Vantiv_Payment/js/view/payment/method-renderer/vantiv-androidpay-vault'
        ]);

        return $component;
    }
}
