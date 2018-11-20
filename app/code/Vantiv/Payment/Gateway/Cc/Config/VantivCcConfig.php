<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Cc\Config;

use Vantiv\Payment\Gateway\Common\Config\VantivPaymentConfig;

/**
 * Vantiv payment configuration class.
 */
class VantivCcConfig extends VantivPaymentConfig
{
    /**
     * Credit card payment method code.
     *
     * @var string
     */
    const METHOD_CODE = 'vantiv_cc';

    /**
     * Credit card vault method code.
     *
     * @var string
     */
    const VAULT_CODE = 'vantiv_cc_vault';
}
