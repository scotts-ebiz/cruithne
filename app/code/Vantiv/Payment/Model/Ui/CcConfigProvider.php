<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Vantiv\Payment\Gateway\Cc\Config\VantivCcConfig as Config;
use Vantiv\Payment\Model\Config\Source\VantivEnvironment;
use Magento\Payment\Model\MethodInterface;

/**
 * Credit card configuration provider.
 */
class CcConfigProvider implements ConfigProviderInterface
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

        /*
         * We should use default style in case config is empty.
         */
        $style = trim($method->getConfigData('eprotect_style'));
        $style = empty($style) ? 'empty' : $style;

        $data = [
            'paypageId'       => $method->getConfigData('eprotect_paypage_id'),
            'style'           => $style,
            'height'          => $method->getConfigData('eprotect_height'),
            'reportGroup'     => $method->getConfigData('report_group'),
            'timeout'         => $method->getConfigData('http_timeout') * 1000,
            'div'             => 'payframe',
            'showCvv'         => (boolean) $method->getConfigData('useccv'),
            'months'          => $this->getMonthsData(),
            'numYears'        => $method->getConfigData('eprotect_num_years'),
            'tooltipText'     => $method->getConfigData('eprotect_tooltip_text'),
            'tabIndex'        => [
                'accountNumber' => $method->getConfigData('eprotect_tab_index_account_number'),
                'expMonth'      => $method->getConfigData('eprotect_tab_index_exp_month'),
                'expYear'       => $method->getConfigData('eprotect_tab_index_exp_year'),
                'cvv'           => $method->getConfigData('eprotect_tab_index_cvv'),
            ],
            'placeholderText' => [
                'cvv'           => $method->getConfigData('eprotect_placeholder_text_cvv'),
                'accountNumber' => $method->getConfigData('eprotect_placeholder_text_account_number'),
            ],
        ];

        return $data;
    }

    /**
     * Return months translated data.
     *
     * @return array
     */
    private function getMonthsData()
    {
        $data = [
            '1'  => (string) __('January'),
            '2'  => (string) __('February'),
            '3'  => (string) __('March'),
            '4'  => (string) __('April'),
            '5'  => (string) __('May'),
            '6'  => (string) __('June'),
            '7'  => (string) __('July'),
            '8'  => (string) __('August'),
            '9'  => (string) __('September'),
            '10' => (string) __('October'),
            '11' => (string) __('November'),
            '12' => (string) __('December'),
        ];
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
                => 'https://request.eprotect.vantivprelive.com/eProtect/js/payframe-client.min.js',
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
