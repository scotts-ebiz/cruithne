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
     * @return array
     *
     * @api
     */
    public function checkRecurlySubscription();

    /**
     * Cancel Recurly subscriptions
     *
     * @param string $cancelReason
     * @return array
     *
     * @api
     */
    public function cancelRecurlySubscription($cancelReason);

    /**
     * Process seasonal invoices sent from Recurly
     *
     * @return array
     *
     * @api
     */
    public function processSeasonalInvoice();
}
