<?php
namespace SMG\SubscriptionApi\Api\Interfaces;

/**
 * @api
 */
interface RecurlyInterface
{
    /**
     * Check existing Recurly subscriptions
     *
     * @return SMG\SubscriptionApi\Api\RecurlySubscription[]
     *
     * @api
     */
    public function checkRecurlySubscription();

    /**
     * Cancel Recurly subscriptions
     *
     * @param string $cancelReason
     * @return SMG\SubscriptionApi\Api\RecurlySubscription[]
     *
     * @api
     */
    public function cancelRecurlySubscription($cancelReason);

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @return SMG\SubscriptionApi\Api\RecurlySubscription[]
     *
     * @api
     */
    public function processSeasonalInvoice();
}
