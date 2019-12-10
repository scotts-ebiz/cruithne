<?php
namespace SMG\SubscriptionApi\Api;

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
	 * @return array
     *
     * @api
	 */
	public function addSubscriptionToCart($key, $subscription_plan, $data, $addons);


	/**
	 * Create orders in Magento
	 * 
	 * @param string $key
	 * @param string $quiz_id
	 * @return array
	 * 
	 * @api
	 */
	public function createOrders($key, $quiz_id);

}
