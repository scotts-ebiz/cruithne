<?php

namespace SMG\Recommendations\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
	/**
	 * Active flag
	 */
	const CONFIG_PATH_RECOMMENDATIONS_ACTIVE = 'recommendations/settings/active';

	/**
	 * API Key
	 */
	const CONFIG_PATH_RECOMMENDATIONS_APIKEY = 'recommendations/settings/apikey';

	/**
	 * Check whether Recommendations is active and ready to use
	 * 
	 * @param null $store_id
	 * @return bool
	 */
	public function isActive($store_id = null)
	{
		$active = $this->scopeConfig->getValue(
			self::CONFIG_PATH_RECOMMENDATIONS_ACTIVE,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);

		$apikey = $this->scopeConfig->getValue(
			self::CONFIG_PATH_RECOMMENDATIONS_APIKEY,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);

		return $active && $apikey;
	}

	/**
	 * Get Recommendations API Key
	 * 
	 * @param null $store_id
	 * @return null | string
	 */
	public function getApiKey($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::CONFIG_PATH_RECOMMENDATIONS_APIKEY,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

}