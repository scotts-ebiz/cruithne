<?php

namespace SMG\SubscriptionApi\Helper;

use SMG\SubscriptionApi\Model\Subscription;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Session\SessionManagerInterface as Session;
use Magento\Store\Model\ScopeInterface;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\Collection as Collection;
use SMG\SubscriptionApi\Model\ResourceModel\Subscription\CollectionFactory as SubscriptionCollectionFactory;

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
     * @var Session
     */
    protected $_session;

    /**
     * @var SubscriptionCollectionFactory
     */
    protected $_subscriptionCollectionFactory;

    public function __construct(
        Session $session,
        SubscriptionCollectionFactory $subscriptionCollectionFactory,
        Context $context
    ) {
        parent::__construct($context);

        $this->_session = $session;
        $this->_subscriptionCollectionFactory = $subscriptionCollectionFactory;
    }

    /**
     * Add subscription to cart based on session details.
     *
     * @return bool
     */
    public function addSessionSubscriptionToCart()
    {
        $details = $this->_session->getData('subscription_details');
        $quizID = $this->_session->getData('quiz_id');

        /** @var Collection $subscriptionCollection */
        $subscriptionCollection = $this->_subscriptionCollectionFactory->create();

        /** @var Subscription $subscription */
        $subscription = $subscriptionCollection
            ->addFieldToFilter('quiz_id', $quizID)
            ->getFirstItem();

        if (! $subscription->getId() || !isset($details['subscription_plan'], $details['addons'])) {
            $this->_logger->error('Could not add subscription to the cart. Missing subscription or session details.');

            return false;
        }

        try {
            $subscription->setData('subscription_type', $details['subscription_plan'])->save();
            $subscription->generateShipDates();
            $subscription->addSubscriptionToCart($details['addons']);

            return true;
        } catch (\Exception $e) {
            $this->_logger->error('Could not add subscription to the cart. ' . $e->getMessage());

            return false;
        }
    }

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

    /**
     * Get Subscription Data For Data Sync.
     *
     * @return array
     */
    public function getSubscriptionDataForSync()
    {
        /** @var Collection $collection **/
        $list = $this->_subscriptionCollectionFactory->create();

        return $list->getData();
    }
}
