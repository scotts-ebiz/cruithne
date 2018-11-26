<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Block\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Vantiv\Payment\Gateway\Applepay\Config\VantivApplepayConfig;

class ApplepayRenderer extends CcRenderer
{
    /**
     * Check if can render current token.
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === VantivApplepayConfig::METHOD_CODE
            && $this->getVaultHelper()->isApplepayVaultEnabled();
    }
}
