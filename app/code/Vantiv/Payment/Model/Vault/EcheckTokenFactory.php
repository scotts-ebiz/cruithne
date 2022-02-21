<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Vault;

use Magento\Vault\Model\PaymentTokenFactory;

/**
 * Class EcheckTokenFactory
 */
class EcheckTokenFactory extends PaymentTokenFactory
{
    /**
     * Bank account token type.
     *
     * @var string
     */
    const TOKEN_TYPE_ECHECK = 'echeck';

    /**
     * Get token type identifier.
     *
     * @return string
     */
    public function getType()
    {
        return self::TOKEN_TYPE_ECHECK;
    }
}
