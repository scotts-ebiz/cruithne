<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace SMG\Vantiv\Gateway\KeyPad\Config;

use Vantiv\Payment\Gateway\Common\Config\VantivPaymentConfig;

/**
 * Vantiv payment configuration class.
 */
class VantivKeyPadConfig extends VantivPaymentConfig
{
    /**
     * KeyPad payment method code.
     *
     * @var string
     */
    const METHOD_CODE = 'vantiv_keypadpayment';

    /**
     * KeyPad vault method code.
     *
     * @var string
     */
    const VAULT_CODE = 'vantiv_keypadpayment_vault';
}
