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
	 * @param mixed $quiz
	 * @param string $plan
	 * @param bool $cancel_existing
	 * @return array
	 * 
	 * @api
	 */
	public function createRecurlySubscription($token, $quiz, $plan, $cancel_existing);

	/**
	 * Check existing Recurly subscriptions
	 * 
	 * @param string $token
	 * @return array
	 * 
	 * @api
	 */
	public function checkRecurlySubscription($token);

}
