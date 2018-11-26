<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Applepay\Config;

/**
 * Vantiv payment configuration class.
 */
class VantivApplepayConfig extends \Vantiv\Payment\Gateway\Cc\Config\VantivCcFallbackConfig
{
    /**
     * ApplePay payment method code.
     *
     * @var string
     */
    const METHOD_CODE = 'vantiv_applepay';

    /**
     * ApplePay vault method code.
     *
     * @var string
     */
    const VAULT_CODE = 'vantiv_applepay_vault';
}
