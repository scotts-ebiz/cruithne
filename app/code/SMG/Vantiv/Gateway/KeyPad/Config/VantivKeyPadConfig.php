<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace SMG\Vantiv\Gateway\KeyPad\Config;

use Vantiv\Payment\Gateway\Common\Config\VantivPaymentConfig;
use Vantiv\Payment\Model\Config\Source\VantivEnvironment;

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

    /**
     * Get API endpoint URL.
     *
     * @param string $environment
     * @return string
     */
    public function getUrlByEnvironment($environment)
    {
        $url = '';

        /**
         * API endpoints URL map.
         *
         * @var array $map
         */
        $map = [
            VantivEnvironment::SANDBOX => 'https://certservices.elementexpress.com',
            VantivEnvironment::PRELIVE => 'https://certservices.elementexpress.com',
            VantivEnvironment::TRANSACT_PRELIVE => 'https://certservices.elementexpress.com',
            VantivEnvironment::POSTLIVE => 'https://certservices.elementexpress.com',
            VantivEnvironment::TRANSACT_POSTLIVE => 'https://certservices.elementexpress.com',
            VantivEnvironment::PRODUCTION => 'https://services.elementexpress.com',
            VantivEnvironment::TRANSACT_PRODUCTION => 'https://services.elementexpress.com',
        ];

        if (array_key_exists($environment, $map)) {
            $url = $map[$environment];
        } else {
            throw new \InvalidArgumentException('Invalid environment.');
        }

        return $url;
    }
}
