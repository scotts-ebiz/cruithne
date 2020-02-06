<?php

namespace SMG\SubscriptionApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class TestHelper extends AbstractHelper
{
    const SEASONAL_TEST_MODE = 'smg/subscription/seasonal_test_mode';
    const SEASONAL_TEST_MINUTES = 'smg/subscription/seasonal_test_minutes';
    const SEASONAL_FUTURE_ORDERS = 'smg/subscription/seasonal_future_orders';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Is test mode enabled?
     *
     * @param $storeID
     * @return mixed
     */
    public function inTestMode($storeID = null)
    {
        return $this->scopeConfig->getValue(
            self::SEASONAL_TEST_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeID
        );
    }

    /**
     * Test minutes between subscription orders.
     *
     * @param $storeID
     * @return mixed
     */
    public function getTestMinutes($storeID = null)
    {
        return $this->scopeConfig->getValue(
            self::SEASONAL_TEST_MINUTES,
            ScopeInterface::SCOPE_STORE,
            $storeID
        ) ?: 5;
    }
}
