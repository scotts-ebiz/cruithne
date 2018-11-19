<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Vantiv payment configuration class.
 */
class VantivPaymentConfig implements ConfigInterface
{
    /**
     * Payment method configuration path pattern.
     *
     * @var string
     */
    const PAYMENT_PATH_PATTERN = 'payment/%s/%s';

    /**
     * Vantiv payment module configuration path pattern.
     *
     * @var string
     */
    const VANTIV_PATH_PATTERN = 'vantiv/payment/%s';

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
     * Scope configuration instance.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig = null;

    /**
     * Payment method code.
     *
     * @var string|null
     */
    private $methodCode = null;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->methodCode = $methodCode;
    }

    /**
     * Sets method code.
     *
     * @param string $code
     * @return void
     */
    public function setMethodCode($code)
    {
        $this->methodCode = $code;
    }

    /**
     * Get payment method code.
     *
     * @return string|null
     */
    public function getMethodCode()
    {
        return $this->methodCode;
    }

    /**
     * Sets path pattern.
     *
     * @param string $pattern
     * @return void
     */
    public function setPathPattern($pattern)
    {
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
            $path = sprintf(self::VANTIV_PATH_PATTERN, $field);
        } else {
            $path = sprintf(self::PAYMENT_PATH_PATTERN, $this->getMethodCode(), $field);
        }

        return $this->getScopeConfigData($path, $storeId);
    }

    /**
     * Check if we should fallback to common configuration.
     *
     * @param string $field
     * @return boolean
     */
    protected function isFallback($field)
    {
        return empty($this->getMethodCode()) || in_array($field, $this->getCommonFields());
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

    /**
     * Get scope configuration data.
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    protected function getScopeConfigData($path, $storeId)
    {
        return $this->getScopeConfig()->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get scope configuration instance.
     *
     * @return ScopeConfigInterface
     */
    private function getScopeConfig()
    {
        return $this->scopeConfig;
    }
}
