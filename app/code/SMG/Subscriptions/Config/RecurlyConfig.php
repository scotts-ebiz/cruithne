<?php

namespace SMG\Subscriptions\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class RecurlyConfig
{
	
	/**
	 * Path used for storing the setting values in the
	 * core_config_data table
	 */
	const RECURLY_CONFIG_PATH_PATTERN = 'recurly/payment/%s';

	/**
     * Scope configuration instance.
     *
     * @var ScopeConfigInterface
     */
	protected $_scopeConfig;
 
 	/**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
    	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param string $field
     * @param int|null $store_id
     * @return mixed
     */
    public function getValue($field, $store_id = null)
    {
    	$path = sprintf(self::RECURLY_CONFIG_PATH_PATTERN, $field);

    	return $this->getScopeConfigData($path, $store_id);
    }

    /**
     * Get scope configuration data.
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    protected function getScopeConfigData($path, $store_id)
    {
        return $this->getScopeConfig()->getValue($path, ScopeInterface::SCOPE_STORE, $store_id);
    }

    /**
     * Get scope configuration instance.
     *
     * @return ScopeConfigInterface
     */
    private function getScopeConfig()
    {
        return $this->_scopeConfig;
    }
    
}