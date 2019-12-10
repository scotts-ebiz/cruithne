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
	 * @param mixed $quiz_id
	 * @param string $plan
	 * @param bool $cancel_existing
	 * @return array
	 * 
	 * @api
	 */
	public function createRecurlySubscription($token, $quiz_id, $plan, $cancel_existing);

	/**
	 * Check existing Recurly subscriptions
	 * 
	 * @return array
	 * 
	 * @api
	 */
	public function checkRecurlySubscription();

}
