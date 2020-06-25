<?php
namespace SMG\SubscriptionApi\Api\Interfaces;

/**
 * @api
 */
interface SubscriptionInterface
{

    /**
     * Process recommendation options and build the order project
     *
     * @param string $key
     * @param string $subscription_plan
     * @param mixed $data
     * @param mixed $addons
     * @return SMG\SubscriptionApi\Api\Subscription[]
     *
     * @api
     */
    public function addSubscriptionToCart($key, $subscription_plan, $data, $addons);

    /**
     * Create orders in Magento
     *
     * @param string $key
     * @param string $token
     * @param string $quiz_id
     * @param mixed $billing_address
     * @param bool $billing_same_as_shipping
     * @return SMG\SubscriptionApi\Api\Subscription[]
     *
     * @api
     */
    public function createSubscription($key, $token, $quiz_id, $billing_address, $billing_same_as_shipping);

    /**
     * Get Subscription Data For Data Sync.
     *
     * @return SMG\SubscriptionApi\Api\Subscription[]
     */
    public function getSubscriptionDataForSync();
}
