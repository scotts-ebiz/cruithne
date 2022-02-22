<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Gateway\Common\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Vantiv payment configuration class.
 */
class VantivCustomConfig
{
    /**
     * Custom functionality configuration path pattern.
     *
     * @var string
     */
    const CUSTOM_PATH_PATTERN = '%s/%s';

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
     * Configuration path namespace.
     *
     * @var string|null
     */
    private $namespace = null;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $namespace
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $namespace = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->namespace = $namespace;
    }

    /**
     * Get configuration path namespace.
     *
     * @return string|NULL
     */
    private function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Retrieve information from custom configuration.
     * Or fallback to common configuration.
     *
     * @param string $field
     * @param int|null $storeId
     * @param string $scopeType
     * @return mixed
     */
    public function getValue($field, $storeId = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        if ($this->isFallback($field)) {
            $path = sprintf(self::VANTIV_PATH_PATTERN, $field);
        } else {
            $path = sprintf(self::CUSTOM_PATH_PATTERN, $this->getNamespace(), $field);
        }

        return $this->getScopeConfigData($path, $storeId, $scopeType);
    }

    /**
     * Check if we should fallback to common configuration.
     *
     * @param string $field
     * @return boolean
     */
    private function isFallback($field)
    {
        return empty($this->getNamespace()) || in_array($field, $this->getCommonFields());
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
     * @param string $path
     * @param int|null $storeId
     * @param string $scopeType
     * @return mixed
     */
    private function getScopeConfigData($path, $storeId, $scopeType)
    {
        return $this->getScopeConfig()->getValue($path, $scopeType, $storeId);
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
