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
     * @return mixed
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
     * @return mixed
     *
     * @api
     */
    public function createSubscription($key, $token, $quiz_id, $billing_address, $billing_same_as_shipping);

    /**
     * Get Subscription Data For Data Sync.
     *
     * @return mixed
     */
    public function getSubscriptionDataForSync();

    /**
     *  Renew subscription for given master id.
     *
     * @param string $master_subscription_id
     * @return mixed
     */
    public function renewSubscription($master_subscription_id);
	
	/**
     *  Cancel subscription for given master id.
     *
     * @param string $master_subscription_id
     * @return mixed
     */
    public function cancelSubscription($master_subscription_id);
}
