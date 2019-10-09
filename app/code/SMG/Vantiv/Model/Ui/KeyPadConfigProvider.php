<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace SMG\Vantiv\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use SMG\Vantiv\Gateway\KeyPad\Config\VantivKeyPadConfig as Config;
use Vantiv\Payment\Model\Config\Source\VantivEnvironment;
use Magento\Payment\Model\MethodInterface;

/**
 * KeyPad configuration provider.
 */
class KeyPadConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment method instance.
     *
     * @var MethodInterface
     */
    private $method = null;

    /**
     * @param MethodInterface $method
     */
    public function __construct(MethodInterface $method)
    {
        $this->method = $method;
    }

    /**
     * Get method instance.
     *
     * @return MethodInterface
     */
    private function getMethod()
    {
        return $this->method;
    }

    /**
     * Retrieve assoc array of eProtect configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $method = $this->getMethod();

        return [
            'payment' => [
                $method->getCode() => [
                    'vault_code' => Config::VAULT_CODE,
                    'script_url' => $this->getScriptUrl($method),
                    'eprotect'   => $this->getEprotectConfig($method),
                ],
            ],
        ];
    }

    /**
     * Get eProtect configuration data.
     *
     * @param MethodInterface|null $method
     * @return array
     */
    public function getEprotectConfig(MethodInterface $method = null)
    {
        if ($method === null) {
            $method = $this->getMethod();
        }

        $data = [];

        return $data;
    }

    /**
     * Get script URL by "environment" value.
     *
     * @throws \InvalidArgumentException
     * @param MethodInterface|null $method
     * @return string
     */
    public function getScriptUrl(MethodInterface $method = null)
    {
        if ($method === null) {
            $method = $this->getMethod();
        }

        $url = '';

        /**
         * Script URL map.
         *
         * @var array $map
         */
        $map = [
            VantivEnvironment::SANDBOX
            => '',
            VantivEnvironment::PRELIVE
            => '',
            VantivEnvironment::TRANSACT_PRELIVE
            => '',
            VantivEnvironment::POSTLIVE
            => '',
            VantivEnvironment::TRANSACT_POSTLIVE
            => '',
            VantivEnvironment::PRODUCTION
            => '',
            VantivEnvironment::TRANSACT_PRODUCTION
            => '',
        ];

        $environment = $method->getConfigData('environment');
        if (array_key_exists($environment, $map)) {
            $url = $map[$environment];
        } else {
            throw new \InvalidArgumentException('Invalid environment.');
        }

        return $url;
    }
}
