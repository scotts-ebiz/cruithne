<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Echeck\Config;

use Vantiv\Payment\Gateway\Common\Config\VantivPaymentConfig;

/**
 * Vantiv eCheck payment configuration class.
 */
class VantivEcheckConfig extends VantivPaymentConfig
{
    /**
     * Credit card payment method code.
     *
     * @var string
     */
    const METHOD_CODE = 'vantiv_echeck';

    /**
     * Credit card vault method code.
     *
     * @var string
     */
    const VAULT_CODE = 'vantiv_echeck_vault';
}
