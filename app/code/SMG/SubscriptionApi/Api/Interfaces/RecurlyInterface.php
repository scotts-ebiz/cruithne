<?php
namespace SMG\SubscriptionApi\Api\Interfaces;

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
	 * @return array
	 * 
	 * @api
	 */
	public function createRecurlySubscription( $token );

	/**
	 * Check existing Recurly subscriptions
	 * 
	 * @return array
	 * 
	 * @api
	 */
	public function checkRecurlySubscription();

	/**
	 * Cancel Recurly subscriptions
	 * 
	 * @return array
	 * 
	 * @api
	 */
	public function cancelRecurlySubscription();

}
