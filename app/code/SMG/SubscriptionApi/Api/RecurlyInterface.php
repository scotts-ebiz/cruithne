<?php
namespace SMG\SubscriptionApi\Api;

/**
 * @api
 */
interface RecurlyInterface
{
	/**
	 * Create new Recurly subscription
	 * 
	 * @param string $token
	 * @param mixed $order
	 * @param bool $cancel_existing
	 * @param mixed $addons
	 * @param string $plan_code
	 * @return array
	 * 
	 * @api
	 */
	public function createRecurlySubscription($token, $order, $cancel_existing, $addons, $plan_code);

	/**
	 * Check existing Recurly subscriptions
	 * 
	 * @param string $token
	 * @param mixed $order
	 * @return array
	 * 
	 * @api
	 */
	public function checkRecurlySubscription($token, $order);

}
