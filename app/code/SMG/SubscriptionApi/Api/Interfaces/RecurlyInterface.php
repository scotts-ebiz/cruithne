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
     * @return array
     *
     * @api
     */
    public function createRecurlySubscription($token);

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

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @return array
     *
     * @api
     */
    public function processSeasonalInvoice();
}
