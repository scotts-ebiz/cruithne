<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class RecurlyHelper
 * @package SMG\SubscriptionApi\Helper
 */
class RecurlyHelper extends AbstractHelper
{

	const RECURLY_CONFIG_ACTIVE = 'recurly/payment/active';
	const RECURLY_CONFIG_PRIVATE_API_KEY = 'recurly/payment/apikey';
	const RECURLY_CONFIG_PUBLIC_API_KEY = 'recurly/payment/publicapikey';
	const RECURLY_CONFIG_SUBDOMAIN = 'recurly/payment/subdomain';

	/**
	 * Return state of Recurly payment
	 * 
	 * @param null $store_id
	 * @return bool
	 */
	public function isActive($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::RECURLY_CONFIG_ACTIVE,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

	/**
	 * Return Recurly private API key
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getRecurlyPrivateApiKey($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::RECURLY_CONFIG_PRIVATE_API_KEY,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

	/**
	 * Return Recurly public API key
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getRecurlyPublicApiKey($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::RECURLY_CONFIG_PUBLIC_API_KEY,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

	/**
	 * Return Recurly subdomain
	 * 
	 * @param null $store_id
	 * @return string
	 */
	public function getRecurlySubdomain($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::RECURLY_CONFIG_SUBDOMAIN,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

}