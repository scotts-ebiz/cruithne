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

}
