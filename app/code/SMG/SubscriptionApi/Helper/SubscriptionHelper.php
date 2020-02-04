<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class SubscriptionHelper
 * @package SMG\SubscriptionApi\Helper
 */
class SubscriptionHelper extends AbstractHelper
{

    const SUBSCRIPTION_CONFIG_ACTIVE = 'smg/subscription/active';
    const SUBSCRIPTION_CONFIG_USE_CSRF = 'smg/subscription/usecsrf';
	const SUBSCRIPTION_CONFIG_SHIP_DAYS_START = 'smg/subscription/ship_days_start';
	const SUBSCRIPTION_CONFIG_SHIP_DAYS_END = 'smg/subscription/ship_days_end';

	/**
	 * Is Subscription Module Active
	 *
	 * @param null $store_id
	 * @return bool
	 */
	public function isActive($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::SUBSCRIPTION_CONFIG_ACTIVE,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

    /**
     * Check whether we should use CSRF token checking
     *
     * @param null $store_id
     * @return mixed
     */
    public function useCsrf($store_id = null)
    {
        $useCsrf = $this->scopeConfig->getValue(
            self::SUBSCRIPTION_CONFIG_USE_CSRF,
            ScopeInterface::SCOPE_STORE,
            $store_id
        );

        return $useCsrf === '1';
    }

	/**
	 * Return number of days to open shipment before application window
	 *
	 * @param null $store_id
	 * @return int
	 */
	public function getShipDaysStart($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::SUBSCRIPTION_CONFIG_SHIP_DAYS_START,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}

	/**
	 * Return number of days to close shipment before application window
	 *
	 * @param null $store_id
	 * @return int
	 */
	public function getShipDaysEnd($store_id = null)
	{
		return $this->scopeConfig->getValue(
			self::SUBSCRIPTION_CONFIG_SHIP_DAYS_END,
			ScopeInterface::SCOPE_STORE,
			$store_id
		);
	}
}