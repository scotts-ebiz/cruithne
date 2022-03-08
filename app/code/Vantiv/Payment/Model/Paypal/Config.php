<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vantiv\Payment\Model\Paypal;

use Vantiv\Payment\Gateway\Common\Config\VantivPaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Config model that is aware of all \Magento\Paypal payment methods
 * Works with PayPal-specific system configuration
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Config extends \Magento\Paypal\Model\Config
{
    /**
     * Array of common configuration fallback fields.
     *
     * @var array
     */
    private $commonFields = [
        'merchant_id',
        'username',
        'password',
        'debug',
        'http_proxy',
        'http_timeout',
        'report_group',
        'threatmetrix_orgid',
        'threatmetrix_sessionprefix',
    ];

    /**
     * Vantiv PayPal payment method code.
     *
     * @var string
     */
    const METHOD_CODE = 'vantiv_paypal_express';

    /**
     * Config path for enabling/disabling order review step in express checkout
     */
    const XML_PATH_PAYPAL_EXPRESS_SKIP_ORDER_REVIEW_STEP_FLAG = 'payment/vantiv_paypal_express/skip_order_review_step';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Payment\Model\Source\CctypeFactory $cctypeFactory
     * @param \Magento\Paypal\Model\CertFactory $certFactory
     * @param array $params
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Payment\Model\Source\CctypeFactory $cctypeFactory,
        \Magento\Paypal\Model\CertFactory $certFactory,
        $params = []
    ) {
        parent::__construct($scopeConfig, $directoryHelper, $storeManager, $cctypeFactory, $certFactory, $params);
    }

    /**
     * Return list of allowed methods for specified country iso code
     *
     * @param string|null $countryCode 2-letters iso code
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCountryMethods($countryCode = null)
    {
        $countryMethods = [
            'other' => [
                self::METHOD_CODE,
            ],
            'US' => [
                self::METHOD_CODE,
            ],
            'CA' => [
                self::METHOD_CODE,
            ],
            'GB' => [
                self::METHOD_CODE,
            ],
            'AU' => [
                self::METHOD_CODE,
            ],
            'NZ' => [
                self::METHOD_CODE,
            ],
            'JP' => [
                self::METHOD_CODE,
            ],
            'FR' => [
                self::METHOD_CODE,
            ],
            'IT' => [
                self::METHOD_CODE,
            ],
            'ES' => [
                self::METHOD_CODE,
            ],
            'HK' => [
                self::METHOD_CODE,
            ],
            'DE' => [
                self::METHOD_CODE,
            ],
        ];
        if ($countryCode === null) {
            return $countryMethods;
        }
        return isset($countryMethods[$countryCode]) ? $countryMethods[$countryCode] : $countryMethods['other'];
    }

    /**
     * Map PayPal Website Payments Pro common config fields
     *
     * @param string $fieldName
     * @return string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _mapWppFieldset($fieldName)
    {
        $method = self::METHOD_CODE;
        switch ($fieldName) {
            case 'api_authentication':
            case 'api_username':
            case 'api_password':
            case 'api_signature':
            case 'api_cert':
            case 'sandbox_flag':
            case 'use_proxy':
            case 'proxy_host':
            case 'proxy_port':
            case 'button_flavor':
            case 'button_type':
            case 'environment':
                return "payment/{$method}/{$fieldName}";
            default:
                return null;
        }
    }

    /**
     * Check whether order review step enabled in configuration
     *
     * @return bool
     */
    public function isOrderReviewStepDisabled()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_PAYPAL_EXPRESS_SKIP_ORDER_REVIEW_STEP_FLAG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    /**
     * Mapper from PayPal-specific payment actions to Magento payment actions
     *
     * @return string|null
     */
    public function getPaymentAction()
    {
        return $this->getValue('payment_action');
    }

    /**
     * Map any supported payment method into a config path by specified field name
     *
     * @param string $fieldName
     * @return string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getSpecificConfigPath($fieldName)
    {
        $path = null;
        $path = $this->_mapExpressFieldset($fieldName);

        if ($path === null) {
            $path = $this->_mapWppFieldset($fieldName);
        }
        if ($path === null) {
            $path = $this->_mapGeneralFieldset($fieldName);
        }
        if ($path === null) {
            $path = $this->_mapGenericStyleFieldset($fieldName);
        }

        return $path;
    }

    /**
     * Check whether method available for checkout or not
     * Logic based on merchant country, methods dependence
     *
     * @param string|null $methodCode
     * @return bool
     */
    public function isMethodAvailable($methodCode = null)
    {
        $result = false;

        if ($this->isMethodActive(self::METHOD_CODE)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Check whether method active in configuration and supported for merchant country or not
     *
     * @param string $method Method code
     * @return bool
     */
    public function isMethodActive($method)
    {
        $isEnabled = $this->_scopeConfig->isSetFlag(
            "payment/{$method}/active",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );

        return $this->isMethodSupportedForCountry($method) && $isEnabled;
    }

    /**
     * Retrieve information from payment configuration.
     * Or fallback to common configuration.
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        $path = '';

        if ($this->isFallback($field)) {
            $path = sprintf(VantivPaymentConfig::VANTIV_PATH_PATTERN, $field);
        } else {
            return parent::getValue($field, $storeId);
        }

        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->_storeId);
    }

    /**
     * Check if we should fallback to common configuration.
     *
     * @param string $field
     * @return boolean
     */
    private function isFallback($field)
    {
        return in_array($field, $this->getCommonFields());
    }

    /**
     * Get list of common configuration fields.
     *
     * @return array
     */
    private function getCommonFields()
    {
        return $this->commonFields;
    }
}
