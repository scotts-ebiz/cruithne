<?php

namespace SMG\Vantiv\Model\Ui;

use Magento\Payment\Model\MethodInterface;
use Vantiv\Payment\Model\Config\Source\VantivEnvironment;

/**
 * Credit card configuration provider.
 */
class CcConfigProvider extends \Vantiv\Payment\Model\Ui\CcConfigProvider
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
         *
         * => 'https://request.eprotect.vantivprelive.com/eProtect/js/payframe-client.min.js',
         * => 'https://request.eprotect.vantivpostlive.com/eProtect/js/payframe-client.min.js',
         */
        $map = [
            VantivEnvironment::SANDBOX
                => 'https://request.eprotect.vantivpostlive.com/eProtect/js/payframe-client.min.js',
            VantivEnvironment::PRELIVE
                => 'https://request.eprotect.vantivprelive.com/eProtect/js/payframe-client.min.js',
            VantivEnvironment::TRANSACT_PRELIVE
                => 'https://request.eprotect.vantivprelive.com/eProtect/js/payframe-client.min.js',
            VantivEnvironment::POSTLIVE
                => 'https://request.eprotect.vantivpostlive.com/eProtect/js/payframe-client.min.js',
            VantivEnvironment::TRANSACT_POSTLIVE
                => 'https://request.eprotect.vantivpostlive.com/eProtect/js/payframe-client.min.js',
            VantivEnvironment::PRODUCTION
                => 'https://request.eprotect.vantivcnp.com/eProtect/js/payframe-client.min.js',
            VantivEnvironment::TRANSACT_PRODUCTION
                => 'https://request.eprotect.vantivcnp.com/eProtect/js/payframe-client.min.js',
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
