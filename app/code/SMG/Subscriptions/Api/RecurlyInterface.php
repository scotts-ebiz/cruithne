<?php

namespace SMG\Subscriptions\Api;

/**
 * @api
 */
interface RecurlyInterface
{

	/**
     * Create new Recurly subscription
     *
     * @param mixed $token
     * @param mixed $order
	 * @return string
     */
	public function newRecurly($token, $order);
}