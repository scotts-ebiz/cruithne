<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Androidpay\Config;

/**
 * Vantiv payment configuration class.
 */
class VantivAndroidpayConfig extends \Vantiv\Payment\Gateway\Cc\Config\VantivCcFallbackConfig
{
    /**
     * AndroidPay payment method code.
     *
     * @var string
     */
    const METHOD_CODE = 'vantiv_androidpay';

    /**
     * AndroidPay vault method code.
     *
     * @var string
     */
    const VAULT_CODE = 'vantiv_androidpay_vault';
}
